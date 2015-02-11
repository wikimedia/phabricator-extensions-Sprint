<?php

final class TasksTableView {

  private $table_data;


  public function setTableData ($table_data) {
    $this->table_data = $table_data;
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
    $table = id(new SprintTableView($this->table_data->getRows()))
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
            false,
            true,
            false,
            true,
            true,
            false,
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
