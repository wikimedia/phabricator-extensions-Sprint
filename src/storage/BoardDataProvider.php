<?php


final class BoardDataProvider {

  private $start;
  private $end;
  private $project;
  private $viewer;
  private $request;
  private $tasks;
  private $taskpoints;
  private $query;
  private $stats;
  private $timezone;

  public function setStart ($start) {
    $this->start = $start;
    return $this;
  }

  public function setEnd ($end) {
    $this->end = $end;
    return $this;
  }

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

  public function setTimezone ($timezone) {
    $this->timezone = $timezone;
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

  public function setStats ($stats) {
    $this->stats = $stats;
    return $this;
  }

  public function setQuery ($query) {
    $this->query = $query;
    return $this;
  }

  public function buildBoardDataSet() {
    $board_columns = array();
    $columns = $this->query->getProjectColumns();
    $positions = $this->query->getProjectColumnPositionforTask($this->tasks,
        $columns);
    $task_map = array();

    foreach ($this->tasks as $task) {
      $task_phid = $task->getPHID();
      if (empty($positions[$task_phid])) {
        continue;
      }
      $position = $positions[$task_phid];
      $task_map[$position->getColumnPHID()][] = $task_phid;
    }

    foreach ($columns as $column) {
      $board_column = $this->buildColumnTasks($column, $task_map);
      $board_columns[$column->getPHID()] = $board_column;

    }
    $coldata = $this->buildBoardColumnData($board_columns);
    return $coldata;
  }

  private function buildBoardColumnData($board_columns) {
    $coldata = array();
    foreach ($board_columns as $column_phid => $tasks) {
      $colname = $this->getColumnName($column_phid);
      $task_count = count($tasks);
      $task_points_total = $this->getTaskPointsSum($tasks);
      $coldata[] = array(
          $colname, $task_count, $task_points_total,
      );
    }
    return $coldata;
  }

  private function buildColumnTasks($column, $task_map) {
    $task_phids = idx($task_map, $column->getPHID(), array());
    $column_tasks = array_select_keys($this->tasks, $task_phids);
    return $column_tasks;
  }

  public function getColumnName($column_phid) {
    $name = null;
    $column = $this->query->getColumnforPHID($column_phid);
    foreach ($column as $obj) {
      $name = $obj->getDisplayName();
    }
    return $name;
  }

  public function getTaskPointsSum($tasks) {
    $points_sum = null;
    $taskpoints = mpull($this->taskpoints, null, 'getObjectPHID');
    $column_points = array_intersect_key($taskpoints, $tasks);
    if (!empty($column_points)) {
      foreach ($column_points as $key => $value) {
          $points = $value->getfieldValue();
          $points_sum += $points;
      }
      if (!isset($points_sum)) {
        $points_sum = '0';
      }
    }
    return $points_sum;
  }

  public function getProjectColumnXactions() {
    $xactions = array();
    $scope_phid = $this->project->getPHID();
    $query = new PhabricatorFeedQuery();
    $query->setFilterPHIDs(
        array(
            $scope_phid,
        ));
    $query->setViewer($this->viewer);
    $stories = $query->execute();
    foreach ($stories as $xaction) {
      $xaction_date = $xaction->getEpoch();
      if ($xaction_date >= $this->start && $xaction_date <= $this->end) {
        $xaction = $xaction->getPrimaryTransaction();
        switch ($xaction->getTransactionType()) {
          case ManiphestTransaction::TYPE_PROJECT_COLUMN:
            $xactions[] = $xaction;
            break;
          default:
            break;
        }
      }
    }
    return $xactions;
  }

  public function buildChartfromBoardData() {

    $date_array = $this->stats->buildDateArray($this->start, $this->end,
        $this->timezone);
    $xactions = $this->getProjectColumnXactions();
    $xaction_map = mpull($xactions, null, 'getPHID');

    $sprint_xaction = id(new SprintColumnTransaction())
        ->setViewer($this->viewer)
        ->setTaskPoints($this->taskpoints)
        ->setQuery($this->query)
        ->setProject($this->project)
        ->setEvents($xactions);

    $dates = $sprint_xaction->parseEvents($date_array, $xaction_map);
    $this->stats->setTaskPoints($this->taskpoints);
    $sprint_data = $this->stats->setSprintData($dates);
    $data = $this->stats->buildDataSet($sprint_data);
    $data = $this->stats->transposeArray($data);
    return $data;
  }
}
