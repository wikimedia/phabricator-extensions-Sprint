<?php
final class ViewController {

  private $views = array();

  public function __construct()
  {
    $this->views = array(
        'user' =>
            new UserOpenTasksView(),
        'project' =>
            new ProjectOpenTasksView(),
    );
  }

  public function TaskView($view)
  {
    if ( ! isset($this->views[$view])) {
      throw new \LogicException('Unsupported view.');
    }

    return $this->views[$view]->execute();
  }
}