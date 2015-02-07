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
    $sprintdata_view = null;

    try {
      $sprintdata_view = $this->getSprintDataView($request, $project, $viewer);
      } catch (Exception $e) {
      $error_box = $this->getErrorBox($e);
    }

    $can_create = $this->hasApplicationCapability(
        ProjectCreateProjectsCapability::CAPABILITY);
    $crumbs = $this->getCrumbs($project, $can_create);
    $nav = $this->buildIconNavView($project);
    $nav->appendChild($crumbs);
    $nav->appendChild($error_box);
    $nav->appendChild($sprintdata_view);

    return $this->buildApplicationPage(
        array(
            $nav,
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
        ->needImages(true)
        ->executeOne();
   return $project;
  }

  public function getCrumbs($project, $can_create) {
    $pid = $project->getID();

    $crumbs = $this->buildSprintApplicationCrumbs($can_create);
    $crumbs->addTextCrumb(
        $project->getName(),
        $this->getApplicationURI().'profile/'.$pid);
    $crumbs->addTextCrumb(pht('Burndown'));
    $crumbs->addAction(
        id(new PHUIListItemView())
            ->setName(pht('Sprint Board'))
            ->setHref($this->getApplicationURI().'board/'.$pid)
            ->setIcon('fa-columns'));
   return $crumbs;
  }

  public function getSprintDataView($request, $project, $viewer) {
    $sprintdata_view = id(new SprintDataView())
        ->setProject($project)
        ->setViewer($viewer)
        ->setRequest($request);
    return $sprintdata_view;
  }

  public function getErrorBox($e) {
    $error_box = id(new PHUIErrorView())
        ->setTitle(pht('Burndown could not be rendered for this project'))
        ->setErrors(array($e->getMessage()));
    return $error_box;
  }
}
