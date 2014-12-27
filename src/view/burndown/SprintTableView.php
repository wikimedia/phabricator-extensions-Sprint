<?php

final class SprintTableView
{

  /**
   * Format the Burndown data for display on the page.
   *
   * @returns PHUIObjectBoxView
   */
  public function buildSprintTable($sprint_data, $before)
  {
    $tdata = array();
    $pdata = array();
    $tasks_remaining = null;

    foreach ($sprint_data as $date) {
      $start_tasks = null;
      $tasks_before = $before->getTasksForwardfromBefore();
      $tasks_yesterday = $date->getYesterdayTasksRemaining();
      $today_tasks = $date->getTasksAddedToday();
      $tasks_remaining = $date->getTasksRemaining();

      $timestamp = strtotime($date->getDate());
      $now = time();

      if ($timestamp < $now) {
        if (!$tasks_before == 0) {
          $start_tasks = $before->getTasksForwardfromBefore();
        } elseif (!$tasks_yesterday == 0) {
          $start_tasks = $date->getYesterdayTasksRemaining();
        } elseif (!$today_tasks == 0) {
          $start_tasks = $today_tasks;
        } else {
          $start_tasks = $tasks_remaining;
        }
      }

      $tdata[] = array(
          $date->getDate(),
          $start_tasks,
          $tasks_remaining,
          $date->getTasksAddedToday(),
          $date->getTasksReopenedToday(),
          $date->getTasksClosedToday(),
      );

      $start_points = null;
      $before_points = $before->getPointsForwardfromBefore();
      $yesterday_points = $date->getYesterdayPointsRemaining();
      $today_points = $date->getPointsAddedToday();
      $points_remaining = $date->getPointsRemaining();

      if ($timestamp < $now) {
        if (!$before_points == 0) {
          $start_points = $before_points;
        } elseif (!$yesterday_points == 0) {
          $start_points = $yesterday_points;
        } elseif (!$today_points == 0) {
          $start_points = $today_points;
        } else {
          $start_points = $points_remaining;
        }
      }

      $pdata[] = array(
          $date->getDate(),
          $start_points,
          $date->getPointsRemaining(),
          $date->getPointsAddedToday(),
          $date->getPointsReopenedToday(),
          $date->getPointsClosedToday(),
      );
    }
    $ttable = id(new AphrontTableView($tdata))
        ->setHeaders(
            array(
                pht('Date'),
                pht('Starting Tasks'),
                pht('Remaining Tasks'),
                pht('Tasks Added'),
                pht('Tasks Reopened'),
                pht('Tasks Closed'),
            ));
    $ptable = id(new AphrontTableView($pdata))
        ->setHeaders(
            array(
                pht('Date'),
                pht('Starting Points'),
                pht('Remaining Points'),
                pht('Points Added'),
                pht('Points Reopened'),
                pht('Points Closed'),
            ));
    $taskdata = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Tasks'))
        ->appendChild($ttable);
    $pointsdata = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Points'))
        ->appendChild($ptable);
    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Data'))
        ->appendChild($taskdata)
        ->appendChild($pointsdata);
    return $box;
  }
}
