<?php

final class C3PieView {

  private $project;
  private $taskpoints;
  private $tasks;

  public function setProject($project) {
    $this->project = $project;
    return $this;
  }

  public function setTasks ($tasks) {
    $this->tasks = $tasks;
    return $this;
  }

  public function setTaskPoints ($taskpoints) {
    $this->taskpoints = $taskpoints;
    return $this;
  }

  public function buildC3Pie() {
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->taskpoints)
        ->setTasks($this->tasks);

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

    $pie = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Task Status Report for '.
            $this->project->getName()))
        ->appendChild(phutil_tag('div',
            array(
                'id' => 'pie',
                'style' => 'width: 100%; height:200px',
            ), ''));

    return $pie;
  }

}
