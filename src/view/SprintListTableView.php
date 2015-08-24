<?php

final class SprintListTableView extends Phobject {

  private $tableData;

  public function setTableData ($table_data) {
    $this->tableData = $table_data;
    return $this;
  }

  public function buildProjectsTable () {
    $id = 'list-table';
    Javelin::initBehavior('sprint-table', array(
        'hardpoint' => $id,
    ), 'sprint');
    $projects_table = id(new SprintTableView($this->tableData->getRows()))
        ->setHeaders(
            array(
                'Sprint Name',
                'Burndown',
                'Epoch Start',
                'Start Date',
                'Epoch End',
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
