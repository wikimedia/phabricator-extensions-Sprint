<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

final class BurndownDataView extends SprintView {

  private $timeseries;
  private $sprint_data;
  private $project;
  private $viewer;
  private $tasks;
  private $xactions;

  public function setProject ($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setTimeZone ($viewer) {
    $timezone = new DateTimeZone($viewer->getTimezoneIdentifier());
    return $timezone;
  }

  public function render() {
    $chart = $this->buildC3Chart();
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
    $stats = id(new SprintBuildStats());
    $dates = $stats->buildDateArray($start, $end, $this->setTimeZone($this->viewer));
    $this->timeseries = $stats->buildTimeSeries($start, $end, $this->setTimeZone($this->viewer));

    $tasks = $query->getTasks();
    $query->checkNull($start, $end, $tasks);
    $xactions = $query->getXactions($tasks);
    $events = $query->getEvents($xactions, $tasks);

    $this->xactions = mpull($xactions, null, 'getPHID');
    $this->tasks = mpull($tasks, null, 'getPHID');

    $sprint_xaction = id(new SprintTransaction())
        ->setViewer($this->viewer);
    $dates = $sprint_xaction->buildDailyData($events, $start, $end, $dates, $this->xactions);

    $this->sprint_data = $this->setSprintData($dates);
    $data = $stats->buildDataSet($this->sprint_data);
    $data = $this->transposeArray($data);
    return $data;
  }

  private function setSprintData($dates) {
    $stats = id(new SprintBuildStats());
    $dates = $stats->sumSprintStats($dates);
    $sprint_data = $stats->computeIdealPoints($dates);
    return $sprint_data;
}

  private function transposeArray($array) {
    $transposed_array = array();
    if ($array) {
      foreach ($array as $row_key => $row) {
        if (is_array($row) && !empty($row)) {
          foreach ($row as $column_key => $element) {
            $transposed_array[$column_key][$row_key] = $element;
          }
        } else {
          $transposed_array[0][$row_key] = $row;
        }
      }
    }
    return $transposed_array;
   }

  private function buildC3Chart() {
    $data = $this->buildChartDataSet();
    $totalpoints = $data[0];
    $remainingpoints = $data[1];
    $idealpoints = $data[2];
    $pointstoday = $data[3];
    $timeseries = $this->timeseries;

    require_celerity_resource('d3','sprint');
    require_celerity_resource('c3-css','sprint');
    require_celerity_resource('c3','sprint');

    $id = 'chart';
    Javelin::initBehavior('c3-chart', array(
        'hardpoint' => $id,
        'timeseries' => $timeseries,
        'totalpoints' => $totalpoints,
        'remainingpoints' => $remainingpoints,
        'idealpoints' =>   $idealpoints,
        'pointstoday' =>   $pointstoday
    ), 'sprint');

    $chart= id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Burndown for ' . $this->project->getName()))
         ->appendChild(phutil_tag('div',
            array(
                'id' => 'chart',
                'style' => 'width: 100%; height:400px'
            ), ''));

    return $chart;
  }

  /**
   * Format the Burndown data for display on the page.
   *
   * @returns PHUIObjectBoxView
   */
  private function buildBurnDownTable() {
    $data = array();

    foreach ($this->sprint_data as $date) {
      $data[] = array(
          $date->getDate(),
          $date->getTasksTotal(),
          $date->getTasksRemaining(),
          $date->getPointsTotal(),
          $date->getPointsRemaining(),
          $date->getPointsIdealRemaining(),
          $date->getPointsClosedToday(),
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
  private function buildTasksTable() {
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

    $data = $query->getXactionData(SprintConstants::CUSTOMFIELD_TYPE_STATUS);
    $points = $this->getTaskStoryPoints($task->getPHID(),$data);
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
  private function buildEventTable() {
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