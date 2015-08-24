<?php

final class SprintHistoryTableView extends SprintView {

  private $request;
  private $tableData;
  protected $viewer;

  public function setViewer (PhabricatorUser $viewer) {
    $this->user = $viewer;
    return $this;
  }

  public function setRequest ($request) {
    $this->request = $request;
    return $this;
  }

  public function render() {
    require_celerity_resource('sprint-report-css', 'sprint');
    $filter = $this->BuildFilter($this->request);
    if ($this->request->getStr('project')) {
      $table = $this->buildProjectsTable();
    } else {
      $table = null;
    }

    return array($filter, $table);
  }

  public function setTableData ($table_data) {
    $this->tableData = $table_data;
    return $this;
  }

  public function buildProjectsTable () {
    if ($this->tableData) {
      $id = 'history-table';
      Javelin::initBehavior('sprint-history-table', array(
          'hardpoint' => $id,
      ), 'sprint');
        $projects_table = id(new SprintTableView($this->tableData->getRows()))
            ->setHeaders(
                array(
                    'projectremoved',
                    'projectadded',
                    'projName',
                    'taskName',
                    'createdEpoch',
                    'created',
                ))
            ->setTableId('sprint-history')
            ->setClassName('display');

        $projects_table = id(new PHUIBoxView())
            ->appendChild($projects_table)
            ->addMargin(PHUI::MARGIN_LARGE);

        return $projects_table;
    } else {
      return
          phutil_tag(
              'p',
              array(), pht('No data available.'));
    }
  }

}
