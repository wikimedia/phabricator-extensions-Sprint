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
    $sprint_phids = $this->getSprintPHIDs();
    $projects = $this->loadAllSprints($viewer, $sprint_phids);
    $this->view = $nav->selectFilter($this->view, 'list');

    $projects_table_view = id(new ProjectsTableView())
        ->setProjects($projects)
        ->setViewer($viewer)
        ->setRequest($request);

    list ($rows, $order, $reverse) = $projects_table_view->getProjectRows();
    $projects_table = $projects_table_view->buildProjectsTable($rows, $order,
        $reverse, $nav);

    $can_create = $this->hasApplicationCapability(
        ProjectCreateProjectsCapability::CAPABILITY);
    $crumbs = $this->buildSprintApplicationCrumbs($can_create);
    $crumbs->addTextCrumb(pht('Sprint Burndown List'));


    $help = id(new PHUIBoxView())
        ->appendChild(phutil_tag('p', array(),
            'To have a project show up in this list, make sure that the'
            .'"Is Sprint" box has been checked in Project Edit Details'))
        ->addMargin(PHUI::MARGIN_LARGE);

    $nav->appendChild(
        array(
            $crumbs,
            $help,
            $projects_table,
        ));

    return $this->buildApplicationPage(
      array(
          $nav,
      ),
      array(
        'title' => array(pht('Sprint List')),
        'device' => true,
      ));
  }

  private function getSprintPHIDs() {
    $query = id(new SprintQuery());
    $sprint_phids = $query->getSprintPHIDs();
    return $sprint_phids;
  }

  private function loadAllSprints($viewer, $sprints) {
    $projects = id(new PhabricatorProjectQuery())
      ->setViewer($viewer)
      ->withPHIDS($sprints)
      ->execute();
    return $projects;
  }
}
