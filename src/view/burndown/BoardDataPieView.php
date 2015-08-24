<?php

final class BoardDataPieView extends Phobject{

  private $boardData;

  public function setBoardData ($board_data) {
    $this->boardData = $board_data;
    return $this;
  }

  public function buildPieBox() {
    $this->initBoardDataPie();
    $this->initTaskStatusPie();
    $this->initTaskPriorityPie();
    $project_name = $this->boardData->getProject()->getName();
    $boardpie = phutil_tag('div',
        array(
            'id' => 'c3-board-data-pie',
            'style' => 'width: 225px; height:200px; padding-left: 30px;
                float: left;',
        ), pht('Board'));
    $taskpie = phutil_tag('div',
        array(
            'id' => 'pie',
            'style' => 'width: 225px; height:200px; padding-left: 170px;
            float: left;',
        ), pht('Task Status'));
    $priority_pie = phutil_tag('div',
        array(
            'id' => 'priority-pie',
            'style' => 'width: 500px; height:200px; padding-left: 75px;
            margin-left: 750px;',
        ), pht('Task Priority'));

    $box = id(new SprintUIObjectBoxView())
        ->setHeaderText(pht('Points Allocation Report for '.
            $project_name))
        ->setColor('green')
        ->appendChild($boardpie)
        ->appendChild($taskpie)
        ->appendChild($priority_pie);

    return $box;
  }

  private function initBoardDataPie() {
    require_celerity_resource('d3', 'sprint');
    require_celerity_resource('c3-css', 'sprint');
    require_celerity_resource('c3', 'sprint');

    $coldata = $this->boardData->getColumnData();
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
  }

  private function initTaskStatusPie() {
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->boardData->getTaskPoints())
        ->setTasks($this->boardData->getTasks());

    list($task_open_status_sum, $task_closed_status_sum) = $sprintpoints
        ->getStatusSums();

    require_celerity_resource('d3', 'sprint');
    require_celerity_resource('c3-css', 'sprint');
    require_celerity_resource('c3', 'sprint');

    $id = 'pie';
    Javelin::initBehavior('c3-pie', array(
        'hardpoint' => $id,
        'open' => $task_open_status_sum,
        'resolved' => $task_closed_status_sum,
    ), 'sprint');
  }

  private function initTaskPriorityPie() {
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->boardData->getTaskPoints())
        ->setTasks($this->boardData->getTasks());

    $task_priority_sum = $sprintpoints
        ->getPrioritySums();

    if (isset($task_priority_sum['Wishlist'])) {
      $lowest_priority = $task_priority_sum['Wishlist'];
    } else if (isset($task_priority_sum['Needs Volunteer'])) {
      $lowest_priority = $task_priority_sum['Needs Volunteer'];
    } else {
      $lowest_priority = null;
    }

    require_celerity_resource('d3', 'sprint');
    require_celerity_resource('c3-css', 'sprint');
    require_celerity_resource('c3', 'sprint');

    $id = 'priority-pie';
    Javelin::initBehavior('priority-pie', array(
        'hardpoint' => $id,
        'Wishlist' => $lowest_priority,
        'Normal' => (isset($task_priority_sum['Normal'])) ?
            $task_priority_sum['Normal']: null,
        'High' => (isset($task_priority_sum['High'])) ?
            $task_priority_sum['High']: null,
        'Unbreak' => (isset($task_priority_sum['Unbreak Now!'])) ?
            $task_priority_sum['Unbreak Now!']: null,
        'Triage' => (isset($task_priority_sum['Needs Triage'])) ?
            $task_priority_sum['Needs Triage']: null,
        'Low' => (isset($task_priority_sum['Low'])) ?
            $task_priority_sum['Low']: null,
    ), 'sprint');
  }
}
