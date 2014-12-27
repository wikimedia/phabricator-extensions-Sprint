<?php

final class EventTableView {
  private $project;
  private $viewer;
  private $request;

  public function setProject ($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest ($request) {
    $this->request =  $request;
    return $this;
  }

  public function buildEventTable($events, $xactions, $tasks, $start, $end) {
    $order = $this->request->getStr('ord', 'name');
    list($order, $reverse) = AphrontTableView::parseSort($order);
    $rows = $this->buildEventsTree($events, $xactions, $tasks,  $start, $end,
        $order, $reverse);
    $table = id(new AphrontTableView($rows))
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
            ));
    $table->setColumnVisibility(
        array(
            false,
            true,
            true,
            true,
        ));
    $table->makeSortable(
        $this->request->getRequestURI(),
        'ord',
        $order,
        $reverse,
        array(
            'When',
            'Date',
            'Task',
            'Action',
        )
    );

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Events related to this sprint'))
        ->appendChild($table);

    return $box;
  }

  private function buildEventsTree ($events, $xactions, $tasks,  $start, $end,
                                    $order, $reverse) {

    $rows = array();
    foreach ($events as $event) {
      $xaction = $xactions[$event['transactionPHID']];
      $xaction_date = $xaction->getDateCreated();
      if ($xaction_date > $start && $xaction_date < $end) {
        $task_phid = $xaction->getObjectPHID();
        $task = $tasks[$task_phid];
        $rows[] = array(
            $event['epoch'],
            phabricator_datetime($event['epoch'], $this->viewer),
            phutil_tag(
                'a',
                array(
                    'href' => '/' . $task->getMonogram(),
                ),
                $task->getMonogram() . ': ' . $task->getTitle()),
            $event['title'],
        );
//        $rows = $this->buildTableRow($event, $task);
 //       list ($stamp, $when, $task, $action) = $row[0];
 //       $row['sort'] = $this->setSortOrder($row, $order, $stamp, $when, $task, $action);
 //       $rows[] = $row;

//        $rows = isort($rows, 'sort');

 //       foreach ($rows as $k => $row) {
 //         unset($rows[$k]['sort']);
 //       }

 //       if ($reverse) {
   //       $rows = array_reverse($rows);
  //      }
  //      $rows = array_map(function ($a) {
  //        return $a['0'];
  //      }, $rows);
      }
    }
    return $rows;
  }

  private function buildTableRow($event, $task) {
    $row[] = array(
        $event['epoch'],
        phabricator_datetime($event['epoch'], $this->viewer),
        phutil_tag(
            'a',
            array(
                'href' => '/' . $task->getMonogram(),
            ),
            $task->getMonogram() . ': ' . $task->getTitle()),
        $event['title'],
    );
    return $row;
  }

  private function setSortOrder ($row, $order, $stamp, $when, $task, $action) {
    switch ($order) {
      case 'Date':
      default:
        $row['sort'] = -$stamp;
        break;
      case 'When':
        $row['sort'] = $when;
        break;
      case 'Task':
        $row['sort'] = $task;
        break;
      case 'Action':
        $row['sort'] = $action;
        break;
    }
    return $row['sort'];
  }



}