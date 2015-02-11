<?php

final class TaskTableDataProvider {

  private $project;
  private $viewer;
  private $request;
  private $tasks;
  private $taskpoints;
  private $query;
  private $rows;
  private $order;
  private $reverse;
  private $blocked;
  private $blocker;
  private $ptasks;
  private $points;
  private $handles;


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

  public function getRows () {
    return $this->rows;
  }

  public function getRequest () {
    return $this->request;
  }

  public function execute() {
    return $this->buildTaskTableData();
  }

  private function checkForBlocked($task, $map) {
    $blocked = false;
    if (isset($map[$task->getPHID()]['child'])) {
      foreach (($map[$task->getPHID()]['child']) as $phid) {
        $ctask = $this->getTaskforPHID($phid);
        foreach ($ctask as $child) {
          if (ManiphestTaskStatus::isOpenStatus($child->getStatus())) {
            $blocked = true;
            break;
          }
        }
      }
     return $blocked;
    }
  }

  private function checkForBlocker($task, $map) {
    $ptasks = array();
    $phid = null;
    $blocker = false;
    if (isset($map[$task->getPHID()]['parent'])) {
      $blocker = true;
      foreach (($map[$task->getPHID()]['parent']) as $phid) {
        $ptask = $this->getTaskforPHID($phid);
        $ptasks = array_merge($ptasks, $ptask);
      }
    }
    return array ($blocker, $ptasks);
  }


  private function buildTaskTableData() {
    $edges = $this->query->getEdges($this->tasks);
    $map = $this->buildTaskMap($edges, $this->tasks);
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->taskpoints);

    $this->handles = $this->getHandles();

    $rows = array();
    foreach ($this->tasks as $task) {
      $this->blocked = $this->checkForBlocked($task, $map);
      list ($this->blocker, $this->ptasks) = $this->checkForBlocker($task, $map);
      $this->points = $sprintpoints->getTaskPoints($task->getPHID());

      $row = $this->addTaskToTree($task);
      $rows[] = $row;
    }

    $this->rows = array_map(function($a) { return $a['0']; }, $rows);
    return $this;
  }

  private function buildTaskMap ($edges, $tasks) {
    $map = array();
    foreach ($tasks as $task) {
      $phid = $task->getPHID();
      if ($parents =
          $edges[$phid][ ManiphestTaskDependedOnByTaskEdgeType::EDGECONST]) {
        foreach ($parents as $parent) {
          if (isset($tasks[$parent['dst']])) {
            $map[$phid]['parent'][] = $parent['dst'];
          }
        }
      } else if ($children =
          $edges[$phid][ManiphestTaskDependsOnTaskEdgeType::EDGECONST]) {
        foreach ($children as $child) {
          if (isset($tasks[$child['dst']])) {
            $map[$phid]['child'][] = $child['dst'];
          }
        }
      }
    }
    return $map;
  }

  private function getHandles() {
    $handle_phids = array();
    foreach ($this->tasks as $task) {
      $phid = $task->getOwnerPHID();
      $handle_phids[$phid] = $phid;
    }
    $handles = $this->query->getViewerHandles($this->request, $handle_phids);
    return $handles;
  }

  private function setOwnerLink($handles, $task) {
    $phid = $task->getOwnerPHID();
    $owner = $handles[$phid];

    if ($owner instanceof PhabricatorObjectHandle) {
      $owner_link = $phid ? $owner->renderLink() : 'none assigned';
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
    $priority_name = new ManiphestTaskPriority();
    return $priority_name->getTaskPriorityName($task->getPriority());
  }

  private function getPriority($task) {
    return $task->getPriority();
  }

  private function addTaskToTree($task) {
    $cdate = $this->getTaskCreatedDate($task);
    $date_created = phabricator_date($cdate, $this->viewer);
    $udate = $this->getTaskModifiedDate($task);
    $last_updated = phabricator_date($udate, $this->viewer);
    $status = $task->getStatus();

    $owner_link = $this->setOwnerLink($this->handles, $task);
    $priority = $this->getPriority($task);
    $priority_name = $this->getPriorityName($task);
    $is_open = ManiphestTaskStatus::isOpenStatus($task->getStatus());

    if ($this->blocker === true && $is_open === true) {
      $blockericon = $this->getIconforBlocker();
    } else {
      $blockericon = '';
    }

    if ($this->blocked === true && $is_open === true) {
      $blockedicon = $this->getIconforBlocked();
    } else {
      $blockedicon = '';
    }
    $output = array();
    $output[] = array(
        phutil_safe_html(phutil_tag(
            'a',
            array(
                'href' => '/'.$task->getMonogram(),
                'class' => $status !== 'open'
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
        $this->points,
        $status,
    );

    return $output;
  }

  private function getIconforBlocker() {
    $linktasks = array();
    $links = null;
    foreach ($this->ptasks as $task) {
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

  private function getTaskforPHID($phid) {
    $task = id(new ManiphestTaskQuery())
        ->setViewer($this->viewer)
        ->withPHIDs(array($phid))
        ->execute();
    return $task;
  }
}
