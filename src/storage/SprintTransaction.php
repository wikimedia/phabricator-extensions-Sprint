<?php

final class SprintTransaction  {

  private $viewer;
  private $task_points = array();
  private $tasks;
  private $task_created;
  private $task_statuses = array();
  private $task_in_sprint = array();
  private $taskpoints;

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
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

  public function parseEvents($events, $before, $start, $end, $dates, $xactions) {

    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->taskpoints);

    foreach ($events as $event) {
      $date = null;
      $xaction = $xactions[$event['transactionPHID']];
      $xaction_date = $event['epoch'];
      $task_phid = $event['objectPHID'];

      $points = $sprintpoints->getTaskPoints($task_phid);

      $date = phabricator_format_local_time($xaction_date, $this->viewer, 'D M j');

      if ($xaction_date < $start) {
          switch ($event['type']) {
            case "task-add":
              $this->AddTasksBefore($before, $dates);
              break;
            case "close":
              // A task was closed, mark it as done
              $this->CloseTasksBefore($before, $dates);
 //             $this->ClosePointsBefore($before, $points, $dates);
              break;
            case "reopen":
              // A task was reopened, subtract from done
               $this->ReopenedTasksBefore($before, $dates);
 //              $this->ReopenedPointsBefore($before, $points, $dates);
              break;
            case "points":
              $old_point_value = $xaction->getOldValue();
              $new_point_value = $xaction->getNewValue();
              // Points were changed
                 $this->ChangePointsBefore($before, $new_point_value, $old_point_value);
                 break;
          }
        }

        if ($xaction_date > $end) {
          continue;
        }

        if ($xaction_date > $start && $xaction_date < $end) {

          switch ($event['type']) {
            case "create":
 //            $this->AddTaskCreated($task_phid);
              break;
            case "task-add":
              $this->AddTasksToday($date, $dates);
              $this->AddTaskInSprint($task_phid);
              break;
            case "task-remove":
              break;
            case "close":
              // A task was closed, mark it as done
              $this->CloseTasksToday($date, $dates);
              $this->ClosePointsToday($date, $points, $dates);
              break;
            case "reopen":
              // A task was reopened, subtract from done
              $this->ReopenedTasksToday($date, $dates);
              $this->ReopenedPointsToday($date, $points, $dates);
              break;
            case "points":
              $old_point_value = $xaction->getOldValue();
              $new_point_value = $xaction->getNewValue();
              $this->changePoints($date, $task_phid, $new_point_value, $old_point_value, $dates);
              break;
          }
        }
      }
  return $dates;
}

  public function buildStatArrays() {
    foreach ($this->tasks as $task) {
      $this->task_points[$task->getPHID()] = 0;
      $this->task_statuses[$task->getPHID()] = null;
      $this->task_in_sprint[$task->getPHID()] = 0;
    }
    return;
  }

  private function AddTasksBefore($before, $dates) {
    $before->setTasksAddedBefore();
    return $dates;
  }

  private function CloseTasksBefore($before, $dates) {
    $before->setTasksClosedBefore();
    return $dates;
  }

  private function ReopenedTasksBefore($before, $dates) {
    $before->setTasksReopenedBefore();
    return $dates;
  }

  private function AddTasksToday($date, $dates) {
    $dates[$date]->setTasksAddedToday();
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

  private function ChangePointsBefore($before, $new_point_value,  $old_point_value) {
    $points = $new_point_value - $old_point_value;
    $before->setPointsAddedBefore($points);
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

  private function changePoints($date, $task_phid, $new_point_value, $old_point_value, $dates) {

    // Adjust points for that day
    $this->task_points[$task_phid] = $new_point_value - $old_point_value;
    $dates[$date]->setPointsAddedToday($this->task_points[$task_phid]);
    return $dates;
  }

  private function AddTaskCreated($task_phid) {
    $this->task_created = $task_phid;
  }

  function getxActionValue($mapping, $keys) {
    $output_arr = array();
    foreach($keys as $key) {
      $output_arr[] = $mapping[$key];
    }
    return $output_arr;
  }

  private function transLog($event) {
    list ($phid, $epoch, $key, $type, $title) = $this->getxActionValue($event, array('transactionPHID', 'epoch', 'key', 'type', 'title'));
    $tmp = "/tmp/xaction.log";
    $format = "%a %b %c %d %e";
    $log = new PhutilDeferredLog($tmp, $format);
    $log->setData(
          array(
              'a' =>  $phid,
              'b' =>  $epoch,
              'c' =>  $key,
              'd' =>  $type,
              'e' =>  $title,
          ));

    unset($log);
  }
}