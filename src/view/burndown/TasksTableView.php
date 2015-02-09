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

    $table = id(new AphrontTableView($this->table_data->getRows()))
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
            ));
    $table->makeSortable(
        $this->table_data->getRequest()->getRequestURI(),
        'order',
        $this->table_data->getOrder(),
        $this->table_data->getReverse(),
        array(
            'Task',
            'Epoch Created',
            'Date Created',
            'Epoch Updated',
            'Last Update',
            'Assigned to',
            'NumPriority',
            'Priority',
            'Points',
            'Status',
        ));
    $table->setColumnVisibility(
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
