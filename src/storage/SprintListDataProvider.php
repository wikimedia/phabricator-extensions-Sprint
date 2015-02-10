<?php

final class SprintListDataProvider {
  private $viewer;
  private $request;
  private $sprint_phids;
  private $sprints;
  private $order;
  private $reverse;
  private $rows;

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest ($request) {
    $this->request = $request;
    return $this;
  }

  public function getRequest () {
    return $this->request;
  }

  public function getOrder () {
    return $this->order;
  }

  public function getReverse () {
    return $this->reverse;
  }

  public function getRows () {
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

  private function setSortOrder($row, $project_name, $order, $start, $end) {
    switch ($order) {
      case 'Name':
        $row['sort'] = $project_name;
        break;
      case 'Burndown':
        $row['sort'] = null;
        break;
      case 'Start':
        $row['sort'] = $start;
        break;
      case 'End':
        $row['sort'] = $end;
        break;
      case 'name':
      default:
        $row['sort'] = -$start;
        break;
    }
    return $row['sort'];
  }

  private function buildSprintListData() {
    $order = $this->request->getStr('order', 'name');
    list($this->order, $this->reverse) = AphrontTableView::parseSort($order);
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
      list ($project_id, $project_name, $start, $end) = $row[0];
      $row['sort'] = $this->setSortOrder($row, $order, $project_id,
          $project_name, $start, $end);
      $rows[] = $row;
    }

    $rows = isort($rows, 'sort');
    foreach ($rows as $k => $row) {
      unset($rows[$k]['sort']);
    }
    if ($this->reverse) {
      $rows = array_reverse($rows);
    }
    $this->rows = array_map(function($a) { return $a['0']; }, $rows);
    return $this;
  }

  private function buildRowSet($project_id, $project_name, $start, $end) {
    $rows = array();
    $rows[] =  array (
        phutil_tag(
        'a',
        array(
            'href' => '/project/sprint/profile/'.$project_id,
            'style' => 'font-weight:bold',
        ),
            $project_name),
        phutil_tag(
            'a',
            array(
                'href'  => '/project/sprint/view/'.$project_id,
             ),
            $this->getEditProjectDetailsIcon()),
        phabricator_datetime($start, $this->viewer),
        phabricator_datetime($end, $this->viewer),
    );

    return $rows;
  }

  private function getEditProjectDetailsIcon() {
    $image = id(new PHUIIconView())
        ->setSpriteSheet(PHUIIconView::SPRITE_PROJECTS)
        ->setIconFont('fa-fire', 'orange')
        ->setText('Burndown');
    return $image;
  }
}
