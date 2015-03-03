<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

abstract class SprintController extends PhabricatorController {

  public function shouldAllowPublic() {
    return true;
   }

  public function getProjectsURI() {
    return '/project/';
  }

  public function getUser() {
    return $this->getRequest()->getUser();
  }

  public function setApplicationURI() {
    return new PhutilURI($this->getApplicationURI());
  }

  public function buildApplicationMenu() {
    return $this->buildSideNavView(true, $this->getUser(),
        $this->setApplicationURI())->getMenu();
  }

  public function buildNavMenu() {
    $nav = id(new AphrontSideNavFilterView())
        ->setBaseURI(new PhutilURI($this->getApplicationURI().'report/'))
        ->addLabel(pht('Sprint Projects'))
        ->addFilter('list', pht('List'))
        ->addLabel(pht('Open Tasks'))
        ->addFilter('project', pht('By Project'))
        ->addFilter('user', pht('By User'))
        ->addLabel(pht('Burn Up'))
        ->addFilter('burn', pht('Burn Up Rate'));
    return $nav;
  }

  /**
   * @param PhutilURI $uri
   */
  public function buildSideNavView($for_app = false, $user, $uri) {

    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI($uri);

    if ($for_app) {
      $nav->addFilter('create', pht('Create Task'));
    }

    id(new ManiphestTaskSearchEngine())
        ->setViewer($user)
        ->addNavigationItems($nav->getMenu());

    if ($user->isLoggedIn()) {
      $nav->addLabel(pht('Reports'));
      $nav->addFilter('report', pht('Reports'));
    }

    $nav->selectFilter(null);

    return $nav;
  }

   protected function buildSprintApplicationCrumbs($can_create) {
    $crumbs = $this->buildCrumbs('fa-bar-chart', $this->getApplicationURI());

    $crumbs->addAction(
        id(new PHUIListItemView())
            ->setName(pht('Create Sprint'))
            ->setHref($this->getProjectsURI().'create/')
            ->setIcon('fa-calendar')
            ->setDisabled(!$can_create));

    return $crumbs;
  }

  protected function buildCrumbs($sprite, $uri) {
    $crumbs = array();

      $crumbs[] = id(new PHUICrumbView())
          ->setHref($uri)
          ->setAural($sprite)
          ->setIcon($sprite);

    $view = new PHUICrumbsView();
    foreach ($crumbs as $crumb) {
      $view->addCrumb($crumb);
    }

    return $view;
  }

  public function buildIconNavView(PhabricatorProject $project) {
    $nav = $this->buildSprintIconNavView($project);
    $nav->selectFilter(null);
    return $nav;
  }

  public function buildSprintIconNavView(PhabricatorProject $project) {
    $viewer = $this->getViewer();
    $id = $project->getID();
    $picture = $project->getProfileImageURI();
    $name = $project->getName();

    $columns = id(new PhabricatorProjectColumnQuery())
        ->setViewer($viewer)
        ->withProjectPHIDs(array($project->getPHID()))
        ->execute();
    if ($columns) {
      $board_icon = 'fa-columns';
    } else {
      $board_icon = 'fa-columns grey';
    }

    $nav = new AphrontSideNavFilterView();
    $nav->setIconNav(true);
    if ($this->isSprint($project) !== false) {
      $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));
      $nav->addIcon("profile/{$id}/", $name, null, $picture);
      $nav->addIcon("burn/{$id}/", pht('Burndown'), 'fa-fire');
      $nav->addIcon("board/{$id}/", pht('Sprint Board'), $board_icon);
      $nav->addIcon('.', pht('Sprint List'), 'fa-bar-chart');
    } else {
      $nav->setBaseURI(new PhutilURI($this->getProjectsURI()));
      $nav->addIcon("profile/{$id}/", $name, null, $picture);
      $nav->addIcon("board/{$id}/", pht('Workboard'), $board_icon);
    }
    $class = 'PhabricatorManiphestApplication';
    if (PhabricatorApplication::isClassInstalledForViewer($class, $viewer)) {
      $phid = $project->getPHID();
      $query_uri = urisprintf(
          '/maniphest/?statuses=%s&allProjects=%s#R',
          implode(',', ManiphestTaskStatus::getOpenStatusConstants()),
          $phid);
      $nav->addIcon(null, pht('Open Tasks'), 'fa-anchor', null, $query_uri);
    }

    $nav->addIcon("feed/{$id}/", pht('Feed'), 'fa-newspaper-o');
    $nav->addIcon("members/{$id}/", pht('Members'), 'fa-group');
    $nav->addIcon("details/{$id}/", pht('Edit Details'), 'fa-pencil');

    return $nav;
  }
  protected function isSprint($object) {
    $validator = new SprintValidator();
    $issprint = call_user_func(array($validator, 'checkForSprint'),
        array($validator, 'isSprint'), $object->getPHID());
    return $issprint;
  }

  public function getErrorBox($e) {
    $error_box = id(new PHUIErrorView())
        ->setTitle(pht('Burndown could not be rendered for this project'))
        ->setErrors(array($e->getMessage()));
    return $error_box;
  }
}
