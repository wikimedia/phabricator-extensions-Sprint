<?php

final class TasksTableView {

  private $project;
  private $viewer;
  private $request;
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

  public function getTaskOpenStatusSum () {
    return $this->task_open_status_sum;
  }

  public function getTaskClosedStatusSum () {
    return $this->task_closed_status_sum;
  }
  /**
   * Format the tasks data for display on the page.
   *
   * @returns PHUIObjectBoxView
   */
  public function buildTasksTable() {
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

   /**
   * This builds a tree of the tasks in this project. Due to the acyclic nature
   * of tasks, we ntake some steps to reduce and call out duplication.
   *
   * We ignore any tasks not in this sprint.
   *
   * @return array
   */
  private function buildTasksTree($order, $reverse) {
    $query = id(new SprintQuery())
        ->setProject($this->project)
        ->setViewer($this->viewer);
    $tasks = $query->getTasks();
    $tasks = mpull($tasks, null, 'getPHID');
    $edges = $query->getEdges($tasks);
    $map = $this->buildTaskMap($edges, $tasks);

    // We also collect the phids we need to fetch owner information
    $handle_phids = array();
    foreach ($tasks as $task) {
      // Get the owner (assigned to) phid
      $handle_phids[$task->getOwnerPHID()] = $task->getOwnerPHID();
    }
    $handles = $query->getViewerHandles($this->request, $handle_phids);

    // Now we loop through the tasks, and add them to the output
    $output = array();
    $rows = array();
    foreach ($tasks as $task) {
      // If parents is set, it means this task has a parent in this sprint so
      // skip it, the parent will handle adding this task to the output
      if (isset($map[$task->getPHID()]['parents'])) {
        continue;
      }

      $row = $this->addTaskToTree($output, $task, $tasks, $map, $handles);
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

  private function buildTaskMap ($edges, $tasks) {
    $map = array();
    foreach ($tasks as $task) {
      if ($parents =
          $edges[$task->getPHID()][PhabricatorEdgeConfig::TYPE_TASK_DEPENDED_ON_BY_TASK]) {
        foreach ($parents as $parent) {
          // Make sure this task is in this sprint.
          if (isset($tasks[$parent['dst']]))
            $map[$task->getPHID()]['parents'][] = $parent['dst'];
        }
      }

      if ($children =
          $edges[$task->getPHID()][PhabricatorEdgeConfig::TYPE_TASK_DEPENDS_ON_TASK]) {
        foreach ($children as $child) {
          // Make sure this task is in this sprint.
          if (isset($tasks[$child['dst']])) {
            $map[$task->getPHID()]['children'][] = $child['dst'];
          }
        }
      }
    }
    return $map;
  }

  private function getTaskPoints($task) {
    $query = id(new SprintQuery())
        ->setProject($this->project)
        ->setViewer($this->viewer);
    $data = $query->getXactionData(SprintConstants::CUSTOMFIELD_TYPE_STATUS);
    $points = $this->getTaskStoryPoints($task->getPHID(),$data);
    $points = trim($points, '"');
    return $points;
  }

  private function addTaskToTree($output, $task, $tasks, $map, $handles, $depth = 0) {
    static $included = array();

    // If this task is already in this tree, this is a repeat.
    $repeat = isset($included[$task->getPHID()]);

    $points = $this->getTaskPoints($task);
    $priority_name = new ManiphestTaskPriority();
    $status = $this->setTaskStatus($task);
    $depth_indent = '';
    for ($i = 0; $i < $depth; $i++) {
      $depth_indent .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    }

    // Get the owner object so we can render the owner username/link
    $owner = $handles[$task->getOwnerPHID()];

    if ($owner instanceof PhabricatorObjectHandle) {
      $owner_link = $task->getOwnerPHID() ? $owner->renderLink() : 'none assigned';
    } else {
      $owner_link = 'none assigned';
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
        $owner_link,
        $priority_name->getTaskPriorityName($task->getPriority()),
        $points,
        $status,
    );
    $included[$task->getPHID()] = $task->getPHID();

    if (isset($map[$task->getPHID()]['children'])) {
      foreach ($map[$task->getPHID()]['children'] as $child) {
        $child = $tasks[$child];
        $this->addTaskToTree($output, $child, $map, $handles, $depth + 1);
      }
    }
    return $output;
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

  private function sumPointsbyStatus ($task) {
    $stats = id(new SprintBuildStats());
    $status = $this->setTaskStatus($task);
    $points = $this->getTaskPoints($task);
    if ($status == 'open') {
      $this->task_open_status_sum = $stats->setTaskOpenStatusSum($this->task_open_status_sum, $points);
    } elseif ($status == 'resolved') {
      $this->task_closed_status_sum = $stats->setTaskClosedStatusSum($this->task_closed_status_sum, $points);
    }
    return;
  }

  public function setStatusPoints () {
    $query = id(new SprintQuery())
        ->setProject($this->project)
        ->setViewer($this->viewer);
    $tasks = $query->getTasks();
    $tasks = mpull($tasks, null, 'getPHID');
    foreach ($tasks as $task) {
      $this->sumPointsbyStatus($task);
    }
   return;
  }

  public function getOpenStatusSum() {
    return $this->task_open_status_sum;
  }

  public function getClosedStatusSum() {
    return $this->task_closed_status_sum;
  }
}