<?php

final class SprintTableView
{

  /**
   * Format the Burndown data for display on the page.
   *
   * @returns PHUIObjectBoxView
   */
  public function buildBurnDownTable($sprint_data)
  {
    $data = array();

    foreach ($sprint_data as $date) {
      $data[] = array(
          $date->getDate(),
          $date->getTasksTotal(),
          $date->getTasksRemaining(),
          $date->getPointsTotal(),
          $date->getPointsRemaining(),
          $date->getPointsIdealRemaining(),
          $date->getPointsClosedToday(),
      );
    }

    $table = id(new AphrontTableView($data))
        ->setHeaders(
            array(
                pht('Date'),
                pht('Total Tasks'),
                pht('Remaining Tasks'),
                pht('Total Points'),
                pht('Remaining Points'),
                pht('Ideal Remaining Points'),
                pht('Points Completed Today'),
            ));

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('DATA'))
        ->appendChild($table);

    return $box;
  }
}
