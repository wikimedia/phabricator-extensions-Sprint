<?php

final class SprintBuildStats {
  private $timezone;

  public function setTimezone ($viewer) {
    $this->timezone = new DateTimeZone($viewer->getTimezoneIdentifier());
    return $this->timezone;
  }

  public function buildDateArray($start, $end, $timezone) {

    $period = new DatePeriod(
        id(new DateTime("@" . $start))->setTime(2, 0)->setTimezone($timezone),
        new DateInterval('P1D'), // 1 day interval
        id(new DateTime("@" . $end))->modify('+1 day')->setTime(17, 0)->setTimezone($timezone));


    $dates = array('before' =>$this->getBurndownDate('Before Sprint'));

    foreach ($period as $day) {
      $dates[$day->format('D M j')] = $this->getBurndownDate(
          $day->format('D M j'));
    }
    $dates['after'] = $this->getBurndownDate('After Sprint');
    return $dates;
  }

  public function buildTimeSeries($start, $end) {
    $timeseries = array_keys($this->buildDateArray ($start, $end, $this->timezone));
    return $timeseries;
  }

  public function getBurndownDate ($date) {
    $sprint_date = id(new BurndownDataDate($date));
    return $sprint_date;
  }

  // Now that we have the data for each day, we need to loop over and sum
  // up the relevant columns
  public function sumSprintStats($dates) {
    $previous = null;
    foreach ($dates as $current) {
      $current->setTasksTotal($current->getTasksAddedToday());
      $current->setPointsTotal($current->getPointsAddedToday());
      $current->setTasksRemaining($current->getTasksAddedToday()-$current->getTasksClosedToday());
      $current->setPointsRemaining($current->getPointsAddedToday()-$current->getPointsClosedToday());
      if ($previous) {
        $current->sumTasksTotal($current, $previous);
        $current->sumPointsTotal($current, $previous);
        $current->sumTasksRemaining($current, $previous);
        $current->sumPointsRemaining ($current, $previous);
      }
      $previous = $current;
    }
    return $dates;
  }

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
        $date->setPointsIdealRemaining($date->getPointsTotal());
        continue;
      } else if ($key == 'after') {
        $date->setPointsIdealRemaining (null);
        continue;
      }

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
        pht('Points Today'),
    ));

    $future = false;
    foreach ($dates as $key => $date) {
      if ($key != 'before' AND $key != 'after') {
        $now = id(new DateTime('now', $this->timezone));
        $future = new DateTime($date->getDate(), $this->timezone) > $now;
      }
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
