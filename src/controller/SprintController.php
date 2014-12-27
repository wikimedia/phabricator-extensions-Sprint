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
    return $this->buildSideNavView(true, $this->getUser(),$this->setApplicationURI())->getMenu();
  }

  public function buildNavMenu() {
    $nav = id(new AphrontSideNavFilterView())
        ->setBaseURI(new PhutilURI('/sprint/report/'))
        ->addLabel(pht('Sprint Projects'))
        ->addFilter('list', pht('List'))
        ->addLabel(pht('Open Tasks'))
        ->addFilter('project', pht('By Project'))
        ->addFilter('user', pht('By User'))
        ->addLabel(pht('Burndown'))
        ->addFilter('burn', pht('Burndown Rate'));
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
      // For now, don't give logged-out users access to reports.
      $nav->addLabel(pht('Reports'));
      $nav->addFilter('report', pht('Reports'));
    }

    $nav->selectFilter(null);

    return $nav;
  }

  protected function buildApplicationCrumbs() {
    $crumbs = $this->buildCrumbs('projects', '/project/');

    $can_create = $this->hasApplicationCapability(
        ProjectCreateProjectsCapability::CAPABILITY);

    $crumbs->addAction(
        id(new PHUIListItemView())
            ->setName(pht('Create Project'))
            ->setHref($this->getProjectsURI().'create/')
            ->setIcon('fa-plus-square')
            ->setDisabled(!$can_create)
            ->setAppIcon('projects'));

    return $crumbs;
  }
  protected function buildSprintApplicationCrumbs() {
    $crumbs = $this->buildCrumbs('slowvote', '/sprint/');

  $can_create = $this->hasApplicationCapability(
        ProjectCreateProjectsCapability::CAPABILITY);

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


      $crumbs[] = id(new PhabricatorCrumbView())
          ->setHref($uri)
          ->setAural($sprite)
          ->setIcon($sprite);


    $view = new PhabricatorCrumbsView();
    foreach ($crumbs as $crumb) {
      $view->addCrumb($crumb);
    }

    return $view;
  }

}
