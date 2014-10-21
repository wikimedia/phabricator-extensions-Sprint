<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

final class BurndownDataView extends SprintView {

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

  private $dates;
  private $data;
   // Project associated with this burndown.
  private $project;
  private $viewer;
  private $tasks;
  private $xactions;
  private $task_points = array();
  private $task_statuses = array();
  private $task_in_sprint = array();

  public function setProject ($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function render() {

    $chart = $this->buildBurnDownChart();
    $tasks_table = $this->buildTasksTable();
    $burndown_table = $this->buildBurnDownTable();
    $event_table = $this->buildEventTable();
    return array ($chart, $tasks_table, $burndown_table, $event_table);
  }

  private function buildChartDataSet() {

    $query = id(new SprintQuery())
         ->setProject($this->project)
         ->setViewer($this->viewer);
    $aux_fields = $query->getAuxFields();
    $start = $query->getStartDate($aux_fields);
    $end = $query->getEndDate($aux_fields);

    $tasks = $query->getTasks();

    $query->checkNull($start, $end, $tasks);

    $xactions = $query->getXactions($tasks);

    $stats = id(new SprintBuildStats());
    $this->dates = $stats->buildDateArray($start, $end);

    $events = $query->getEvents($xactions, $tasks);

    $this->xactions = mpull($xactions, null, 'getPHID');
    $this->tasks = mpull($tasks, null, 'getPHID');

    $this->buildDailyData($events, $start, $end);


    $stats->sumSprintStats($this->dates);
    $stats->computeIdealPoints($this->dates);


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

  // Now loop through the events and build the data for each day
  private function buildDailyData($events, $start, $end) {

    foreach ($events as $event) {

      $xaction = $this->xactions[$event['transactionPHID']];
      $xaction_date = $xaction->getDateCreated();
      $task_phid = $xaction->getObjectPHID();

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

  private function buildBurnDownChart() {

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
    $stats = id(new SprintBuildStats());
    $stats->sumSprintStats($this->dates);
    $stats->computeIdealPoints($this->dates);
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
    $query = id(new SprintQuery())
        ->setProject($this->project)
        ->setViewer($this->viewer);

    // Get the owner object so we can render the owner username/link
    $owner = $handles[$task->getOwnerPHID()];

    // If this task is already is this tree, this is a repeat.
    $repeat = isset($included[$task->getPHID()]);

    $points_data = $query->getPointsData();
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
    $query = id(new SprintQuery())
        ->setProject($this->project)
        ->setViewer($this->viewer);
    $aux_fields = $query->getAuxFields();
    $start = $query->getStartDate($aux_fields);
    $end = $query->getEndDate($aux_fields);

    $tasks = $query->getTasks();

    $query->checkNull($start, $end, $tasks);

    $xactions = $query->getXactions($tasks);

    $events = $query->getEvents($xactions, $tasks);
    $rows = array();
    foreach ($events as $event) {
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
}