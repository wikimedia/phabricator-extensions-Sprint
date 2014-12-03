<?php

final class SprintBuildStats {
  private $timezone;
  private $total_tasks_added;
  private $tasks_remaining;
  private $total_points_added;
  private $points_remaining;

  public function setTimezone ($viewer) {
    $this->timezone = new DateTimeZone($viewer->getTimezoneIdentifier());
    return $this->timezone;
  }

  public function buildDateArray($start, $end, DateTimeZone $timezone) {

    $period = new DatePeriod(
        id(new DateTime("@" . $start, $timezone))->setTime(2, 0),
        new DateInterval('P1D'), // 1 day interval
        id(new DateTime("@" . $end, $timezone))->modify('+1 day')->setTime(17, 0));

    foreach ($period as $day) {
      $dates[$day->format('D M j')] = $this->getBurndownDate(
          $day->format('D M j'));
    }

    return $dates;
  }

  public function buildTimeSeries($start, $end) {
    $timezone = $this->timezone;
    $timeseries = array_keys($this->buildDateArray ($start, $end, $timezone));
    return $timeseries;
  }

  public function getBurndownDate ($date) {
    $sprint_date = id(new BurndownDataDate($date));
    return $sprint_date;
  }

  // Now that we have the data for each day, we need to loop over and sum
  // up the relevant columns
  public function sumSprintStats($dates) {
     $this->sumTasksTotal($dates);
     $this->calcTasksRemaining($dates);
     $this->sumPointsTotal($dates);
     $this->calcPointsRemaining($dates);
    return $dates;

  }

  public function sumTasksTotal($dates) {
    $first = true;
    $previous = new BurndownDataDate($date=null);
    foreach ($dates as $date) {
      $this->total_tasks_added += $date->getTasksAddedToday();
      if ($first) {
        $date->setTasksTotal($this->total_tasks_added);
        $tasks_total = $this->total_tasks_added;
      } else {
        $tasks_total = $date->sumTasksTotal($date, $previous);
        $date->setTasksTotal($tasks_total);
      }
      $this->total_tasks_added += $tasks_total;
      $previous = $date;
      $first = false;
    }
    return $dates;
  }

  public function sumPointsTotal($dates) {
    $first = true;
    $previous = new BurndownDataDate($date=null);
    foreach ($dates as $date) {
      $this->total_points_added += $date->getPointsAddedToday();
      if ($first) {
        $date->setPointsTotal($this->total_points_added);
        $points_total = $this->total_points_added;
      } else {
        $points_total = $date->sumPointsTotal($date, $previous);
        $date->setPointsTotal($points_total);
      }
      $this->total_points_added += $points_total;
      $previous = $date;
      $first = false;
    }
    return $dates;
  }

  public function calcPointsRemaining($dates) {
    $first = true;
    foreach ($dates as $date) {
      $points_added = $date->getPointsAddedToday();
      $points_closed = $date->getPointsClosedToday();
      $points_reopened = $date->getPointsReopenedToday();
      $points_removed = $date->getPointsRemovedToday();

      if ($first) {
        $points_total = $points_added + $points_reopened - $points_removed - $points_closed;
        $net_change =0;
      } else {
        $points_total = $this->points_remaining;
        $net_change = $points_added + $points_reopened - $points_removed - $points_closed;
      }

      $points_diff = $net_change;
      $points_remaining = $points_total + $points_diff;
      if ($points_remaining < 0) {
        $points_remaining = 0;
      }
      $date->setPointsRemaining($points_remaining);
      $this->points_remaining = $points_remaining;

      $first = false;
    }
    return $dates;
  }

  public function calcTasksRemaining($dates) {
    $first = true;
    foreach ($dates as $date) {
      $tasks_added = $date->getTasksAddedToday();
      $tasks_closed = $date->getTasksClosedToday();
      $tasks_reopened = $date->getTasksReopenedToday();
      $tasks_removed = $date->getTasksRemovedToday();
      if ($first) {
        $tasks_total = $tasks_added + $tasks_reopened - $tasks_removed - $tasks_closed;
        $net_change = 0;
      } else {
        $tasks_total = $this->tasks_remaining;
        $net_change = $tasks_added + $tasks_reopened - $tasks_removed - $tasks_closed;
      }
      $tasks_diff = $net_change;
      $tasks_remaining = $tasks_total + $tasks_diff;
      $date->setTasksRemaining($tasks_remaining);
      $this->tasks_remaining = $tasks_remaining;
      $first = false;
    }
    return $dates;
  }

  public function computeIdealPoints($dates) {
    $total_business_days = 0;
    foreach ($dates as $key => $date) {
      $day_of_week = id(new DateTime($date->getDate()))->format('w');
      if ($day_of_week != 0 AND $day_of_week != 6) {
        $total_business_days++;
      }
    }

    $elapsed_business_days = 0;
    foreach ($dates as $key => $date) {
      $date->setPointsIdealRemaining($date->getPointsTotal());

      $day_of_week = id(new DateTime($date->getDate()))->format('w');
      if ($day_of_week != 0 AND $day_of_week != 6) {
        $elapsed_business_days++;
      }

      $date->setPointsIdealRemaining (round($date->getPointsTotal() *
          (1 - ($elapsed_business_days / $total_business_days)), 1));
    }
    return $dates;
  }

  public function buildDataSet ($dates) {
    $data = array(array(
        pht('Total Points'),
        pht('Remaining Points'),
        pht('Ideal Points'),
        pht('Points Closed Today'),
    ));

    $future = false;
    foreach ($dates as $key => $date) {
        $now = id(new DateTime('now', $this->timezone));
        $future = new DateTime($date->getDate(), $this->timezone) > $now;

      $data[] = array(
          $future ? null : $date->getPointsTotal(),
          $future ? null : $date->getPointsRemaining(),
          $date->getPointsIdealRemaining(),
          $future ? null : $date->getPointsClosedToday(),
      );

    }
    return $data;
  }

  public function setTaskOpenStatusSum ($task_open_status_sum, $points) {
    $task_open_status_sum += $points;
    return $task_open_status_sum;
  }

  public function setTaskClosedStatusSum ($task_closed_status_sum, $points) {
    $task_closed_status_sum += $points;
    return $task_closed_status_sum;
  }
}
