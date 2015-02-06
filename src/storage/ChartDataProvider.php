<?php

final class ChartDataProvider {

  private $project;
  private $timezone;
  private $viewer;
  private $tasks;
  private $taskpoints;
  private $query;
  private $start;
  private $end;
  private $before;


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

  public function setTasks ($tasks) {
    $this->tasks = $tasks;
    return $this;
  }

  public function setEvents ($events) {
    $this->events = $events;
    return $this;
  }

  public function setTaskPoints ($taskpoints) {
    $this->taskpoints = $taskpoints;
    return $this;
  }

  public function setTimezone ($timezone) {
    $this->timezone = $timezone;
    return $this;
  }

  public function setQuery ($query) {
    $this->query = $query;
    return $this;
  }

  public function setStats ($stats) {
    $this->stats = $stats;
    return $this;
  }

  public function setBefore ($before) {
    $this->before = $before;
    return $this;
  }

  public function buildChartDataSet() {

    $this->query->checkNull($this->start, $this->end, $this->project,
        $this->tasks);

    $date_array = $this->stats->buildDateArray($this->start, $this->end,
        $this->timezone);
    $xactions = $this->query->getXactions($this->tasks);
    $xaction_map = mpull($xactions, null, 'getPHID');

    $sprint_xaction = id(new SprintTransaction())
        ->setViewer($this->viewer)
        ->setTasks($this->tasks)
        ->setTaskPoints($this->taskpoints);

    $dates = $sprint_xaction->parseEvents($this->events, $this->before,
        $this->start, $this->end, $date_array, $xaction_map);

    $sprint_data = $this->stats->setSprintData($dates, $this->before);
    $data = $this->stats->buildDataSet($sprint_data);
    $data = $this->stats->transposeArray($data);
    return $data;
  }

}
