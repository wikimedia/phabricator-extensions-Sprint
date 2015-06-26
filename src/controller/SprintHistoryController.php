<?php

final class SprintHistoryController extends SprintController {

  private $view;
  private $viewer;

  public function handleRequest(AphrontRequest $request) {
    $this->viewer = $request->getUser();

    if ($request->isFormPost()) {
      $uri = $request->getRequestURI();

      $project = head($request->getArr('set_project'));
      $project = nonempty($project, null);
      $uri = $uri->alter('project', $project);

      $window = $request->getStr('set_window');
      $uri = $uri->alter('window', $window);

      return id(new AphrontRedirectResponse())->setURI($uri);
    }

    $error_box = null;
    $history_model = id(new SprintHistoryDataProvider())
        ->setViewer($this->viewer)
        ->setRequest($request)
        ->execute();

    try {
      $sprintlist_table = id(new SprintHistoryTableView())
          ->setViewer($this->viewer)
          ->setRequest($request)
          ->setTableData($history_model)
          ->render();
    } catch (Exception $e) {
      $error_box = $this->getErrorBox($e);
    }


    $can_create = $this->hasApplicationCapability(
        ProjectCreateProjectsCapability::CAPABILITY);
    $crumbs = $this->buildSprintApplicationCrumbs($can_create);
    $crumbs->addTextCrumb(pht('Task Project History'));

    $help = id(new PHUIBoxView())
        ->appendChild(phutil_tag('p', array(),
            'This is a history of tasks and logs when a project was added or removed'))
        ->appendChild(phutil_tag('br', array(), ''))
        ->appendChild(phutil_tag('p', array(),
            'NOTE: The tasks are selected from the current tasks in the project.  Tasks previously removed
            will not appear!'))
        ->addMargin(PHUI::MARGIN_LARGE);
    $nav = $this->buildNavMenu();
    $this->view = $nav->selectFilter($this->view, 'history');
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
            'title' => array(pht('Task Project History')),
            'device' => true,
        ));
  }
}
