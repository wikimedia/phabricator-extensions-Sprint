<?php

final class BoardDataPieView {

  private $project;
  private $board_data;

  public function setBoardData ($board_data) {
    $this->board_data = $board_data;
    return $this;
  }

  public function setProject($project) {
    $this->project = $project;
    return $this;
  }

  public function buildBoardDataPie() {
    require_celerity_resource('d3', 'sprint');
    require_celerity_resource('c3-css', 'sprint');
    require_celerity_resource('c3', 'sprint');

    $coldata = $this->board_data->buildBoardDataSet();
    $done_points = '0';
    $backlog_points = '0';
    $doing_points = '0';
    $review_points = '0';

    foreach ($coldata as $col) {
      switch ($col[0]) {
        case ('Done'):
          $done_points = $col[2];
          break;
        case ('Backlog'):
          $backlog_points = $col[2];
          break;
        case ('Doing'):
          $doing_points = $col[2];
          break;
        case ('Review'):
          $review_points = $col[2];
          break;
        default:
          break;
      }
    }

    $id = 'c3-board-data-pie';
    Javelin::initBehavior('c3-board-data-pie', array(
        'hardpoint' => $id,
        'Backlog' => $backlog_points,
        'Doing' => $doing_points,
        'Review' => $review_points,
        'Done' => $done_points,
    ), 'sprint');


    $boardpie = phutil_tag('div',
            array(
                'id' => 'c3-board-data-pie',
                'style' => 'width: 400px; height:200px; padding-right: 200px;
                float: left;',
            ), pht('Board'));
    $taskpie = phutil_tag('div',
            array(
                'id' => 'pie',
                'style' => 'width: 300px; height:200px; margin-left: 600px;',
            ), pht('Task Status'));
    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Progress Report for '.
            $this->project->getName()))
        ->appendChild($boardpie)
        ->appendChild($taskpie);
    return $box;
  }
}
