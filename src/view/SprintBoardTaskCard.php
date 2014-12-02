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

  public function getItem() {
    $query = id(new SprintQuery())
        ->setProject($this->project)
        ->setViewer($this->viewer);

    $task = $this->getTask();
    $task_phid = $task->getPHID();
    $owner = $this->getOwner();
    $points = $query->getStoryPoints($task_phid);
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
