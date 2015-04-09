<?php

final class SprintStats {
  private $timezone;
  private $taskpoints;

  public function setTimezone ($viewer) {
    $this->timezone = new DateTimeZone($viewer->getTimezoneIdentifier());
    return $this->timezone;
  }

  public function setSprintData($dates) {
    $dates = $this->sumSprintStats($dates);
    $dates = $this->computeIdealPoints($dates);
    return $dates;
  }

  public function setTaskPoints ($taskpoints) {
    $this->taskpoints = $taskpoints;
    return $this;
  }

  public function buildDateArray($start, $end, DateTimeZone $timezone) {
    $dates = array();
    $period = new DatePeriod(
        id(new DateTime('@'.$start, $timezone))->setTime(2, 0),
        new DateInterval('P1D'), // 1 day interval
        id(new DateTime('@'.$end, $timezone))->modify('+1 day')->setTime(17, 0));

    foreach ($period as $day) {
      $dates[$day->format('D M j')] = $this->getBurndownDate(
          $day->format('D M j'));
    }

    return $dates;
  }

  public function buildBefore($start, $timezone) {
    $before = id(new DateTime('@'.$start, $timezone))->modify('-1 day')->setTime(2, 0);
    return $this->getBurndownDate($before->format('D M j'));
  }

  public function buildTimeSeries($start, $end) {
    $timezone = $this->timezone;
    $timeseries = array_keys($this->buildDateArray ($start, $end, $timezone));
    return $timeseries;
  }

  /**
   * @param string $date
   */
  public function getBurndownDate ($date) {
    $sprint_date = id(new BurndownDataDate($date));
    return $sprint_date;
  }

  public function sumSprintStats($dates) {
    $this->sumTotalPoints($dates);
    $this->calcTasksRemaining($dates);
    $this->calcPointsRemaining($dates);
    return $dates;
  }

  public function sumTotalPoints($dates) {
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->taskpoints);
    $points_total = $sprintpoints->sumTotalTaskPoints();
    foreach ($dates as $date) {
      $date->setPointsTotal($points_total);
    }
    return $dates;
  }

  public function sumTotalTasks($dates) {
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->taskpoints);
    $points_total = $sprintpoints->sumTotalTasks();
    foreach ($dates as $date) {
      $date->setTasksTotal($points_total);
    }
    return $dates;
  }

  public function calcPointsRemaining($dates) {
    $first = true;
    $previous = new BurndownDataDate($date = null);
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->taskpoints);
    $points_total = $sprintpoints->sumTotalTaskPoints();

    foreach ($dates as $date) {
      $points_closed_today = $date->getPointsClosedToday();
      $points_reopened_today = $date->getPointsReopenedToday();
      $points_today = $points_reopened_today - $points_closed_today;
      if ($first) {
         $points_remaining = $points_today + $points_total;
      } else {
        $yesterday_points_remaining = $previous->getPointsRemaining();
        $date->setYesterdayPointsRemaining($yesterday_points_remaining);
        $points_remaining = $points_today + $yesterday_points_remaining;
      }
      if ($points_remaining < 0) {
        $points_remaining = 0;
      }
      $date->setPointsRemaining($points_remaining);
      $previous = $date;
      $first = false;
    }
    return $dates;
  }

  public function calcTasksRemaining($dates) {
    $first = true;
    $previous = new BurndownDataDate($date = null);
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->taskpoints);
    $tasks_total = $sprintpoints->sumTotalTasks();
    foreach ($dates as $date) {
      $tasks_closed_today = $date->getTasksClosedToday();
      $tasks_reopened_today = $date->getTasksReopenedToday();
      $tasks_today = $tasks_reopened_today - $tasks_closed_today;
      if ($first) {
        $tasks_remaining = $tasks_today + $tasks_total;
      } else {
        $yesterday_tasks_remaining = $previous->getTasksRemaining();
        $date->setYesterdayTasksRemaining($yesterday_tasks_remaining);
        $tasks_remaining = $tasks_today + $yesterday_tasks_remaining;
      }
      $date->setTasksRemaining($tasks_remaining);
      $previous = $date;
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
        pht('Start Points'),
        pht('Remaining Points'),
        pht('Ideal Points'),
        pht('Points Closed Today'),
    ),);

    foreach ($dates as $key => $date) {
      $data[] = array(
          $date->getPointsTotal(),
          $date->getPointsRemaining(),
          $date->getPointsIdealRemaining(),
          $date->getPointsClosedToday(),
      );

    }
    return $data;
  }

  public function transposeArray($array) {
    $transposed_array = array();
    if ($array) {
      foreach ($array as $row_key => $row) {
        if (is_array($row) && !empty($row)) {
          foreach ($row as $column_key => $element) {
            $transposed_array[$column_key][$row_key] = $element;
          }
        } else {
          $transposed_array[0][$row_key] = $row;
        }
      }
    }
    return $transposed_array;
  }
}
