<?php

final class SprintListController extends SprintController {

  private $view;
  private $viewer;

  public function handleRequest(AphrontRequest $request) {
    $this->viewer = $request->getUser();

    $error_box = null;
    $sprintlist_model = id(new SprintListDataProvider())
        ->setViewer($this->viewer)
        ->setRequest($request)
        ->execute();

    try {
      $sprintlist_table = id(new SprintListTableView())
          ->setTableData($sprintlist_model)
          ->buildProjectsTable();
    } catch (Exception $e) {
      $error_box = $this->getErrorBox($e);
    }


    $can_create = $this->hasApplicationCapability(
        ProjectCreateProjectsCapability::CAPABILITY);
    $crumbs = $this->buildSprintApplicationCrumbs($can_create);
    $crumbs->addTextCrumb(pht('Sprint Burndown List'));

    $help = id(new PHUIBoxView())
        ->appendChild(phutil_tag('p', array(),
            'To have a project show up in this list, make sure that the'
            .'"Is Sprint" box has been checked in Project Edit Details'))
        ->addMargin(PHUI::MARGIN_LARGE);
    $nav = $this->buildNavMenu();
    $this->view = $nav->selectFilter($this->view, 'list');
    $nav->appendChild(
        array(
            $error_box,
            $crumbs,
            $help,
            $sprintlist_table,
        ));

    return $this->buildApplicationPage(
      $nav,
      array(
        'title' => array(pht('Sprint List')),
        'device' => true,
      ));
  }
}
