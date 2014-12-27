<?php

final class HistoryTableView {

  public function buildHistoryTable($before) {
    $bdata = array();
    $bdata[] = array(
            $before->getTasksAddedBefore(),
            $before->getTasksReopenedBefore(),
            $before->getTasksClosedBefore(),
            $before->getPointsAddedBefore(),
            $before->getPointsForwardfromBefore(),
        );

    $btable = id(new AphrontTableView($bdata))
        ->setHeaders(
            array(
                pht('Tasks Added '),
                pht('Tasks Reopened'),
                pht('Tasks Closed'),
                pht('Points Added'),
                pht('Points Forwarded'),
            ));
    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Before Sprint'))
        ->appendChild($btable);
    return $box;
  }
}
