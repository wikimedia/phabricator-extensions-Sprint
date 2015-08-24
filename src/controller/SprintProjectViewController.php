<?php

final class SprintProjectViewController
    extends SprintController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $request = $this->getRequest();
    $viewer = $request->getViewer();

    $query = id(new PhabricatorProjectQuery())
        ->setViewer($viewer)
        ->needMembers(true)
        ->needWatchers(true)
        ->needImages(true)
        ->needSlugs(true);
    $id = $request->getURIData('id');
    $slug = $request->getURIData('slug');
    if ($slug) {
      $query->withSlugs(array($slug));
    } else {
      $query->withIDs(array($id));
    }
    $project = $query->executeOne();
    if (!$project) {
      return new Aphront404Response();
    }


    $columns = id(new PhabricatorProjectColumnQuery())
        ->setViewer($viewer)
        ->withProjectPHIDs(array($project->getPHID()))
        ->execute();
    if ($columns) {
      $controller = 'board';
    } else {
      $controller = 'profile';
    }

    switch ($controller) {
      case 'board':
        $controller_object = new SprintBoardViewController();
        break;
      case 'profile':
      default:
        $controller_object = new SprintProjectProfileController();
        break;
    }

    return $this->delegateToController($controller_object);
  }

}
