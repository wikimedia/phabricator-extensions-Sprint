<?php

final class SprintDataView extends SprintView {

  private $request;
  private $timezone;
  private $timeseries;
  private $project;
  private $viewer;
  private $tasks;
  private $taskpoints;
  private $events;
  private $start;
  private $end;
  private $before;

  public function setProject($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest($request) {
    $this->request = $request;
    return $this;
  }

  public function render() {
    $query = id(new SprintQuery())
        ->setProject($this->project)
        ->setViewer($this->viewer)
        ->setPHID($this->project->getPHID());

    $tasks = $query->getTasks();
    $this->taskpoints = $query->getTaskData();
    $this->tasks = mpull($tasks, null, 'getPHID');
    $stats = id(new SprintStats());

    $this->setStartandEndDates($query);
    $this->setTimezone($stats);
    $this->setBefore($stats);
    $this->setTimeSeries($stats);


    $show_burndown = PhabricatorEnv::getEnvConfig('sprint.show-burndown');
    $show_pies = PhabricatorEnv::getEnvConfig('sprint.show-pies');
    $show_board_data = PhabricatorEnv::getEnvConfig('sprint.show-board-data');
    if ($show_pies == true || $show_burndown == true || $show_board_data == true) {
      $board_model = id(new BoardDataProvider())
          ->setStart($this->start)
          ->setEnd($this->end)
          ->setProject($this->project)
          ->setViewer($this->viewer)
          ->setRequest($this->request)
          ->setTasks($this->tasks)
          ->setTimezone($this->timezone)
          ->setTaskPoints($this->taskpoints)
          ->setStats($stats)
          ->setQuery($query)
          ->setTimeSeries($this->timeseries)
          ->execute();
    } else {
      $board_model = null;
    }

    if ($show_pies == true) {
      $board_data_pie_view = id(new BoardDataPieView())
          ->setBoardData($board_model);
      $pies = $board_data_pie_view->buildPieBox();
    } else {
      $pies = null;
    }

    if ($show_board_data == true) {
      $board_data_table_view = id(new BoardDataTableView())
          ->setBoardData($board_model);
      $board_table = $board_data_table_view->buildBoardDataTable();
    } else {
      $board_table = null;
    }

    if ($show_burndown == true) {
      $board_chart_view = id(new BurndownChartView())
          ->setChartData($board_model);
      $board_chart = $board_chart_view->buildBurndownChart();
    } else {
      $board_chart = null;
    }

    $show_tasks = PhabricatorEnv::getEnvConfig('sprint.show-tasks-table');
    if ($show_tasks == true) {
      $task_table_model = id(new TaskTableDataProvider())
          ->setProject($this->project)
          ->setViewer($this->viewer)
          ->setRequest($this->request)
          ->setTasks($this->tasks)
          ->setTaskPoints($this->taskpoints)
          ->setQuery($query)
          ->execute();
      $tasks_table_view = id(new TasksTableView())
          ->setTableData($task_table_model);
      $tasks_table = $tasks_table_view->buildTasksTable();
    } else {
      $tasks_table = null;
    }

    $show_events = PhabricatorEnv::getEnvConfig('sprint.show-events-table');
    if ($show_events == true) {
      $this->setEvents($query);
      $event_table_view = id(new EventTableView())
          ->setProject($this->project)
          ->setViewer($this->viewer)
          ->setRequest($this->request)
          ->setEvents($this->events)
          ->setTasks($this->tasks);
      $event_table = $event_table_view->buildEventTable(
          $this->start, $this->end);
    } else {
      $event_table = null;
    }

    return array($board_chart, $board_table, $pies, $tasks_table,
     $event_table,
    );
  }

  private function setStartandEndDates($query) {
    $field_list = $query->getCustomFieldList();
    $aux_fields = $query->getAuxFields($field_list);
    $this->start = $query->getStartDate($aux_fields);
    $this->end = $query->getEndDate($aux_fields);
    return $this;
  }

  private function setBefore($stats) {
    $this->before = $stats->buildBefore($this->start, $this->timezone);
    return $this;
  }

  private function setTimezone($stats) {
    $this->timezone = $stats->setTimezone($this->viewer);
    return $this;
  }

  private function setTimeSeries($stats) {
    $this->timeseries = $stats->buildTimeSeries($this->start, $this->end);
    return $this;
  }

  private function setEvents($query) {
    $xactions = $query->getXactions($this->tasks);
    $this->events = $query->getEvents($xactions, $this->tasks);
    return $this;
  }
}
