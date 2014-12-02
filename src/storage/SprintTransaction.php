<?php

final class SprintTransaction  {

  private $viewer;
  private $task_points = array();
  private $task_statuses = array();
  private $task_in_sprint = array();

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function buildDailyData($events, $start, $end, $dates, $xactions) {

    $query = id(new SprintQuery());

    foreach ($events as $event) {
      $xaction = $xactions[$event['transactionPHID']];
      $xaction_date = $xaction->getDateCreated();

      // Determine which date to attach this data to
      $date = null;
      if ($xaction_date >= $start && $xaction_date <= $end) {
        $task_phid = $xaction->getObjectPHID();
        $points = $query->getStoryPoints($task_phid);
        $date = phabricator_format_local_time($xaction_date, $this->viewer, 'D M j');

          switch ($event['type']) {
            case "create":
              // Will be accounted for by "task-add" when the project is added
              // But we still include it so it shows on the Events list
              break;
            case "task-add":
              // A task was added to the sprint
              $this->AddTasksToday($date, $dates);
              $this->AddPointsToday($date, $points, $dates);
              $this->AddTaskInSprint($task_phid);
              break;
            case "task-remove":
              // A task was removed from the sprint
              $this->RemoveTasksToday($date, $dates);
              $this->RemovePointsToday($date, $points, $dates);
              $this->RemoveTaskInSprint($task_phid);
              break;
            case "close":
              // A task was closed, mark it as done
              $this->CloseTasksToday($date, $dates);
              $this->ClosePointsToday($date, $points, $dates);
              $this->CloseTaskStatus($task_phid);
              break;
            case "reopen":
              // A task was reopened, subtract from done
              $this->ReopenedTasksToday($date, $dates);
              $this->ReopenedPointsToday($date, $points, $dates);
              $this->OpenTaskStatus($task_phid);
              break;
            case "points":
              // Points were changed
              $old_point_value = $xaction->getOldValue();
              $this->changePoints($date, $task_phid, $points, $old_point_value, $dates);
              break;
          }
      }
    }
    return $dates;
  }


  public function buildStatArrays($tasks) {
    foreach ($tasks as $task) {
      $this->task_points[$task->getPHID()] = 0;
      $this->task_statuses[$task->getPHID()] = null;
      $this->task_in_sprint[$task->getPHID()] = 0;
    }
    return;
  }

  private function AddTasksToday($date, $dates) {
    $dates[$date]->setTasksAddedToday();
    return $dates;
  }

  private function RemoveTasksToday($date, $dates) {
    $dates[$date]->setTasksRemovedToday();
    return $dates;
  }

  private function CloseTasksToday($date, $dates) {
    $dates[$date]->setTasksClosedToday();
    return $dates;
  }

  private function ReopenedTasksToday($date, $dates) {
    $dates[$date]->setTasksReopenedToday();
    return $dates;
  }

  private function AddPointsToday($date, $points, $dates) {
    $dates[$date]->setPointsAddedToday($points);
    return $dates;
  }

  private function RemovePointsToday($date, $points, $dates) {
    $dates[$date]->setPointsRemovedToday($points);
    return $dates;
  }

  private function ClosePointsToday($date, $points, $dates) {
   $dates[$date]->setPointsClosedToday($points);
    return $dates;
  }

  private function ReopenedPointsToday($date, $points, $dates) {
    $dates[$date]->setPointsReopenedToday($points);
    return $dates;
  }

  private function AddTaskInSprint($task_phid) {
    $this->task_in_sprint[$task_phid] = 1;
    return $this->task_in_sprint[$task_phid];
  }

  private function RemoveTaskInSprint($task_phid) {
    $this->task_in_sprint[$task_phid] = 0;
    return $this->task_in_sprint[$task_phid];
  }

  private function CloseTaskStatus($task_phid) {
    $this->task_statuses[$task_phid] = 'closed';
    return $this->task_statuses[$task_phid];
  }

  private function OpenTaskStatus($task_phid) {
    $this->task_statuses[$task_phid] = 'open';
    return $this->task_statuses[$task_phid];
  }

  private function changePoints($date, $task_phid, $points, $old_point_value, $dates) {

       // Adjust points for that day
      $this->task_points[$task_phid] = $points - $old_point_value;
      $dates[$date]->setPointsAddedToday($this->task_points[$task_phid]);

      // If the task is closed, adjust completed points as well
      if (isset($this->task_statuses[$task_phid]) && $this->task_statuses[$task_phid] == 'closed') {
        $this->task_points[$task_phid] = $points - $old_point_value;
        $dates[$date]->setPointsClosedToday($this->task_points[$task_phid]);
      }
    return $dates;
  }
}