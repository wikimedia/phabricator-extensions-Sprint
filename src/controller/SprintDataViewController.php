<?php

final class SprintDataViewController extends SprintController {

  private $projectID;
  private $request;
  private $viewer;
  private $project;

  public function handleRequest(AphrontRequest $request) {
    $this->projectID = $request->getURIData('id');
    $this->request = $this->getRequest();
    $this->viewer = $this->request->getUser();
    $this->project = $this->loadProject();
    if (!$this->project) {
      return new Aphront404Response();
    }

    $error_box = null;
    $sprintdata_view = null;

    try {
      $sprintdata_view = $this->getSprintDataView();
      } catch (Exception $e) {
      $error_box = $this->getErrorBox($e);
    }

    $can_create = $this->hasApplicationCapability(
        ProjectCreateProjectsCapability::CAPABILITY);
    $crumbs = $this->getCrumbs($can_create);
    $nav = $this->buildIconNavView($this->project);
    $nav->appendChild(
        array($crumbs,
              $error_box,
              $sprintdata_view,));

    return $this->buildApplicationPage(
        $nav,
        array(
            'title' => array(pht('Burndown'), $this->project->getName()),
            'device' => true,
        ));
  }

  public function loadProject() {
    // Load the project we're looking at, based on the project ID in the URL.
    $project = id(new PhabricatorProjectQuery())
        ->setViewer($this->viewer)
        ->withIDs(array($this->projectID))
        ->needImages(true)
        ->executeOne();
   return $project;
  }

  public function getCrumbs($can_create) {

    $crumbs = $this->buildSprintApplicationCrumbs($can_create);
    $crumbs->addTextCrumb(
        $this->project->getName(),
        $this->getApplicationURI().'profile/'.$this->projectID);
    $crumbs->addTextCrumb(pht('Burndown'));
    $crumbs->addAction(
        id(new PHUIListItemView())
            ->setName(pht('Sprint Board'))
            ->setHref($this->getApplicationURI().'board/'.$this->projectID)
            ->setIcon('fa-columns'));
   return $crumbs;
  }

  public function getSprintDataView() {
    $sprintdata_view = id(new SprintDataView())
        ->setProject($this->project)
        ->setViewer($this->viewer)
        ->setRequest($this->request);
    return $sprintdata_view;
  }

}
