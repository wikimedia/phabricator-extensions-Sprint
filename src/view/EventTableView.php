<?php

final class EventTableView {
  /**
   * Format the Event data for display on the page.
   *
   * @returns PHUIObjectBoxView
   */
  public function buildEventTable($project, $viewer) {
    $query = id(new SprintQuery())
        ->setProject($project)
        ->setViewer($viewer);
    $aux_fields = $query->getAuxFields();
    $start = $query->getStartDate($aux_fields);
    $end = $query->getEndDate($aux_fields);

    $tasks = $query->getTasks();
    $query->checkNull($start, $end, $tasks);
    $xactions = $query->getXactions($tasks);
    $xactions = mpull($xactions, null, 'getPHID');
    $tasks = mpull($tasks, null, 'getPHID');
    $events = $query->getEvents($xactions, $tasks);
    $rows = array();
    foreach ($events as $event) {
      $task_phid = $xactions[$event['transactionPHID']]->getObjectPHID();
      $task = $tasks[$task_phid];

      $rows[] = array(
          phabricator_datetime($event['epoch'], $viewer),
          phutil_tag(
              'a',
              array(
                  'href' => '/' . $task->getMonogram(),
              ),
              $task->getMonogram() . ': ' . $task->getTitle()),
          $event['title'],
      );
    }

    $table = id(new AphrontTableView($rows))
        ->setHeaders(
            array(
                pht('When'),
                pht('Task'),
                pht('Action'),
            ))
        ->setColumnClasses(
            array(
                '',
                '',
                'wide',
            ));

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Events related to this sprint'))
        ->appendChild($table);

    return $box;
  }

}