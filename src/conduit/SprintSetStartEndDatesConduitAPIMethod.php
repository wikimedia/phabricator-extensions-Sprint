<?php

final class SprintSetStartEndDatesConduitAPIMethod extends SprintConduitAPIMethod {

  public function getAPIMethodName() {
    return 'sprint.setstartenddates';
  }

  public function getMethodDescription() {
    return pht('Set sprint start and end dates');
  }

  public function defineParamTypes() {
    return array(
        'project'       => 'required string ("PHID")',
        'startdate'  => 'required string ("YYYY-MM-DD H:i")',
        'enddate'    => 'required string ("YYYY-MM-DD H:i")',
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
        ->setMetadataValue('customfield:key', 'isdc:sprint:startdate')
        ->setOldValue(null)
        ->setNewValue(strtotime($request->getValue('startdate')));

    $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
        ->setMetadataValue('customfield:key', 'isdc:sprint:enddate')
        ->setOldValue(null)
        ->setNewValue(strtotime($request->getValue('enddate')));

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
      return $this->buildSprintInfoDictionary($project, $user);
    }
  }

}
