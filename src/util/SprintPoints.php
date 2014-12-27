<?php

final class SprintPoints {

private $taskpoints;
private $task_open_status_sum;
private $task_closed_status_sum;
private $tasks;

  public function setTaskPoints ($taskpoints) {
    $this->taskpoints = $taskpoints;
    return $this;
  }

  public function setTasks ($tasks) {
    $this->tasks = $tasks;
    return $this;
  }

  public function getTaskPoints($task_phid) {
    $taskpoints = mpull($this->taskpoints, null, 'getObjectPHID');
    if (!empty($taskpoints)) {
      foreach ($taskpoints as $key=>$value) {
        if ($key == $task_phid) {
          $points = $value->getfieldValue();
        }
      }
      if (!isset($points)) {
        $points = '0';
      }
    }
    return $points;
  }

  public function getTaskStatus($task) {
    $status = $task->getStatus();
    return $status;
  }

  private function sumPointsbyStatus ($task) {
    $status = $this->getTaskStatus($task);

    $points = $this->getTaskPoints($task->getPHID());

    if ($status == 'open') {
      $this->task_open_status_sum = $this->setTaskOpenStatusSum($this->task_open_status_sum, $points);
    } elseif ($status == 'resolved') {
      $this->task_closed_status_sum = $this->setTaskClosedStatusSum($this->task_closed_status_sum, $points);
    }
    return;
  }

  public function setTaskOpenStatusSum ($task_open_status_sum, $points) {
    $task_open_status_sum += $points;
    return $task_open_status_sum;
  }

  public function setTaskClosedStatusSum ($task_closed_status_sum, $points) {
    $task_closed_status_sum += $points;
    return $task_closed_status_sum;
  }

  public function setStatusPoints () {
    foreach ($this->tasks as $task) {
      $this->sumPointsbyStatus($task);
    }
    return $this;
  }

  public function getStatusSums() {
    $this->setStatusPoints();
    $opensum = $this->getOpenStatusSum();
    $closedsum = $this->getClosedStatusSum();
    return array ($opensum, $closedsum);
  }

  public function getOpenStatusSum() {
    return $this->task_open_status_sum;
  }

  public function getClosedStatusSum() {
    return $this->task_closed_status_sum;
  }
}