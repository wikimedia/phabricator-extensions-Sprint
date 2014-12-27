<?php

final class SprintReportController extends SprintController {

  private $view;

  public function willProcessRequest(array $data) {
    $this->view = idx($data, 'view');
  }

  public function processRequest() {
    $request = $this->getRequest();
    $user = $request->getUser();

    if ($request->isFormPost()) {
      $uri = $request->getRequestURI();

      $project = head($request->getArr('set_project'));
      $project = nonempty($project, null);
      $uri = $uri->alter('project', $project);

      $window = $request->getStr('set_window');
      $uri = $uri->alter('window', $window);

      return id(new AphrontRedirectResponse())->setURI($uri);
    }

    $nav = $this->buildNavMenu();
    $this->view = $nav->selectFilter($this->view, 'List');
    require_celerity_resource('maniphest-report-css');

    switch ($this->view) {
      case 'list':
      case 'user':
      case 'project':
      $core = id(new SprintReportOpenTasksView())
          ->setUser($user)
          ->setRequest($request)
          ->setView($this->view);
        break;
      case 'burn':
        $core = id(new SprintReportBurndownView())
            ->setUser($user)
            ->setRequest($request);
        break;
      default:
        return new Aphront404Response();
    }

    $nav->appendChild($core);
    $nav->setCrumbs(
        $this->buildSprintApplicationCrumbs()
            ->addTextCrumb(pht('Reports')));

    return $this->buildApplicationPage(
        $nav,
        array(
            'title' => pht('Sprint Reports'),
            'device' => false,
        ));
  }
}

