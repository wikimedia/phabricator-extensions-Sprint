<?php

final class SprintListDataProvider {
  private $viewer;
  private $request;
  private $sprint_phids;
  private $sprints;
  private $rows;

  public function setViewer($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest($request) {
    $this->request = $request;
    return $this;
  }

  public function getRequest() {
    return $this->request;
  }

  public function getRows() {
    return $this->rows;
  }

  public function execute() {
    $this->sprint_phids = id(new SprintQuery())
        ->getSprintPHIDs();
    $this->sprints = $this->loadAllSprints();
    $this->buildSprintListData();
    return $this;
  }

  private function loadAllSprints() {
    $sprints = id(new PhabricatorProjectQuery())
        ->setViewer($this->viewer)
        ->withPHIDS($this->sprint_phids)
        ->execute();
    return $sprints;
  }

  private function buildSprintListData() {
    $query = id(new SprintQuery())
        ->setViewer($this->viewer);

    $rows = array();
    foreach ($this->sprints as $project) {
      $query->setProject($project);
      $field_list = $query->getCustomFieldList();
      $aux_fields = $query->getAuxFields($field_list);
      $start = $query->getStartDate($aux_fields);
      $end = $query->getEndDate($aux_fields);
      $project_id = $project->getId();
      $project_name = $project->getName();

      $row = $this->buildRowSet($project_id, $project_name, $start, $end);
      $rows[] = $row;
    }

    $this->rows = array_map(function ($a) { return $a['0']; }, $rows);
    return $this;
  }

  private function buildRowSet($project_id, $project_name, $start, $end) {
    $rows = array();
    $rows[] =  array(
        phutil_tag(
        'a',
        array(
            'href' => '/project/sprint/profile/'.$project_id .'/',
            'style' => 'font-weight:bold',
        ),
            $project_name),
        phutil_tag(
            'a',
            array(
                'href'  => '/project/sprint/view/'.$project_id . '/',
             ),
            $this->getEditProjectDetailsIcon()),
        $start,
        phabricator_datetime($start, $this->viewer),
        $end,
        phabricator_datetime($end, $this->viewer),
    );

    return $rows;
  }

  private function getEditProjectDetailsIcon() {
    $image = id(new PHUIIconView())
        ->setIcon('fa-fire', 'orange')
        ->setText('Burndown');
    return $image;
  }
}
