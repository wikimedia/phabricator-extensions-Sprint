<?php

final class BoardDataView {

  private $board_data;

  public function setBoardData ($board_data) {
    $this->board_data = $board_data;
    return $this;
  }

  public function buildBoardDataTable() {
    $coldata = $this->board_data->buildBoardDataSet();
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
