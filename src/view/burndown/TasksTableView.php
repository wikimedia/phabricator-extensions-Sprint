<?php

final class TasksTableView {

  private $tableData;


  public function setTableData ($table_data) {
    $this->tableData = $table_data;
    return $this;
  }

  /**
   * Format the tasks data for display on the page.
   *
   * @returns PHUIObjectBoxView
   */
  public function buildTasksTable() {
    Javelin::initBehavior('tasks-table', array(
    ), 'sprint');
    $table = id(new SprintTableView($this->tableData->getRows()))
        ->setHeaders(
            array(
                pht('Task'),
                pht('Epoch Created'),
                pht('Date Created'),
                pht('Epoch Updated'),
                pht('Last Update'),
                pht('Assigned to'),
                pht('NumPriority'),
                pht('Priority'),
                pht('Points'),
                pht('Status'),
            ))
        ->setTableId('tasks-list')
        ->setClassName('display')
        ->setColumnVisibility(
        array(
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
        ));

    $box = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Tasks in this Sprint'))
        ->appendChild($table);

    return $box;
  }


}
