<?php

final class BoardDataView {

  private $board_data;

  public function setBoardData ($board_data) {
    $this->board_data = $board_data;
    return $this;
  }

  public function buildBoardDataTable() {
    $coldata = array();
    $board_columns = $this->board_data->buildBoardDataSet();
    foreach ($board_columns as $column_phid => $tasks) {
      $colname = $this->board_data->getColumnName($column_phid);
      $task_count = count($tasks);
      $task_points_total = $this->board_data->getTaskPointsSum($tasks);
      $coldata[] = array(
          $colname, $task_count, $task_points_total,
        );
    }
    $table = id(new AphrontTableView($coldata))
        ->setHeaders(
            array(
                pht('Column Name'),
                pht('Number of Tasks'),
                pht('Total Points'),
            ));

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Board Column List'))
        ->appendChild($table);
    return $box;
  }
}
