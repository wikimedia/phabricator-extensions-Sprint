<?php

final class SprintGetIsSprintConduitAPIMethod extends SprintConduitAPIMethod {

  public function getAPIMethodName() {
    return 'sprint.getissprint';
  }

  public function getMethodDescription() {
    return pht('Get if a project is a sprint');
  }

  public function defineParamTypes() {
    return array(
        'project'       => 'required string ("PHID")',
    );
  }

  public function defineReturnType() {
    return 'bool';
  }

  public function defineErrorTypes() {
    return array();
  }

  protected function execute(ConduitAPIRequest $request) {
    $project_phid = $request->getValue('project');
    return $this->isSprint($project_phid);
  }
}
