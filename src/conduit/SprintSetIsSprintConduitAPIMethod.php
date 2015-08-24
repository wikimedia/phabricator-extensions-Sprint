<?php

final class SprintSetIsSprintConduitAPIMethod extends SprintConduitAPIMethod {

  public function getAPIMethodName() {
    return 'sprint.setissprint';
  }

  public function getMethodDescription() {
    return pht('Set a project as a sprint');
  }

  public function defineParamTypes() {
    return array(
        'project'       => 'required string ("PHID")',
        'issprint'       => 'boolean ("1")',
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

    $xactions = array();

    $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
        ->setMetadataValue('customfield:key', 'isdc:sprint:issprint')
        ->setOldValue(null)
        ->setNewValue($request->getValue('issprint'));

    $editor = id(new PhabricatorProjectTransactionEditor())
        ->setActor($user)
        ->setContinueOnNoEffect(true)
        ->setContentSourceFromConduitRequest($request);

    $project = id(new PhabricatorProjectQuery())
        ->setViewer($user)
        ->withPHIDS(array($request->getValue('project')))
        ->needSlugs(true)
        ->executeOne();

    if (!$project) {
      return null;
    } else {
      $editor->applyTransactions($project, $xactions);
      return $this->buildProjectInfoDictionary($project);
    }
  }

}
