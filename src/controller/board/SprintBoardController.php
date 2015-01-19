<?php

abstract class SprintBoardController
  extends SprintController {

  private $project;

  protected function setProject(PhabricatorProject $project) {
    $this->project = $project;
    return $this;
  }
  protected function getProject() {
    return $this->project;
  }
}
