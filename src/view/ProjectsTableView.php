<?php

final class ProjectsTableView {

  private $projects;
  private $viewer;
  private $request;

  public function setProjects ($projects) {
    $this->projects = $projects;
    return $this;
  }

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest ($request) {
    $this->request = $request;
    return $this;
  }

  public function getProjectRows() {
    $order = $this->request->getStr('order', 'name');
    list($order, $reverse) = AphrontTableView::parseSort($order);

    $rows = array();
    foreach ($this->projects as $project) {

      $query = id(new SprintQuery())
          ->setProject($project)
          ->setViewer($this->viewer);

      $field_list = $query->getCustomFieldList();
      $aux_fields = $query->getAuxFields($field_list);
      $start = $query->getStartDate($aux_fields);
      $end = $query->getEndDate($aux_fields);

      $rows[] = $this->buildRowSet($project, $start, $end, $order);
    }

    $rows = isort($rows, 'sort');
    foreach ($rows as $k => $row) {
      unset($rows[$k]['sort']);
    }
    if ($reverse) {
      $rows = array_reverse($rows);
    }
  return array ($rows, $order, $reverse);
  }

  /**
   * @param string $order
   */
  private function buildRowSet($project, $start, $end, $order) {
    $rows = array();
    $row = array();
    $row[] =  phutil_tag(
        'a',
        array(
            'href'  => '/sprint/view/'.$project->getId(),
            'style' => 'font-weight:bold',
        ),
        $project->getName());
    $row[] = phabricator_datetime($start, $this->viewer);
    $row[] = phabricator_datetime($end, $this->viewer);

    switch ($order) {
      case 'Name':
        $row['sort'] = $project->getName();
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
    return $rows[] = $row;
  }

  /**
   * @param string $order
   * @param integer $reverse
   */
  public function buildProjectsTable ($rows, $order, $reverse) {
    $projects_table = id(new AphrontTableView($rows))
        ->setHeaders(
            array(
                'Sprint Name',
                'Start Date',
                'End Date',
            ))
        ->setColumnClasses(
            array(
                'left',
                'left narrow',
                'left narrow',
            ))
        ->makeSortable(
            $this->request->getRequestURI(),
            'order',
            $order,
            $reverse,
            array(
                'Name',
                'Start',
                'End',
            ));

    $projects_table = id(new PHUIBoxView())
        ->appendChild($projects_table)
        ->addMargin(PHUI::MARGIN_LARGE);

    return $projects_table;
  }

}