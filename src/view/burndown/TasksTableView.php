<?php

final class TasksTableView {

  private $project;
  private $viewer;
  private $request;
  private $tasks;
  private $taskpoints;
  private $query;


  public function setProject ($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest ($request) {
    $this->request = $request;
    return $this;
  }

  public function setTasks ($tasks) {
    $this->tasks = $tasks;
    return $this;
  }

  public function setTaskPoints ($taskpoints) {
    $this->taskpoints = $taskpoints;
    return $this;
  }

  public function setQuery ($query) {
    $this->query = $query;
    return $this;
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
                pht('Epoch Created'),
                pht('Date Created'),
                pht('Epoch Updated'),
                pht('Last Update'),
                pht('Assigned to'),
                pht('NumPriority'),
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
            'Epoch Created',
            'Date Created',
            'Epoch Updated',
            'Last Update',
            'Assigned to',
            'NumPriority',
            'Priority',
            'Points',
            'Status'
        )
    );
    $table->setColumnVisibility(
        array(
            true,
            false,
            true,
            false,
            true,
            true,
            false,
            true,
            true,
            true,
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
   * @param string $order
   * @param integer $reverse
   * @return array
   */
  private function buildTasksTree($order, $reverse) {
    $edges = $this->query->getEdges($this->tasks);
    $map = $this->buildTaskMap($edges, $this->tasks);
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->taskpoints);

    // We also collect the phids we need to fetch owner information
    $handle_phids = array();
    foreach ($this->tasks as $task) {
       $handle_phids[$task->getOwnerPHID()] = $task->getOwnerPHID();
    }
    $handles = $this->query->getViewerHandles($this->request, $handle_phids);

    $output = array();
    $rows = array();
    foreach ($this->tasks as $task) {
      if (isset($map[$task->getPHID()]['child'])) {
        $blocked = true;
      } else {
        $blocked = false;
      }

      $ptasks = array();
      $parentphid = null;
      if (isset($map[$task->getPHID()]['parent'])) {
        $blocker = true;
        foreach (($map[$task->getPHID()]['parent']) as $parentphid) {
          $ptask = $this->getTaskforPHID($parentphid);
          $ptasks = array_merge($ptasks, $ptask);
        }
      } else {
        $blocker = false;
      }

      $points = $sprintpoints->getTaskPoints($task->getPHID());

      $row = $this->addTaskToTree($output, $blocked, $ptasks, $blocker,
          $task, $handles, $points);
      list ($task, $cdate, , $udate, , $owner_link, $numpriority, , $points,
          $status) = $row[0];
      $row['sort'] = $this->setSortOrder($row, $order, $task, $cdate, $udate,
          $owner_link, $numpriority, $points, $status);
      $rows[] = $row;
    }
    $rows = isort($rows, 'sort');

    foreach ($rows as $k => $row) {
      unset($rows[$k]['sort']);
    }

    if ($reverse) {
      $rows = array_reverse($rows);
    }

    $a = array();
    $rows = array_map(function($a) { return $a['0']; }, $rows);
    return $rows;
  }

  private function setSortOrder ($row, $order, $task, $cdate, $udate,
                                 $owner_link, $numpriority, $points, $status) {
    switch ($order) {
      case 'Task':
        $row['sort'] = $task;
        break;
      case 'Date Created':
        $row['sort'] = $cdate;
        break;
      case 'Last Update':
        $row['sort'] = $udate;
        break;
      case 'Assigned to':
        $row['sort'] = $owner_link;
        break;
      case 'Priority':
        $row['sort'] = $numpriority;
        break;
      case 'Points':
        $row['sort'] = $points;
        break;
      case 'Status':
        $row['sort'] = $status;
        break;
      default:
        $row['sort'] = -$numpriority;
        break;
    }
    return $row['sort'];
  }

  private function buildTaskMap ($edges, $tasks)
  {
    $map = array();
    foreach ($tasks as $task) {
      if ($parents =
          $edges[$task->getPHID()][ ManiphestTaskDependedOnByTaskEdgeType::EDGECONST]) {
        foreach ($parents as $parent) {
            if (isset($tasks[$parent['dst']]))
            $map[$task->getPHID()]['parent'][] = $parent['dst'];
        }
      } elseif ($children =
          $edges[$task->getPHID()][ManiphestTaskDependsOnTaskEdgeType::EDGECONST]) {
          foreach ($children as $child) {
            if (isset($tasks[$child['dst']])) {
              $map[$task->getPHID()]['child'][] = $child['dst'];
            }
          }
      }
    }
    return $map;
  }

  private function setOwnerLink($handles, $task) {
    // Get the owner object so we can render the owner username/link
    $owner = $handles[$task->getOwnerPHID()];

    if ($owner instanceof PhabricatorObjectHandle) {
      $owner_link = $task->getOwnerPHID() ? $owner->renderLink() : 'none assigned';
    } else {
      $owner_link = 'none assigned';
    }
    return $owner_link;
  }

  private function getTaskCreatedDate($task) {
    $date_created = $task->getDateCreated();
    return $date_created;
  }

  private function getTaskModifiedDate($task) {
    $last_updated = $task->getDateModified();
    return $last_updated;
  }

  private function getPriorityName($task) {
    $priority_name = new ManiphestTaskPriority;
    return $priority_name->getTaskPriorityName($task->getPriority());
  }

  private function getPriority($task) {
    return $task->getPriority();
  }

  private function addTaskToTree($output, $blocked, $ptasks, $blocker,
                                 $task, $handles, $points) {

    $cdate = $this->getTaskCreatedDate($task);
    $date_created = phabricator_datetime($cdate, $this->viewer);
    $udate = $this->getTaskModifiedDate($task);
    $last_updated = phabricator_datetime($udate, $this->viewer);
    $status = $task->getStatus();

    $owner_link = $this->setOwnerLink($handles, $task);
    $priority = $this->getPriority($task);
    $priority_name = $this->getPriorityName($task);

    if ($blocker === true && $task->getStatus() == 'open') {
      $blockericon = $this->getIconforBlocker($ptasks);
    } else {
      $blockericon = '';
    }

    if ($blocked === true && $task->getStatus() == 'open') {
      $blockedicon = $this->getIconforBlocked();
    } else {
      $blockedicon = '';
    }

    $output[] = array(
        phutil_safe_html(phutil_tag(
                'a',
                array(
                    'href' => '/' . $task->getMonogram(),
                    'class' => $task->getStatus() !== 'open'
                        ? 'phui-tag-core-closed'
                        : '',
                ),
                array ($this->buildTaskLink($task), $blockericon,
                    $blockedicon,))),
        $cdate,
        $date_created,
        $udate,
        $last_updated,
        $owner_link,
        $priority,
        $priority_name,
        $points,
        $status,
    );

    return $output;
  }

  private function getIconforBlocker($ptasks) {
      $linktasks = array();
      foreach ($ptasks as $task) {
        $linktasks[] = $this->buildTaskLink($task);
        $links = implode('|  ', $linktasks);
      }

      $sigil = 'has-tooltip';
      $meta  = array(
        'tip' => pht('Blocks: '.$links),
        'size' => 500,
        'align' => 'E',);
      $image = id(new PHUIIconView())
          ->addSigil($sigil)
          ->setMetadata($meta)
          ->setSpriteSheet(PHUIIconView::SPRITE_PROJECTS)
          ->setIconFont('fa-wrench', 'green')
          ->setText('Blocker');
      return $image;
  }

  private function getIconforBlocked() {
      $image = id(new PHUIIconView())
          ->setSpriteSheet(PHUIIconView::SPRITE_PROJECTS)
          ->setIconFont('fa-lock', 'red')
          ->setText('Blocked');
     return $image;
  }

  private function buildTaskLink($task) {
    $linktext = $task->getMonogram().': '.$task->getTitle().'  ';
    return $linktext;
  }

  private function buildTaskMonogram($task) {
    $monogram = '/'.$task->getMonogram().'/';
    return $monogram;
  }

  private function getTaskforPHID($parentphid) {
    $task = id(new ManiphestTaskQuery())
        ->setViewer($this->viewer)
        ->withPHIDs(array($parentphid))
        ->execute();
    return $task;
  }
}
