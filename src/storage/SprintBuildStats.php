<?php

final class SprintBuildStats {
  private $timezone;

  public function setTimezone ($viewer) {
    $this->timezone = new DateTimeZone($viewer->getTimezoneIdentifier());
    return $this->timezone;
  }

  public function setSprintData($dates, $before) {
    $dates = $this->sumSprintStats($dates, $before);
    $sprint_data = $this->computeIdealPoints($dates, $before);
    return $sprint_data;
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

  public function buildBefore($start, $timezone) {
    $before = id(new DateTime("@" . $start, $timezone))->modify('-1 day')->setTime(2, 0);
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

  // Now that we have the data for each day, we need to loop over and sum
  // up the relevant columns

  public function sumSprintStats($dates, $before) {
//    $this->sumTasksTotal($dates, $before);
    $this->sumPointsTotal($dates, $before);
    $this->calcTasksRemaining($dates, $before);
     $this->calcPointsRemaining($dates, $before);
    return $dates;
  }

  public function sumTasksTotal($dates, $before) {
    $first = true;
    $previous = new BurndownDataDate($date=null);
    $tasks_added_today = null;
    $tasks_reopened_today = null;
    $tasks_closed_today = null;

    $tasks_added_before = $before->getTasksAddedBefore();
    $tasks_reopened_before = $before->getTasksReopenedBefore();
    $tasks_closed_before = $before->getTasksClosedBefore();
    $tasks_before = $tasks_added_before + $tasks_reopened_before - $tasks_closed_before;

    foreach ($dates as $date) {
      $tasks_added_today += $date->getTasksAddedToday();
      $tasks_reopened_today += $date->getTasksReopenedToday();
      $tasks_closed_today += $date->getTasksClosedToday();
      $tasks_today = $tasks_added_today + $tasks_reopened_today - $tasks_closed_today;
      if ($first) {
        $start_tasks = $tasks_before + $tasks_today;
        $date->setTasksTotal($start_tasks);
      } else {
        $tasks_total = $previous->getTasksRemaining();
        $date->setTasksTotal($tasks_total);
      }
      $previous = $date;
      $first = false;
    }
    return $dates;
  }

  public function sumPointsTotal($dates, $before) {
    $first = true;
    $previous = new BurndownDataDate($date=null);
    $points_added_today = null;
    $points_reopened_today = null;
    $points_closed_today = null;

    $points_added_before = $before->getPointsAddedBefore();
    $points_reopened_before = $before->getPointsReopenedBefore();
    $points_closed_before = $before->getPointsClosedBefore();
    $points_before = $points_added_before + $points_reopened_before - $points_closed_before;

    foreach ($dates as $date) {
      $points_added_today += $date->getPointsAddedToday();
      $points_reopened_today += $date->getPointsReopenedToday();
      $points_closed_today += $date->getPointsClosedToday();
      $points_today = $points_added_today + $points_reopened_today - $points_closed_today;
      if ($first) {
        $start_points = $points_before + $points_today;
        $date->setPointsTotal($start_points);
      } else {
        $points_total = $previous->getPointsTotal();
        $date->setPointsTotal($points_total);
      }
      $previous = $date;
      $first = false;
    }
    return $dates;
  }

  public function calcPointsRemaining($dates, $before) {
    $first = true;
    $previous = new BurndownDataDate($date=null);
    $points_added_before = null;
    $points_closed_before = null;
    $points_reopened_before = null;
    $points_added_today = null;
    $points_closed_today = null;
    $points_reopened_today = null;
    $points_remaining = null;
    foreach ($dates as $date) {
      $points_added_today = $date->getPointsAddedToday();
      $points_closed_today = $date->getPointsClosedToday();
      $points_reopened_today = $date->getPointsReopenedToday();
      $points_today = $points_added_today + $points_reopened_today - $points_closed_today;
      if ($first) {
        $points_added_before = $before->getPointsAddedBefore();
        $points_reopened_before = $before->getPointsReopenedBefore();
        $points_closed_before = $before->getPointsClosedBefore();
        $points_before = $points_added_before + $points_reopened_before - $points_closed_before;
        $points_remaining = $points_today + $points_before;
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

  public function calcTasksRemaining($dates, $before) {
    $first = true;
    $previous = new BurndownDataDate($date=null);
    $tasks_added_before = null;
    $tasks_closed_before = null;
    $tasks_reopened_before = null;
    $tasks_added_today = null;
    $tasks_closed_today = null;
    $tasks_reopened_today = null;
    foreach ($dates as $date) {
      $tasks_added_today = $date->getTasksAddedToday();
      $tasks_closed_today = $date->getTasksClosedToday();
      $tasks_reopened_today = $date->getTasksReopenedToday();
      $tasks_today = $tasks_added_today + $tasks_reopened_today - $tasks_closed_today;
      if ($first) {
        $tasks_added_before = $before->getTasksAddedBefore();
        $tasks_reopened_before = $before->getTasksReopenedBefore();
        $tasks_closed_before = $before->getTasksClosedBefore();
        $tasks_before = $tasks_added_before + $tasks_reopened_before - $tasks_closed_before;
        $tasks_remaining = $tasks_today + $tasks_before;
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

  public function computeIdealPoints($dates, $before) {
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
