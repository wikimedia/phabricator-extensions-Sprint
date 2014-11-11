<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

final class BurndownDataView extends SprintView {

  private $request;
  private $timeseries;
  private $sprint_data;
  private $project;
  private $viewer;
  private $tasks;
  private $xactions;
  private $task_open_status_sum;
  private $task_closed_status_sum;

  public function setProject ($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest ($request) {
    $this->request =  $request;
    return $this;
  }

  public function render() {
    $chart = $this->buildC3Chart();
    $tasks_table = $this->buildTasksTable();
    $pie = $this->buildC3Pie();
    $burndown_table = $this->buildBurnDownTable();
    $event_table = $this->buildEventTable();
    return array ($chart, $tasks_table, $pie, $burndown_table, $event_table);
  }

  private function buildChartDataSet() {
    $query = id(new SprintQuery())
         ->setProject($this->project)
         ->setViewer($this->viewer);
    $aux_fields = $query->getAuxFields();
    $start = $query->getStartDate($aux_fields);
    $end = $query->getEndDate($aux_fields);
    $stats = id(new SprintBuildStats());
    $timezone = $stats->setTimezone($this->viewer);
    $dates = $stats->buildDateArray($start, $end, $timezone);
    $this->timeseries = $stats->buildTimeSeries($start, $end);

    $tasks = $query->getTasks();
    $query->checkNull($start, $end, $tasks);
    $xactions = $query->getXactions($tasks);
    $events = $query->getEvents($xactions, $tasks);

    $this->xactions = mpull($xactions, null, 'getPHID');
    $this->tasks = mpull($tasks, null, 'getPHID');

    $sprint_xaction = id(new SprintTransaction())
        ->setViewer($this->viewer);
    $sprint_xaction->buildStatArrays($tasks);
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

  private function buildC3Pie() {
    $task_open_status_sum = $this->task_open_status_sum;
    $task_closed_status_sum = $this->task_closed_status_sum;

    require_celerity_resource('d3','sprint');
    require_celerity_resource('c3-css','sprint');
    require_celerity_resource('c3','sprint');

    $id = 'pie';
    Javelin::initBehavior('c3-pie', array(
        'hardpoint' => $id,
        'open' => $task_open_status_sum,
        'resolved' => $task_closed_status_sum,
    ), 'sprint');

    $pie= id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Task Status Report for ' . $this->project->getName()))
        ->appendChild(phutil_tag('div',
            array(
                'id' => 'pie',
                'style' => 'width: 100%; height:200px'
            ), ''));

    return $pie;
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
    $order = $this->request->getStr('order', 'name');
    list($order, $reverse) = AphrontTableView::parseSort($order);
    $rows = $this->buildTasksTree($order, $reverse);
    $table = id(new AphrontTableView($rows))
        ->setHeaders(
            array(
                pht('Task'),
                pht('Assigned to'),
                pht('Priority'),
                pht('Points'),
                pht('Status'),
            ));
    $table->makeSortable(
        $this->request->getRequestURI(),
        'order',
        $order,
        $reverse,
        array(
            'Task',
            'Assigned to',
            'Priority',
            'Points',
            'Status'
         )
    );

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Tasks in this Sprint'))
        ->appendChild($table);

    return $box;
  }

  private function setSortOrder ($row, $order, $task, $assigned_to, $priority,
                                 $points, $status) {
    switch ($order) {
      case 'Task':
        $row['sort'] = $task;
        break;
      case 'Assigned to':
        $row['sort'] = $assigned_to;
        break;
      case 'Priority':
        $row['sort'] = $priority;
        break;
      case 'Points':
        $row['sort'] = $points;
        break;
      case 'Status':
      default:
        $row['sort'] = $status;
        break;
    }
    return $row['sort'];
  }


  private function buildTaskMap ($edges) {
    $map = array();
    foreach ($this->tasks as $task) {
      if ($parents =
          $edges[$task->getPHID()][PhabricatorEdgeConfig::TYPE_TASK_DEPENDED_ON_BY_TASK]) {
        foreach ($parents as $parent) {
          // Make sure this task is in this sprint.
          if (isset($this->tasks[$parent['dst']]))
            $map[$task->getPHID()]['parents'][] = $parent['dst'];
        }
      }

      if ($children =
          $edges[$task->getPHID()][PhabricatorEdgeConfig::TYPE_TASK_DEPENDS_ON_TASK]) {
        foreach ($children as $child) {
          // Make sure this task is in this sprint.
          if (isset($this->tasks[$child['dst']])) {
            $map[$task->getPHID()]['children'][] = $child['dst'];
          }
        }
      }
    }
    return $map;
  }

  /**
   * This builds a tree of the tasks in this project. Due to the acyclic nature
   * of tasks, we ntake some steps to reduce and call out duplication.
   *
   * We ignore any tasks not in this sprint.
   *
   * @return array
   */
  private function buildTasksTree($order, $reverse) {
    $query = id(new SprintQuery());
    $edges = $query->getEdges($this->tasks);
    $map = $this->buildTaskMap($edges);

    // We also collect the phids we need to fetch owner information
    $handle_phids = array();
    foreach ($this->tasks as $task) {
      // Get the owner (assigned to) phid
      $handle_phids[$task->getOwnerPHID()] = $task->getOwnerPHID();
    }
    $handles = $query->getViewerHandles($this->request, $handle_phids);

    // Now we loop through the tasks, and add them to the output
    $output = array();
    $rows = array();
    foreach ($this->tasks as $task) {
      // If parents is set, it means this task has a parent in this sprint so
      // skip it, the parent will handle adding this task to the output
      if (isset($map[$task->getPHID()]['parents'])) {
        continue;
      }

      $row = $this->addTaskToTree($output, $task, $map, $handles);
      list ($task, $assigned_to, $priority,$points, $status) = $row[0];
      $row['sort'] = $this->setSortOrder($row, $order, $task, $assigned_to, $priority,$points, $status);
      $rows[] = $row;
    }
    $rows = isort($rows, 'sort');

    foreach ($rows as $k => $row) {
      unset($rows[$k]['sort']);
    }

    if ($reverse) {
      $rows = array_reverse($rows);
    }
    $rows = array_map( function( $a ) { return $a['0']; }, $rows );
    return $rows;
  }

  private function addTaskToTree($output, $task, $map, $handles, $depth = 0) {
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
    $status = $this->setTaskStatus($task);
    $this->sumPointsbyStatus($status, $points);
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
        $status,
    );
    $included[$task->getPHID()] = $task->getPHID();

    if (isset($map[$task->getPHID()]['children'])) {
      foreach ($map[$task->getPHID()]['children'] as $child) {
        $child = $this->tasks[$child];
        $this->addTaskToTree($output, $child, $map, $handles, $depth + 1);
      }
    }
    return $output;
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

  private function setTaskStatus($task) {
    $status = $task->getStatus();
   return $status;
  }

  private function sumPointsbyStatus ($status, $points) {
    $stats = id(new SprintBuildStats());
    if ($status == 'open') {
      $this->task_open_status_sum = $stats->setTaskOpenStatusSum($this->task_open_status_sum, $points);
    } elseif ($status == 'resolved') {
      $this->task_closed_status_sum = $stats->setTaskClosedStatusSum($this->task_closed_status_sum, $points);
    }
   }
}