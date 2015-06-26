<?php

final class SprintGetTaskProjectHistoryConduitAPIMethod extends SprintConduitAPIMethod {

  public function getAPIMethodName() {
    return 'sprint.gettaskprojecthistory';
  }

  public function getMethodDescription() {
    return pht('Get History of Task Project Assignment');
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

    $this->requireApplicationCapability(
        ProjectCreateProjectsCapability::CAPABILITY,
        $user);

    $history = id(new SprintQuery())
        ->setViewer($user)
        ->getTaskHistory($request->getValue('project'));
    return $history;
  }

}
