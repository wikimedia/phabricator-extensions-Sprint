<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

class BurndownData {

  // Array of BurndownDataDates
  // There are two special keys, 'before' and 'after'
  //
  // Looks like: array(
  //   'before' => BurndownDataDate
  //   'Tue Jun 3' => BurndownDataDate
  //   'Wed Jun 4' => BurndownDataDate
  //   ...
  //   'after' => BurndownDataDate
  // )
  private $type_status = 'core:customfield';
  private $storypoints;
  private $dates;
  private $data;
  // These hold an array of each task, and how many points are assigned, and
  // whether it's open or closed. These values change as we progress through
  // time, so that changes to points or status reflect on the graph.
  private $task_points = array();
  private $task_statuses = array();
  private $task_in_sprint = array();
  // Project associated with this burndown.
  private $project;
  private $viewer;
  private $tasks;
  private $events;
  private $xactions;

  public function __construct($project, $viewer) {

    $this->project = $project;
    $this->viewer = $viewer;

    // We need the custom fields so we can pull out the start and end date
    $aux_fields = $this->getAuxFields();
    $start = $this->getStartDate($aux_fields);
    $end = $this->getEndDate($aux_fields);
    $this->dates = $this->buildDateArray($start, $end);

    $tasks = $this->getTasks();

    $this->checkNull($start, $end, $tasks);

    $xactions = $this->getXactions($tasks);

    $this->examineXactions($xactions, $tasks);

    $this->buildDailyData($start, $end);
    $this->buildTaskArrays();


    $this->sumSprintStats();
    $this->computeIdealPoints();

  }

  private function getAuxFields() {
    $field_list = PhabricatorCustomField::getObjectFields($this->project, PhabricatorCustomField::ROLE_EDIT);
    $field_list->setViewer($this->viewer);
    $field_list->readFieldsFromStorage($this->project);
    $aux_fields = $field_list->getFields();
    return $aux_fields;
  }

  private function getStartDate($aux_fields) {
    $start = idx($aux_fields, 'isdc:sprint:startdate')
        ->getProxy()->getFieldValue();
    return $start;
  }

  private function getEndDate($aux_fields) {
    $end = idx($aux_fields, 'isdc:sprint:enddate')
        ->getProxy()->getFieldValue();
    return $end;
  }

  private function buildDateArray($start, $end) {
    // Build an array of dates between start and end
    $period = new DatePeriod(
        id(new DateTime("@" . $start))->setTime(0, 0),
        new DateInterval('P1D'), // 1 day interval
        id(new DateTime("@" . $end))->modify('+1 day')->setTime(0, 0));

    $dates = array('before' => new BurndownDataDate('Start of Sprint'));
    foreach ($period as $day) {
      $dates[$day->format('D M j')] = new BurndownDataDate(
          $day->format('D M j'));
    }
    $dates['after'] = new BurndownDataDate('After end of Sprint');
    return $dates;
  }

  // Examine all the transactions and extract "events" out of them. These are
  // times when a task was opened or closed. Make some effort to also track
  // "scope" events (when a task was added or removed from a project).
  private function examineXactions($xactions, $tasks) {
    $scope_phids = array($this->project->getPHID());
    $this->events = $this->extractEvents($xactions, $scope_phids);

    $this->xactions = mpull($xactions, null, 'getPHID');
    $this->tasks = mpull($tasks, null, 'getPHID');

  }

  // Load the data for the chart. This approach tries to be simple, but loads
  // and processes large amounts of unnecessary data, so it is not especially
  // fast. Some performance improvements can be made at the cost of fragility
  // by using raw SQL; real improvements can be made once Facts comes online.

  // First, load *every task* in the project. We have to do something like
  // this because there's no straightforward way to determine which tasks
  // have activity in the project period.
  private function getTasks() {
    $tasks = id(new ManiphestTaskQuery())
        ->setViewer($this->viewer)
        ->withAnyProjects(array($this->project->getPHID()))
        ->execute();
    return $tasks;
  }

