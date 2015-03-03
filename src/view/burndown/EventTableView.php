<?php

final class EventTableView {
  private $project;
  private $viewer;
  private $request;
  private $events;
  private $tasks;

  public function setProject ($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setEvents ($events) {
    $this->events = $events;
    return $this;
  }

  public function setTasks ($tasks) {
    $this->tasks = $tasks;
    return $this;
  }

  public function setRequest ($request) {
    $this->request =  $request;
    return $this;
  }

  public function buildEventTable($start, $end) {
    Javelin::initBehavior('events-table', array(
    ), 'sprint');
    $rows = $this->buildEventsTree($start, $end);
    $table = id(new SprintTableView($rows))
        ->setHeaders(
            array(
                pht('Stamp'),
                pht('When'),
                pht('Task'),
                pht('Action'),
            ))
        ->setColumnClasses(
            array(
                '',
                '',
                '',
                'wide',
            ))
        ->setTableId('events-list')
        ->setClassName('display')
       ->setColumnVisibility(
        array(
            true,
            true,
            true,
            true,
        ));
    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Events related to this sprint'))
        ->appendChild($table);

    return $box;
  }

  private function buildEventsTree ($start, $end) {

    $rows = array();
    foreach ($this->events as $event) {
      $xaction_date = $event['epoch'];
      if ($xaction_date > $start && $xaction_date < $end) {
        $task_phid = $event['objectPHID'];
        $task = $this->tasks[$task_phid];
        $rows[] = array(
            $event['epoch'],
            phabricator_datetime($event['epoch'], $this->viewer),
            phutil_tag(
                'a',
                array(
                    'href' => '/'.$task->getMonogram(),
                ),
                $task->getMonogram().': '.$task->getTitle()),
            $event['title'],
        );
      }
    }
    return $rows;
  }
}
