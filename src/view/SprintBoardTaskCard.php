<?php

final class SprintBoardTaskCard {

  private $project;
  private $viewer;
  private $task;
  private $owner;
  private $canEdit;
  private $task_node_id;

  public function setViewer(PhabricatorUser $viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setProject ($project) {
    $this->project = $project;
    return $this;
  }

  public function getViewer() {
    return $this->viewer;
  }

  public function setTask(ManiphestTask $task) {
    $this->task = $task;
    return $this;
  }

  public function setNode($task_node_id) {
    $this->task_node_id = $task_node_id;
    return $this;
  }

  public function getTask() {
    return $this->task;
  }

  public function setOwner(PhabricatorObjectHandle $owner = null) {
    $this->owner = $owner;
    return $this;
  }
  public function getOwner() {
    return $this->owner;
  }

  public function setCanEdit($can_edit) {
    $this->canEdit = $can_edit;
    return $this;
  }

  public function getCanEdit() {
    return $this->canEdit;
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

  public function getStoryPoints($task)  {
    $query = id(new SprintQuery())
        ->setProject($this->project)
        ->setViewer($this->viewer);

    $data = $query->getXactionData(SprintConstants::CUSTOMFIELD_TYPE_STATUS);
    $points = $this->getTaskStoryPoints($task->getPHID(),$data);
    $points = trim($points, '"');
    return $points;
  }

  public function getItem() {
    $task = $this->getTask();
    $owner = $this->getOwner();
    $points = $this->getStoryPoints($task);
    $can_edit = $this->getCanEdit();

    $color_map = ManiphestTaskPriority::getColorMap();
    $bar_color = idx($color_map, $task->getPriority(), 'grey');

    $card = id(new PHUIObjectItemView())
      ->setObjectName('T'.$task->getID())
      ->setHeader($task->getTitle())
      ->setGrippable($can_edit)
      ->setHref('/T'.$task->getID())
      ->addSigil('project-card')
      ->setDisabled($task->isClosed())
      ->setMetadata(
        array(
          'objectPHID' => $task->getPHID(),
          'taskNodeID' => $this->task_node_id,
          'points' => $points,
        ))
      ->addAction(
        id(new PHUIListItemView())
        ->setName(pht('Edit'))
        ->setIcon('fa-pencil')
        ->addSigil('edit-project-card')
        ->setHref('/sprint/board/task/edit/'.$task->getID().'/'))
      ->setBarColor($bar_color);

    if ($owner) {
      $card->addAttribute($owner->renderLink());
    }
    if ($points) {
      $card->addAttribute($points);
    }

    return $card;
  }

}
