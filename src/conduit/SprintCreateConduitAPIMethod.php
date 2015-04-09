<?php

final class SprintCreateConduitAPIMethod extends SprintConduitAPIMethod {

  public function getAPIMethodName() {
    return 'sprint.create';
  }

  public function getMethodDescription() {
    return pht('Create a Sprint Project');
  }

  public function defineParamTypes() {
    return array(
      'name'       => 'required string ("name")',
      'members'    => 'optional list ([<phid>]) - empty list enter []',
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

    $project = PhabricatorProject::initializeNewProject($user);
    $type_name = PhabricatorProjectTransaction::TYPE_NAME;
    $members = $request->getValue('members');
    $xactions = array();

    $xactions[] = id(new PhabricatorProjectTransaction())
      ->setTransactionType($type_name)
      ->setNewValue($request->getValue('name'));

    $xactions[] = id(new PhabricatorProjectTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
      ->setMetadataValue(
        'edge:type',
        PhabricatorProjectProjectHasMemberEdgeType::EDGECONST)
      ->setNewValue(
        array(
          '+' => array_fuse($members),
        ));

    $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(PhabricatorProjectTransaction::TYPE_ICON)
        ->setNewValue('fa-calendar');

    $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(PhabricatorProjectTransaction::TYPE_COLOR)
        ->setNewValue('green');

    $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
        ->setMetadataValue('customfield:key', 'isdc:sprint:issprint')
        ->setOldValue(null)
        ->setNewValue(1);

    $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
        ->setMetadataValue('customfield:key', 'isdc:sprint:startdate')
        ->setOldValue(null)
        ->setNewValue(strtotime($request->getValue('startdate')));

    $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
        ->setMetadataValue('customfield:key', 'isdc:sprint:enddate')
        ->setOldValue(0)
        ->setNewValue(strtotime($request->getValue('enddate')));

    $editor = id(new PhabricatorProjectTransactionEditor())
      ->setActor($user)
      ->setContinueOnNoEffect(true)
      ->setContentSourceFromConduitRequest($request);

    $editor->applyTransactions($project, $xactions);

    return $this->buildProjectInfoDictionary($project);
  }

}