  private function checkNull($start, $end, $tasks) {
    if (!$start OR !$end) {
      throw new BurndownException("This project is not set up for Burndowns, "
          . "make sure it has 'Sprint' in the name, and then edit it to add the "
          . "sprint start and end date.");
    }

    if (!$tasks) {
      throw new BurndownException("This project has no tasks.");
    }
  }

  // Now load *every transaction* for those tasks. This loads all the
  // comments, etc., for every one of the tasks. Again, not very fast, but
  // we largely do not have ways to select this data more narrowly yet.
  private function getXactions($tasks) {
    $task_phids = mpull($tasks, 'getPHID');

    $xactions = id(new ManiphestTransactionQuery())
        ->setViewer($this->viewer)
        ->withObjectPHIDs($task_phids)
        ->execute();
    return $xactions;
  }

  // Now that we have the data for each day, we need to loop over and sum
  // up the relevant columns
  private function sumSprintStats() {
    $previous = null;
    foreach ($this->dates as $current) {
      $current->tasks_total = $current->tasks_added_today;
      $current->points_total = $current->points_added_today;
      $current->tasks_remaining = $current->tasks_added_today;
      $current->points_remaining = $current->points_added_today;
      if ($previous) {
        $current->tasks_total += $previous->tasks_total;
        $current->points_total += $previous->points_total;
        $current->tasks_remaining += $previous->tasks_remaining - $current->tasks_closed_today;
        $current->points_remaining += $previous->points_remaining - $current->points_closed_today;
      }
      $previous = $current;
    }
    return;
  }

  // Build arrays to store current point and closed status of tasks as we
  // progress through time, so that these changes reflect on the graph
  private function buildTaskArrays() {
    $this->task_points = array();
    $this->task_statuses = array();
    foreach ($this->tasks as $task) {
      $this->task_points[$task->getPHID()] = 0;
      $this->task_statuses[$task->getPHID()] = null;
      $this->task_in_sprint[$task->getPHID()] = 0;
    }
    return;
  }

  // Now loop through the events and build the data for each day
  private function buildDailyData($start, $end) {
    foreach ($this->events as $event) {

      $xaction = $this->xactions[$event['transactionPHID']];
      $xaction_date = $xaction->getDateCreated();
      $task_phid = $xaction->getObjectPHID();
      // $task = $this->tasks[$task_phid];

      // Determine which date to attach this data to
      if ($xaction_date < $start) {
        $date = 'before';
      } else if ($xaction_date > $end) {
        $date = 'after';
      } else {
        //$date = id(new DateTime("@".$xaction_date))->format('D M j');
        $date = phabricator_format_local_time($xaction_date, $this->viewer, 'D M j');
      }

      switch ($event['type']) {
        case "create":
          // Will be accounted for by "task-add" when the project is added
          // Bet we still include it so it shows on the Events list
          break;
        case "task-add":
          // A task was added to the sprint
          $this->addTaskToSprint($date, $task_phid);
          break;
        case "task-remove":
          // A task was removed from the sprint
          $this->removeTaskFromSprint($date, $task_phid);
          break;
        case "close":
          // A task was closed, mark it as done
          $this->closeTask($date, $task_phid);
          break;
        case "reopen":
          // A task was reopened, subtract from done
          $this->reopenTask($date, $task_phid);
          break;
        case "points":
          // Points were changed
          $this->changePoints($date, $task_phid, $xaction);
          break;
      }
    }
  }

  /**
   * Compute the values for the "Ideal Points" line.
   */

