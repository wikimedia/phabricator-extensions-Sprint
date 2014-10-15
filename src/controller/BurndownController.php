<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

abstract class BurndownController extends PhabricatorController {

  public function shouldAllowPublic() {
        return true;
   }

  public function getProjectsURI() {
    return '/project/';
  }

  public function buildApplicationMenu() {
    return $this->buildSideNavView(true)->getMenu();
  }

  public function buildNavMenu() {
    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI(new PhutilURI('/sprint/report/'));
    $nav->addLabel(pht('Sprint Projects'));
    $nav->addFilter('list', pht('List'));
    $nav->addLabel(pht('Open Tasks'));
    $nav->addFilter('project', pht('By Project'));
    $nav->addFilter('user', pht('By User'));
    $nav->addLabel(pht('Burndown'));
    $nav->addFilter('burn', pht('Burndown Rate'));

    return $nav;
  }

  public function buildSideNavView($for_app = false) {
    $user = $this->getRequest()->getUser();

    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

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

  public function getAuxFields($project, $viewer) {
    $field_list = PhabricatorCustomField::getObjectFields(
        $project,
        PhabricatorCustomField::ROLE_EDIT);
    $field_list->setViewer($viewer);
    $field_list->readFieldsFromStorage($project);
    $aux_fields = $field_list->getFields();
    return $aux_fields;
  }

  protected function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();

    $crumbs->addAction(
        id(new PHUIListItemView())
            ->setName(pht('Create Sprint'))
            ->setHref($this->getProjectsURI().'create/')
            ->setIcon('fa-calendar'));

    return $crumbs;
  }

}
