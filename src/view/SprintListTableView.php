<?php

final class SprintListTableView {

  private $table_data;

  public function setTableData ($table_data) {
    $this->table_data = $table_data;
    return $this;
  }

  public function buildProjectsTable () {
    $projects_table = id(new AphrontTableView($this->table_data->getRows()))
        ->setHeaders(
            array(
                'Sprint Name',
                'Burndown',
                'Start Date',
                'End Date',
            ))
        ->setColumnClasses(
            array(
                'left',
                'left',
                'left',
                'left',
            ))
        ->makeSortable(
            $this->table_data->getRequest()->getRequestURI(),
            'order',
            $this->table_data->getOrder(),
            $this->table_data->getReverse(),
            array(
                'Name',
                '',
                'Start',
                'End',
            ));

    $projects_table = id(new PHUIBoxView())
        ->appendChild($projects_table)
        ->addMargin(PHUI::MARGIN_LARGE);

    return $projects_table;
  }

}
