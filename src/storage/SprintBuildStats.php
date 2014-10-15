<?php

final class SprintBuildStats {

  private $tasks;

  public function buildDateArray($start, $end) {
    // Build an array of dates between start and end
    $period = new DatePeriod(
        id(new DateTime("@" . $start))->setTime(0, 0),
        new DateInterval('P1D'), // 1 day interval
        id(new DateTime("@" . $end))->modify('+1 day')->setTime(0, 0));

    $dates = array('before' => new BurndownDataDate('Start of Sprint'));
    foreach ($period as $day) {
      $dates[$day->format('D M j')] = new BurndownDataDate(
          $day->format('D M j'));
    }
    $dates['after'] = new BurndownDataDate('After end of Sprint');
    return $dates;
  }

  // Now that we have the data for each day, we need to loop over and sum
  // up the relevant columns
  public function sumSprintStats($dates) {
    $previous = null;
    foreach ($dates as $current) {
      $current->tasks_total = $current->tasks_added_today;
      $current->points_total = $current->points_added_today;
      $current->tasks_remaining = $current->tasks_added_today;
      $current->points_remaining = $current->points_added_today;
      if ($previous) {
        $current->tasks_total += $previous->tasks_total;
        $current->points_total += $previous->points_total;
        $current->tasks_remaining += $previous->tasks_remaining - $current->tasks_closed_today;
        $current->points_remaining += $previous->points_remaining - $current->points_closed_today;
      }
      $previous = $current;
    }
    return;
  }

  // Build arrays to store current point and closed status of tasks as we
  // progress through time, so that these changes reflect on the graph
  public function buildTaskArrays() {
    $this->task_points = array();
    $this->task_statuses = array();
    foreach ($this->tasks as $task) {
      $this->task_points[$task->getPHID()] = 0;
      $this->task_statuses[$task->getPHID()] = null;
      $this->task_in_sprint[$task->getPHID()] = 0;
    }
    return;
  }

  /**
   * Compute the values for the "Ideal Points" line.
   */

  // This is a cheap hacky way to get business days, and does not account for
  // holidays at all.
  public function computeIdealPoints($dates) {
    $total_business_days = 0;
    foreach ($dates as $key => $date) {
      if ($key == 'before' OR $key == 'after')
        continue;
      $day_of_week = id(new DateTime($date->getDate()))->format('w');
      if ($day_of_week != 0 AND $day_of_week != 6) {
        $total_business_days++;
      }
    }

    $elapsed_business_days = 0;
    foreach ($dates as $key => $date) {
      if ($key == 'before') {
        $date->points_ideal_remaining = $date->points_total;
        continue;
      } else if ($key == 'after') {
        $date->points_ideal_remaining = 0;
        continue;
      }

      $day_of_week = id(new DateTime($date->getDate()))->format('w');
      if ($day_of_week != 0 AND $day_of_week != 6) {
        $elapsed_business_days++;
      }

      $date->points_ideal_remaining = round($date->points_total *
          (1 - ($elapsed_business_days / $total_business_days)), 1);
    }
  }
}
