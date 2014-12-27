<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

final class SprintListController extends SprintController {

  private $view;

  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $nav = $this->buildNavMenu();
    $projects = $this->loadAllProjects($viewer);
    $this->view = $nav->selectFilter($this->view, 'list');

    $order = $request->getStr('order', 'name');
    list($order, $reverse) = AphrontTableView::parseSort($order);

    $rows = array();
    foreach ($projects as $project) {

      $query = id(new SprintQuery())
          ->setProject($project)
          ->setViewer($viewer);

      $aux_fields = $query->getAuxFields();
      $start = $query->getStartDate($aux_fields);
      $end = $query->getEndDate($aux_fields);

     $rows[] = $this->buildRowSet($project, $start, $viewer, $end, $order);
    }

    $rows = isort($rows, 'sort');
    foreach ($rows as $k => $row) {
      unset($rows[$k]['sort']);
    }
    if ($reverse) {
      $rows = array_reverse($rows);
    }

    $nav = $this->buildProjectsTable($rows, $request, $order, $reverse, $nav);
    return $this->buildApplicationPage(
      array(
        $nav,
      ),
      array(
        'title' => array(pht('Sprint List')),
        'device' => true,
      ));
  }

  /**
   * @param string $order
   */
  private function buildRowSet($project, $start, $viewer, $end, $order) {
    $rows = array();
    $row = array();
    $row[] =  phutil_tag(
        'a',
        array(
            'href'  => '/sprint/view/'.$project->getId(),
            'style' => 'font-weight:bold',
        ),
        $project->getName());
    $row[] = phabricator_datetime($start, $viewer);
    $row[] = phabricator_datetime($end, $viewer);

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
   * @param AphrontRequest $request
   * @param string $order
   * @param integer $reverse
   */
  private function buildProjectsTable ($rows, $request, $order, $reverse, $nav) {
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
            $request->getRequestURI(),
            'order',
            $order,
            $reverse,
            array(
                'Name',
                'Start',
                'End',
            ));


    $crumbs = $this->buildSprintApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Burndown List'));


    $help = id(new PHUIBoxView())
        ->appendChild(phutil_tag('p', array(),
            "To have a project show up in this list, make sure it's name includes"
            ."\"§\" and then edit it to set the start and end date."
        ))
        ->addMargin(PHUI::MARGIN_LARGE);

    $box= id(new PHUIBoxView())
        ->appendChild($projects_table)
        ->addMargin(PHUI::MARGIN_LARGE);

    $nav->appendChild(
        array(
            $crumbs,
            $help,
            $box,
        ));
    return $nav;
  }

  // Load all projects with "§" in the name.
  private function loadAllProjects($viewer) {
    $projects = id(new PhabricatorProjectQuery())
      ->setViewer($viewer)
      ->withDatasourceQuery(SprintConstants::MAGIC_WORD)
      ->execute();
    return $projects;
  }
}