  // This is a cheap hacky way to get business days, and does not account for
  // holidays at all.
  private function computeIdealPoints() {
    $total_business_days = 0;
    foreach ($this->dates as $key => $date) {
      if ($key == 'before' OR $key == 'after')
        continue;
      $day_of_week = id(new DateTime($date->getDate()))->format('w');
      if ($day_of_week != 0 AND $day_of_week != 6) {
        $total_business_days++;
      }
    }

    $elapsed_business_days = 0;
    foreach ($this->dates as $key => $date) {
      if ($key == 'before') {
        $date->points_ideal_remaining = $date->points_total;
        continue;
      } else if ($key == 'after') {
        $date->points_ideal_remaining = 0;
        continue;
      }

      $day_of_week = id(new DateTime($date->getDate()))->format('w');
      if ($day_of_week != 0 AND $day_of_week != 6) {
        $elapsed_business_days++;
      }

      $date->points_ideal_remaining = round($date->points_total *
          (1 - ($elapsed_business_days / $total_business_days)), 1);
    }
  }


  /**
   * These handle the relevant math for adding, removing, closing, etc.
   * @param $date
   * @param $task_phid
   */
  private function addTaskToSprint($date, $task_phid) {
    $this->dates[$date]->tasks_added_today += 1;
    $this->dates[$date]->points_added_today += $this->task_points[$task_phid];
    $this->task_in_sprint[$task_phid] = 1;
  }

  private function removeTaskFromSprint($date, $task_phid) {
    $this->dates[$date]->tasks_added_today -= 1;
    $this->dates[$date]->points_added_today -= $this->task_points[$task_phid];
    $this->task_in_sprint[$task_phid] = 0;
  }

  private function closeTask($date, $task_phid) {
    $this->dates[$date]->tasks_closed_today += 1;
    $this->dates[$date]->points_closed_today += $this->task_points[$task_phid];
    $this->task_statuses[$task_phid] = 'closed';
  }

  private function reopenTask($date, $task_phid) {
    $this->dates[$date]->tasks_closed_today -= 1;
    $this->dates[$date]->points_closed_today -= $this->task_points[$task_phid];
    $this->task_statuses[$task_phid] = 'open';
  }

  private function changePoints($date, $task_phid, $xaction) {
    $this->task_points[$task_phid] = $xaction->getNewValue();

    // Only make changes if the task is in the sprint
    if ($this->task_in_sprint[$task_phid]) {

      // Adjust points for that day
      $this->dates[$date]->points_added_today +=
          $xaction->getNewValue() - $xaction->getOldValue();

      // If the task is closed, adjust completed points as well
      if ($this->task_statuses[$task_phid] == 'closed') {
        $this->dates[$date]->points_closed_today +=
            $xaction->getNewValue() - $xaction->getOldValue();
      }
    }
  }

  private function buildChartDataSet() {
    $data = array(array(
        pht('Date'),
        pht('Total Points'),
        pht('Remaining Points'),
        pht('Ideal Points'),
        pht('Points Today'),
    ));

    $future = false;
    foreach ($this->dates as $key => $date) {
      if ($key != 'before' AND $key != 'after') {
        $future = new DateTime($date->getDate()) > id(new DateTime())->setTime(0, 0);
      }
      $data[] = array(
          $date->getDate(),
          $future ? null : $date->points_total,
          $future ? null : $date->points_remaining,
          $date->points_ideal_remaining,
          $future ? null : $date->points_closed_today,
      );

    }
    return $data;

  }

  public function buildBurnDownChart() {

    $this->data = $this->buildChartDataSet();
    // Format the data for the chart
    $data = json_encode($this->data);

    // This should probably use celerity and/or javelin

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Burndown for ' . $this->project->getName()))
        // Calling phutil_safe_html and passing in <script> tags is a potential
        // security hole. None of this data is direct user input, so we should
        // be fine.
        ->appendChild(phutil_safe_html(<<<HERE
<script type="text/javascript" src="//www.google.com/jsapi"></script>
<script type="text/javascript">
  google.load('visualization', '1', {packages: ['corechart']});
</script>
<script type="text/javascript">

  function drawVisualization() {
    // Create and populate the data table.
    var data = google.visualization.arrayToDataTable($data);

    // Create and draw the visualization.
    var ac = new google.visualization.ComboChart(document.getElementById('visualization'));
    ac.draw(data, {
      height: 400,
      vAxis: {title: "Points"},
      hAxis: {title: "Date"},
      seriesType: "line",
      lineWidth: 3,
      series: {
        0: {color: '#f88'},
        1: {color: '#fb0'},
        2: {color: '#ccc', lineDashStyle: [8,4]},
        3: {type: "bars", color: '#0c0'},
      }
    });
  }

  google.setOnLoadCallback(drawVisualization);
</script>
HERE
        ))
        ->appendChild(phutil_tag('div',
            array(
                'id' => 'visualization',
                'style' => 'width: 100%; height:400px'
            ), ''));

    return $box;
  }

