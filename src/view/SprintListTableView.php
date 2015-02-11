<?php

final class SprintListTableView {

  private $table_data;

  public function setTableData ($table_data) {
    $this->table_data = $table_data;
    return $this;
  }

  public function buildProjectsTable () {
    $id = 'list-table';
    Javelin::initBehavior('sprint-table', array(
        'hardpoint' => $id,
    ), 'sprint');
    $projects_table = id(new SprintTableView($this->table_data->getRows()))
        ->setHeaders(
            array(
                'Sprint Name',
                'Burndown',
                'Start Date',
                'End Date',
            ))
        ->setTableId('sprint-list')
        ->setClassName('display');

    $projects_table = id(new PHUIBoxView())
        ->appendChild($projects_table)
        ->addMargin(PHUI::MARGIN_LARGE);

    return $projects_table;
  }

}
