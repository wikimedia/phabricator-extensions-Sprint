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
            "To have a project show up in this list, make sure its name includes"
            ."\"".SprintConstants::MAGIC_WORD."\" and then edit it to set the start and end date."
        ))
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

  // Load all projects with "§" in the name.
  private function loadAllProjects($viewer) {
    $projects = id(new SprintProjectQuery())
      ->setViewer($viewer)
      ->withDatasourceQuery(SprintConstants::MAGIC_WORD)
      ->execute();
    return $projects;
  }
}
