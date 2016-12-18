<?php

abstract class SprintProjectController extends SprintController {

  private $project;
  private $profileMenu;

  const ITEM_BURNDOWN = 'project.sprint';

  protected function setProject(PhabricatorProject $project) {
    $this->project = $project;
    return $this;
  }

  protected function getProject() {
    return $this->project;
  }

  protected function loadProject() {
    $viewer = $this->getViewer();
    $request = $this->getRequest();

    $id = nonempty(
        $request->getURIData('projectID'),
        $request->getURIData('id'));
    $slug = $request->getURIData('slug');

    if ($slug) {
      $normal_slug = PhabricatorSlug::normalizeProjectSlug($slug);
      $is_abnormal = ($slug !== $normal_slug);
      $normal_uri = "/tag/{$normal_slug}/";
    } else {
      $is_abnormal = false;
    }

    $query = id(new PhabricatorProjectQuery())
        ->setViewer($viewer)
        ->needMembers(true)
        ->needWatchers(true)
        ->needImages(true)
        ->needSlugs(true);

    if ($slug) {
      $query->withSlugs(array($slug));
    } else {
      $query->withIDs(array($id));
    }

    $policy_exception = null;
    try {
      $project = $query->executeOne();
    } catch (PhabricatorPolicyException $ex) {
      $policy_exception = $ex;
      $project = null;
    }

    if (!$project) {
      // This project legitimately does not exist, so just 404 the user.
      if (!$policy_exception) {
        return new Aphront404Response();
      }

      // Here, the project exists but the user can't see it. If they are
      // using a non-canonical slug to view the project, redirect to the
      // canonical slug. If they're already using the canonical slug, rethrow
      // the exception to give them the policy error.
      if ($is_abnormal) {
        return id(new AphrontRedirectResponse())->setURI($normal_uri);
      } else {
        throw $policy_exception;
      }
    }

    // The user can view the project, but is using a noncanonical slug.
    // Redirect to the canonical slug.
    $primary_slug = $project->getPrimarySlug();
    if ($slug && ($slug !== $primary_slug)) {
      $primary_uri = "/tag/{$primary_slug}/";
      return id(new AphrontRedirectResponse())->setURI($primary_uri);
    }

    $this->setProject($project);

    return null;
  }

  public function buildApplicationMenu() {
    $menu = $this->newApplicationMenu();

    $profile_menu = $this->getProfileMenu();
    if ($profile_menu) {
      $menu->setProfileMenu($profile_menu);
    }

    $menu->setSearchEngine(new PhabricatorProjectSearchEngine());

    return $menu;
  }

  protected function getProfileMenu() {
    if (!$this->profileMenu) {
      $project = $this->getProject();
      if ($project) {
        $viewer = $this->getViewer();

        $engine = id(new SprintProjectProfileMenuEngine())
            ->setViewer($viewer)
            ->setProfileObject($project);

        $this->profileMenu = $engine->buildNavigation();
      }
    }

    return $this->profileMenu;
  }

  public function buildSideNavView($for_app = false) {
    $project = $this->getProject();

    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

    $viewer = $this->getViewer();

    $id = null;
    if ($for_app) {
      if ($project) {
        $id = $project->getID();
        $nav->addFilter("profile/{$id}/", pht('Profile'));
        $nav->addFilter("board/{$id}/", pht('Workboard'));
        $nav->addFilter("members/{$id}/", pht('Members'));
        $nav->addFilter("feed/{$id}/", pht('Feed'));
        $nav->addFilter("details/{$id}/", pht('Edit Details'));
      }
      $nav->addFilter('create', pht('Create Project'));
    }

    if (!$id) {
      id(new PhabricatorProjectSearchEngine())
          ->setViewer($viewer)
          ->addNavigationItems($nav->getMenu());
    }

    $nav->selectFilter(null);

    return $nav;
  }

  protected function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();

    $project = $this->getProject();
    if ($project) {
      $ancestors = $project->getAncestorProjects();
      $ancestors = array_reverse($ancestors);
      $ancestors[] = $project;
      foreach ($ancestors as $ancestor) {
        $crumbs->addTextCrumb(
            $project->getName(),
            $project->getURI());
      }
    }

    return $crumbs;
  }

}
