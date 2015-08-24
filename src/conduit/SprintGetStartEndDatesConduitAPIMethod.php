<?php

final class SprintGetStartEndDatesConduitAPIMethod extends SprintConduitAPIMethod {

  public function getAPIMethodName() {
    return 'sprint.getstartenddates';
  }

  public function getMethodDescription() {
    return pht('Get sprint start and end dates');
  }

  public function defineParamTypes() {
    return array(
        'project'       => 'required string ("PHID")',
    );
  }

  public function defineReturnType() {
    return 'dict';
  }

  public function defineErrorTypes() {
    return array();
  }

  protected function execute(ConduitAPIRequest $request) {
    $user = $request->getUser();
    $query = id(new SprintQuery())
        ->setViewer($user);
    $project = id(new PhabricatorProjectQuery())
        ->setViewer($user)
        ->withPHIDS(array($request->getValue('project')))
        ->needSlugs(true)
        ->executeOne();
    if (!$project) {
      return null;
    } else {
      $dates = $this->getStartEndDates($query, $project);
      return $dates;
    }
  }
}
