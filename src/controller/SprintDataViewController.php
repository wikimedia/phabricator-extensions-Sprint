<?php

final class SprintDataViewController extends SprintController {

  // Project data
  private $projectID;

  public function willProcessRequest(array $data) {
    $this->projectID = $data['id'];
  }

  public function processRequest() {

    $request = $this->getRequest();
    $viewer = $request->getUser();

    // Load the project we're looking at, based on the project ID in the URL.
    $project = id(new PhabricatorProjectQuery())
        ->setViewer($viewer)
        ->withIDs(array($this->projectID))
        ->executeOne();
    if (!$project) {
      return new Aphront404Response();
    }

    $error_box = false;
    $burndown_view = false;

    try {
      $burndown_view = id(new SprintDataView())
          ->setProject($project)
          ->setViewer($viewer)
          ->setRequest($request);
      } catch (BurndownException $e) {
      $error_box = id(new AphrontErrorView())
          ->setTitle(pht('Burndown could not be rendered for this project'))
          ->setErrors(array($e->getMessage()));
    }

    $pid = $project->getID();
    $crumbs = $this->buildSprintApplicationCrumbs();
    $crumbs->addTextCrumb(
        $project->getName(),
        '/project/view/'.$pid);
    $crumbs->addTextCrumb(pht('Burndown'));
    $crumbs->addAction(
        id(new PHUIListItemView())
            ->setName(pht('Sprint Board'))
            ->setHref('/sprint/board/'.$pid)
            ->setIcon('fa-columns'));

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
}