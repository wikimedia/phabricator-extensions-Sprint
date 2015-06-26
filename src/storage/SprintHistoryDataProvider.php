<?php

final class SprintHistoryDataProvider {
  private $viewer;
  private $request;
  private $rows;
  private $history;
  private $project;

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest ($request) {
    $this->request = $request;
    return $this;
  }

   public function getRequest () {
    return $this->request;
  }

  public function getRows () {
    return $this->rows;
  }

  public function execute() {
    $this->history = id(new SprintQuery())
        ->setViewer($this->viewer)
        ->getTaskHistory($this->request->getStr('project'));
    if ($this->history) {
      $this->buildSprintHistoryData();
      return $this;
    } else {
      return null;
    }
  }

  private function buildSprintHistoryData() {

    $rows = array();
    foreach ($this->history as $project) {
      $remove_action = $project['projectremoved'];
      $add_action = $project['projectadded'];
      // $projectPHID = $project['projPHID'];
      $projectname = $project['projName'];
      // $transactionPHID = $project['transactionPHID'];
      // $objectPHID = $project['objectPHID'];
      $taskname = $project['taskName'];
      $createdEpoch = $project['createdEpoch'];
      $created = phabricator_date($createdEpoch, $this->viewer);

      $row = $this->buildRowSet($remove_action, $add_action, $projectname, $taskname, $createdEpoch, $created);
      $rows[] = $row;
    }

    $this->rows = array_map(function($a) { return $a['0']; }, $rows);
    return $this;
  }

  private function buildRowSet($remove_action, $add_action, $projectname, $taskname, $createdEpoch, $created) {
    $rows = array();
    $rows[] =  array (
    $remove_action,
    $add_action,
    $projectname,
    $taskname,
    $createdEpoch,
    $created,
    );

    return $rows;
  }
}
