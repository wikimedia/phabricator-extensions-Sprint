<?php

final class BoardDataTableView {

  private $boardData;

  public function setBoardData ($board_data) {
    $this->boardData = $board_data;
    return $this;
  }

  public function buildBoardDataTable() {
    $coldata = $this->boardData->getColumnData();
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