  /**
   * Format the Burndown data for display on the page.
   *
   * @returns PHUIObjectBoxView
   */
  public function buildBurnDownTable() {
    $data = array();
    foreach ($this->dates as $date) {
      $data[] = array(
          $date->getDate(),
          $date->tasks_total,
          $date->tasks_remaining,
          $date->points_total,
          $date->points_remaining,
          $date->points_ideal_remaining,
          $date->points_closed_today,
      );
    }

    $table = id(new AphrontTableView($data))
        ->setHeaders(
            array(
                pht('Date'),
                pht('Total Tasks'),
                pht('Remaining Tasks'),
                pht('Total Points'),
                pht('Remaining Points'),
                pht('Ideal Remaining Points'),
                pht('Points Completed Today'),
            ));

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('DATA'))
        ->appendChild($table);

    return $box;
  }

  /**
   * Format the tasks data for display on the page.
   *
   * @returns PHUIObjectBoxView
   */
  public function buildTasksTable() {

    $rows = $this->buildTasksTree();

    $table = id(new AphrontTableView($rows))
        ->setHeaders(
            array(
                pht('Task'),
                pht('Assigned to'),
                pht('Priority'),
                pht('Points'),
                pht('Status'),
            ));

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Tasks in this Sprint'))
        ->appendChild($table);

    return $box;
  }

  /**
   * This builds a tree of the tasks in this project. Due to the acyclic nature
   * of tasks, we ntake some steps to reduce and call out duplication.
   *
   * We ignore any tasks not in this sprint.
   *
   * @return array
   */
  private function buildTasksTree() {
    // Shorter constants
    $DEPENDS_ON = PhabricatorEdgeConfig::TYPE_TASK_DEPENDS_ON_TASK;
    $DEPENDED_ON = PhabricatorEdgeConfig::TYPE_TASK_DEPENDED_ON_BY_TASK;

    // Load all edges of depends and depended on tasks
    $edges = id(new PhabricatorEdgeQuery())
        ->withSourcePHIDs(array_keys($this->tasks))
        ->withEdgeTypes(array($DEPENDS_ON, $DEPENDED_ON))
        ->execute();

    // First we build a flat map. Each task is in the map at the root level,
    // and lists it's parents and children.
    $map = array();
    foreach ($this->tasks as $task) {
      if ($parents = $edges[$task->getPHID()][$DEPENDED_ON]) {
        foreach ($parents as $parent) {
          // Make sure this task is in this sprint.
          if (isset($this->tasks[$parent['dst']]))
            $map[$task->getPHID()]['parents'][] = $parent['dst'];
        }
      }

      if ($children = $edges[$task->getPHID()][$DEPENDS_ON]) {
        foreach ($children as $child) {
          // Make sure this task is in this sprint.
          if (isset($this->tasks[$child['dst']])) {
            $map[$task->getPHID()]['children'][] = $child['dst'];
          }
        }
      }
    }

    // We also collect the phids we need to fetch owner information
    $handle_phids = array();
    foreach ($this->tasks as $task) {
      // Get the owner (assigned to) phid
      $handle_phids[$task->getOwnerPHID()] = $task->getOwnerPHID();
    }

    $handles = id(new PhabricatorHandleQuery())
        ->setViewer($this->viewer)
        ->withPHIDs($handle_phids)
        ->execute();

    // Now we loop through the tasks, and add them to the output
    $output = array();
    foreach ($this->tasks as $task) {
      // If parents is set, it means this task has a parent in this sprint so
      // skip it, the parent will handle adding this task to the output
      if (isset($map[$task->getPHID()]['parents'])) {
        continue;
      }

      $this->addTaskToTree($output, $task, $map, $handles);
    }

    return $output;
  }

  private function addTaskToTree(&$output, $task, &$map, $handles, $depth = 0) {
    static $included = array();

    // Get the owner object so we can render the owner username/link
    $owner = $handles[$task->getOwnerPHID()];

    // If this task is already is this tree, this is a repeat.
    $repeat = isset($included[$task->getPHID()]);

    $points_data = $this->getPointsData();
    $points = $this->getTaskStoryPoints($task->getPHID(),$points_data);
    $points = trim($points, '"');

    $priority_name = new ManiphestTaskPriority();
    $depth_indent = '';
    for ($i = 0; $i < $depth; $i++) {
      $depth_indent .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    }

    // Build the row
    $output[] = array(
        phutil_safe_html($depth_indent . phutil_tag(
                'a',
                array(
                    'href' => '/' . $task->getMonogram(),
                    'class' => $task->getStatus() !== 'open'
                        ? 'phui-tag-core-closed'
                        : '',
                ),
                $task->getMonogram() . ': ' . $task->getTitle()
            ) . ($repeat ? '&nbsp;&nbsp;<em title="This task is a child of more than one task in this list. Children are only shown on ' .
                'the first occurance">[Repeat]</em>' : '')),
        $task->getOwnerPHID() ? $owner->renderLink() : 'none assigned',
        $priority_name->getTaskPriorityName($task->getPriority()),
        $points,
        $task->getStatus(),
    );
    $included[$task->getPHID()] = $task->getPHID();

    if (isset($map[$task->getPHID()]['children'])) {
      foreach ($map[$task->getPHID()]['children'] as $child) {
        $child = $this->tasks[$child];
        $this->addTaskToTree($output, $child, $map, $handles, $depth + 1);
      }
    }
  }

  /**
   * Format the Event data for display on the page.
   *
   * @returns PHUIObjectBoxView
   */
  public function buildEventTable() {
    $rows = array();
    foreach ($this->events as $event) {
      $task_phid = $this->xactions[$event['transactionPHID']]->getObjectPHID();
      $task = $this->tasks[$task_phid];

      $rows[] = array(
          phabricator_datetime($event['epoch'], $this->viewer),
          phutil_tag(
              'a',
              array(
                  'href' => '/' . $task->getMonogram(),
              ),
              $task->getMonogram() . ': ' . $task->getTitle()),
          $event['title'],
      );
    }

    $table = id(new AphrontTableView($rows))
        ->setHeaders(
            array(
                pht('When'),
                pht('Task'),
                pht('Action'),
            ))
        ->setColumnClasses(
            array(
                '',
                '',
                'wide',
            ));

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Events related to this sprint'))
        ->appendChild($table);

    return $box;
  }

  private function getTaskStoryPoints($task,$points_data) {
    $storypoints = array();
       foreach ($points_data as $k=>$subarray) {
         if (isset ($subarray['objectPHID']) && $subarray['objectPHID'] == $task) {
           $points_data[$k] = $subarray;
           $storypoints = $subarray['newValue'];
         }
       }
    return $storypoints;
  }


  private function getPointsData () {
    $handle = null;

    $project_phid = $this->project->getPHID();
    $table = new ManiphestTransaction();
    $conn = $table->establishConnection('r');

    $joins = '';
    if ($project_phid) {
      $joins = qsprintf(
          $conn,
          'JOIN %T t ON x.objectPHID = t.phid
          JOIN %T p ON p.src = t.phid AND p.type = %d AND p.dst = %s',
          id(new ManiphestTask())->getTableName(),
          PhabricatorEdgeConfig::TABLE_NAME_EDGE,
          PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
          $project_phid);
    }

    $points_data = queryfx_all(
        $conn,
        'SELECT x.objectPHID, x.oldValue, x.newValue, x.dateCreated FROM %T x %Q
        WHERE transactionType = %s
        ORDER BY x.dateCreated ASC',
        $table->getTableName(),
        $joins,
        $this->type_status);

    return $points_data;
  }

  /**
   * Extract important events (the times when tasks were opened or closed)
   * from a list of transactions.
   *
   * @param array<ManiphestTransaction> List of transactions.
   * @param array<phid> List of project PHIDs to emit "scope" events for.
   * @return array<dict> Chronologically sorted events.
   */
  private function extractEvents($xactions, array $scope_phids) {
    assert_instances_of($xactions, 'ManiphestTransaction');

    $scope_phids = array_fuse($scope_phids);

    $events = array();
    foreach ($xactions as $xaction) {
      $old = $xaction->getOldValue();
      $new = $xaction->getNewValue();

      $event_type = null;
      switch ($xaction->getTransactionType()) {
        case ManiphestTransaction::TYPE_STATUS:
          $old_is_closed = ($old === null) ||
                           ManiphestTaskStatus::isClosedStatus($old);
          $new_is_closed = ManiphestTaskStatus::isClosedStatus($new);

          if ($old_is_closed == $new_is_closed) {
            // This was just a status change from one open status to another,
            // or from one closed status to another, so it's not an event we
            // care about.
            break;
          }
          if ($old === null) {
            // This would show as "reopened" even though it's when the task was
            // created so we skip it. Instead we will use the title for created
            // events
            break;
          }

          if ($new_is_closed) {
            $event_type = 'close';
          } else {
            $event_type = 'reopen';
          }
          break;

        case ManiphestTransaction::TYPE_TITLE:
          if ($old === null)
          {
            $event_type = 'create';
          }
          break;

        // Project changes are "core:edge" transactions
        case PhabricatorTransactions::TYPE_EDGE:

          // We only care about ProjectEdgeType
          if (idx($xaction->getMetadata(), 'edge:type') !==
            PhabricatorProjectObjectHasProjectEdgeType::EDGECONST)
            break;

          $old = ipull($old, 'dst');
          $new = ipull($new, 'dst');

          $in_old_scope = array_intersect_key($scope_phids, $old);
          $in_new_scope = array_intersect_key($scope_phids, $new);

          if ($in_new_scope && !$in_old_scope) {
            $event_type = 'task-add';
          } else if ($in_old_scope && !$in_new_scope) {
            // NOTE: We will miss some of these events, becuase we are only
            // examining tasks that are currently in the project. If a task
            // is removed from the project and not added again later, it will
            // just vanish from the chart completely, not show up as a
            // scope contraction. We can't do better until the Facts application
            // is avialable without examining *every* task.
            $event_type = 'task-remove';
          }
          break;

        case PhabricatorTransactions::TYPE_CUSTOMFIELD:
          if ($xaction->getMetadataValue('customfield:key') == 'isdc:sprint:storypoints') {
            // POINTS!
            $event_type = 'points';
          }
          break;

        default:
          // This is something else (comment, subscription change, etc) that
          // we don't care about for now.
          break;
      }

      // If we found some kind of event that we care about, stick it in the
      // list of events.
      if ($event_type !== null) {
        $events[] = array(
          'transactionPHID' => $xaction->getPHID(),
          'epoch' => $xaction->getDateCreated(),
          'key'   => $xaction->getMetadataValue('customfield:key'),
          'type'  => $event_type,
          'title' => $xaction->getTitle(),
        );
      }
    }

    // Sort all events chronologically.
    $events = isort($events, 'epoch');

    return $events;
  }

}