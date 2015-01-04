<?php

final class SprintDataViewController extends SprintController {

  private $projectID;

  public function willProcessRequest(array $data) {
    $this->projectID = $data['id'];
  }

  public function processRequest() {

    $request = $this->getRequest();
    $viewer = $request->getUser();
    $pid = $this->projectID;
    $project = $this->loadProject($viewer, $pid);
    if (!$project) {
      return new Aphront404Response();
    }

    $error_box = null;
    $burndown_view = null;

    try {
      $burndown_view = $this->getBurndownView($request, $project, $viewer);
      } catch (BurndownException $e) {
      $error_box = $this->getErrorBox($e);
    }

    $can_create = $this->hasApplicationCapability(
        ProjectCreateProjectsCapability::CAPABILITY);
    $crumbs = $this->getCrumbs($project, $can_create);

    return $this->buildApplicationPage(
        array(
            $crumbs,
            $error_box,
            $burndown_view,
        ),
        array(
            'title' => array(pht('Burndown'), $project->getName()),
            'device' => true,
        ));
  }

  public function loadProject($viewer, $pid) {
    // Load the project we're looking at, based on the project ID in the URL.
    $project = id(new PhabricatorProjectQuery())
        ->setViewer($viewer)
        ->withIDs(array($pid))
        ->executeOne();
   return $project;
  }

  public function getCrumbs($project, $can_create) {
    $pid = $project->getID();

    $crumbs = $this->buildSprintApplicationCrumbs($can_create);
    $crumbs->addTextCrumb(
        $project->getName(),
        '/project/view/'.$pid);
    $crumbs->addTextCrumb(pht('Burndown'));
    $crumbs->addAction(
        id(new PHUIListItemView())
            ->setName(pht('Sprint Board'))
            ->setHref('/sprint/board/'.$pid)
            ->setIcon('fa-columns'));
   return $crumbs;
  }

  public function getBurndownView($request, $project, $viewer) {
    $burndown_view = id(new SprintDataView())
        ->setProject($project)
        ->setViewer($viewer)
        ->setRequest($request);
    return $burndown_view;
  }

  public function getErrorBox($e) {
    $error_box = id(new AphrontErrorView())
        ->setTitle(pht('Burndown could not be rendered for this project'))
        ->setErrors(array($e->getMessage()));
    return $error_box;
  }
}