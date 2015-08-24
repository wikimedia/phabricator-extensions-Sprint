<?php
/**
 * @author Michael Peters
 * @author Christopher Johnson
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
      return $this->buildSideNavView($this->getUser(),
          $this->setApplicationURI(), true)->getMenu();
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
        ->addFilter('burn', pht('Burn Up Rate'))
        ->addFilter('history', pht('Task Project History'));
    return $nav;
  }

  /**
   * @param PhutilURI $uri
   */
  public function buildSideNavView($viewer, $uri, $for_app = false) {
    $request = $this->getRequest();
    $id = $request->getURIData('id');
    $slug = $request->getURIData('slug');
    if ($slug) {
      $id = $this->getProjectIDfromSlug($slug, $viewer);
    }
    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI($uri);

    if ($for_app) {
      if ($id) {
        $nav->addFilter("profile/{$id}/", pht('Profile'));
        $nav->addFilter("board/{$id}/", pht('Workboard'));
        $nav->addFilter("members/{$id}/", pht('Members'));
        $nav->addFilter("feed/{$id}/", pht('Feed'));
        $nav->addFilter("details/{$id}/", pht('Edit Details'));
      }
      $nav->addFilter('create', pht('Create Project'));
    }

    id(new PhabricatorProjectSearchEngine())
        ->setViewer($viewer)
        ->addNavigationItems($nav->getMenu());

    if ($viewer->isLoggedIn()) {
      $nav->addLabel(pht('Sprints'));
      $nav->addFilter('report/list', pht('Sprint List'));
    }

    $nav->selectFilter(null);

    return $nav;
  }

   protected function buildSprintApplicationCrumbs($can_create) {
    $crumbs = $this->buildCrumbs('fa-bar-chart', $this->getApplicationURI());

    $crumbs->addAction(
        id(new PHUIListItemView())
            ->setName(pht('Create Sprint'))
            ->setHref('/conduit/method/sprint.create/')
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

  public function getProjectIDfromSlug($slug, $viewer) {
    $project = id(new PhabricatorProjectQuery())
        ->setViewer($viewer)
        ->withSlugs(array($slug))
        ->executeOne();
    $id = $project->getID();
    return $id;
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
    $enable_phragile = PhabricatorEnv::getEnvConfig('sprint.enable-phragile');
    $phragile_uri = new PhutilURI('https://phragile.wmflabs.org/sprints/'.$id);
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
      $nav->addIcon("profile/{$id}/", $name, null, $picture, null);
      $nav->addIcon("burn/{$id}/", pht('Burndown'), 'fa-fire', null, null);
      if ($enable_phragile) {
        $nav->addIcon("sprints/{$id}/", pht('Phragile'), 'fa-pie-chart', null, $phragile_uri);
      }
      $nav->addIcon("board/{$id}/", pht('Sprint Board'), $board_icon, null, null);
      $nav->addIcon('.', pht('Sprint List'), 'fa-bar-chart', null, null);
    } else {
      $nav->setBaseURI(new PhutilURI($this->getProjectsURI()));
      $nav->addIcon("profile/{$id}/", $name, null, $picture);
      $nav->addIcon("board/{$id}/", pht('Workboard'), $board_icon);
    }
    $class = 'PhabricatorManiphestApplication';
    if (PhabricatorApplication::isClassInstalledForViewer($class, $viewer)) {
      $phid = $project->getPHID();
      $query_uri = urisprintf(
          '/maniphest/?statuses=open()&projects=%s#R',
          $phid);
      $nav->addIcon(null, pht('Open Tasks'), 'fa-anchor', null, $query_uri);
    }

    $nav->addIcon("feed/{$id}/", pht('Feed'), 'fa-newspaper-o', null, null);
    $nav->addIcon("members/{$id}/", pht('Members'), 'fa-group', null, null);
    $nav->addIcon("details/{$id}/", pht('Edit Details'), 'fa-pencil', null, null);

    return $nav;
  }
  protected function isSprint($object) {
    $validator = new SprintValidator();
    $issprint = call_user_func(array($validator, 'checkForSprint'),
        array($validator, 'isSprint'), $object->getPHID());
    return $issprint;
  }

  public function getErrorBox($e) {
    $error_box = id(new PHUIInfoView())
        ->setTitle(pht('Burndown could not be rendered for this project'))
        ->setErrors(array($e->getMessage()));
    return $error_box;
  }
}
