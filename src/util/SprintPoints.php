<?php

final class SprintPoints extends Phobject {

private $taskpoints;
private $task_open_status_sum;
private $task_closed_status_sum;
private $tasks;
private $taskPrioritySum = array();

  public function setTaskPoints ($taskpoints) {
    $this->taskpoints = $taskpoints;
    return $this;
  }

  public function setTasks ($tasks) {
    $this->tasks = $tasks;
    return $this;
  }

  public function getTaskPoints($task_phid) {
    $points = null;
    $taskpoints = mpull($this->taskpoints, null, 'getObjectPHID');
    if (!empty($taskpoints)) {
      foreach ($taskpoints as $key => $value) {
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

  public function sumTotalTaskPoints() {
    $points = null;
    if (!empty($this->taskpoints)) {
      $taskpoints = mpull($this->taskpoints, null, 'getObjectPHID');
      foreach ($taskpoints as $key => $value) {
        $points += $value->getfieldValue();
      }
    }
    if (!isset($points)) {
      $points = '0';
    }
    return $points;
  }

  public function sumTotalTasks() {
    $total_tasks = null;
    if (!empty($this->taskpoints)) {
        $total_tasks += count($this->taskpoints);
    }
    return $total_tasks;
  }

  public function getTaskStatus($task) {
    $status = $task->getStatus();
    return $status;
  }

  private function getPriorityName($task) {
    $priority_name = new ManiphestTaskPriority();
    return $priority_name->getTaskPriorityName($task->getPriority());
  }

  private function sumPointsbyStatus ($task) {
    $status = $this->getTaskStatus($task);

    $points = $this->getTaskPoints($task->getPHID());

    if ($status == 'open') {
      $this->task_open_status_sum =
          $this->setTaskOpenStatusSum($this->task_open_status_sum, $points);
    } else if ($status == 'resolved') {
      $this->task_closed_status_sum =
          $this->setTaskClosedStatusSum($this->task_closed_status_sum, $points);
    }
    return;
  }

  private function sumPointsbyPriority ($task) {
    $priority_name = $this->getPriorityName($task);
    $points = $this->getTaskPoints($task->getPHID());
    if ($priority_name) {
      $this->taskPrioritySum = $this->setTaskPrioritySum($this->taskPrioritySum,
          $priority_name,
          $points);
    }
    return;
  }

  private function setTaskPrioritySum ($task_priority_sum, $priority_name,
                                      $points) {
        if (empty($task_priority_sum)) {
          $task_priority_sum[$priority_name] = $points;
        } else {
            if (isset($task_priority_sum[$priority_name])) {
              $task_priority_sum[$priority_name] += $points;
            } else {
              $task_priority_sum[$priority_name] = $points;
            }
        }
    return $task_priority_sum;
  }

  private function setTaskOpenStatusSum ($task_open_status_sum, $points) {
    $task_open_status_sum += $points;
    return $task_open_status_sum;
  }

  private function setTaskClosedStatusSum ($task_closed_status_sum, $points) {
    $task_closed_status_sum += $points;
    return $task_closed_status_sum;
  }

  private function setStatusPoints () {
    foreach ($this->tasks as $task) {
      $this->sumPointsbyStatus($task);
    }
    return $this;
  }

  private function setPriorityPoints () {
    foreach ($this->tasks as $task) {
      $this->sumPointsbyPriority($task);
    }
    return $this;
  }

  public function getStatusSums() {
    $this->setStatusPoints();
    $opensum = $this->getOpenStatusSum();
    $closedsum = $this->getClosedStatusSum();
    return array ($opensum, $closedsum);
  }

  private function getOpenStatusSum() {
    return $this->task_open_status_sum;
  }

  private function getClosedStatusSum() {
    return $this->task_closed_status_sum;
  }

  public function getPrioritySums() {
    $this->setPriorityPoints();
    $priority_points = $this->getTaskPrioritySum();
    return $priority_points;
  }

  private function getTaskPrioritySum() {
    return $this->taskPrioritySum;
  }
}
