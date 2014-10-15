<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

final class BurndownTestDataGenerator
  extends PhabricatorTestDataGenerator {

  private $xactions = array();

  public function generate() {
    $title = $this->generateTitle();
    // Prepend or append 'Sprint'
    $title = (mt_rand(0,1)) ? $title.' Sprint':'Sprint '.$title;
    $author = $this->loadPhabrictorUser();
    $project = PhabricatorProject::initializeNewProject($author);

    $this->addTransaction(
      PhabricatorProjectTransaction::TYPE_NAME,
      $title);
     $this->addTransaction(
      PhabricatorProjectTransaction::TYPE_ICON,
      'fa-briefcase');
     $this->addTransaction(
      PhabricatorProjectTransaction::TYPE_COLOR,
      'blue');
    // $this->addTransaction(
    //   PhabricatorProjectTransaction::TYPE_MEMBERS,
    //   $this->loadMembersWithAuthor($authorPHID));
    $this->addTransaction(
      PhabricatorTransactions::TYPE_VIEW_POLICY,
      PhabricatorPolicies::POLICY_PUBLIC);
    $this->addTransaction(
      PhabricatorTransactions::TYPE_EDIT_POLICY,
      PhabricatorPolicies::POLICY_PUBLIC);
    $this->addTransaction(
      PhabricatorTransactions::TYPE_JOIN_POLICY,
      PhabricatorPolicies::POLICY_PUBLIC);

    // Pick a date to be the start date for the sprint
    // Random between 4 weeks ago and one week from now
    $start = mt_rand(time() - 28 * 24 * 60 * 60, time() + 7 * 24 * 60 * 60);
    $this->xactions[] = id(new ManiphestTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
      ->setMetadataValue('customfield:key', 'isdc:sprint:startdate')
      ->setOldValue(null)
      ->setNewValue($start);

    // Pick a date to be the end date for the sprint
    // Sprint is between 3 days and 3 weeks long
    $end = $start + mt_rand(3 * 24 * 60 * 60, 21 * 24 * 60 * 60);
    $this->xactions[] = id(new ManiphestTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
      ->setMetadataValue('customfield:key', 'isdc:sprint:enddate')
      ->setOldValue(null)
      ->setNewValue($end);

    $project->save();

    // Generate a bunch of tasks created the before the sprint starts
    for($i = 0, $num = mt_rand(5,40); $i <= $num; $i++) {
      echo ".";
      $this->generateTask($project, $start, $end);
    }

    return $project;
  }

  private function addTransaction($type, $value) {
    $this->xactions[] = id(new PhabricatorProjectTransaction())
      ->setTransactionType($type)
      ->setNewValue($value);
  }

  public function loadMembersWithAuthor($author) {
    $members = array($author);
    for ($i = 0; $i < rand(10, 20);$i++) {
      $members[] = $this->loadPhabrictorUserPHID();
    }
    return $members;
  }

  public function generateTitle() {
    return id(new PhutilLipsumContextFreeGrammar())
      ->generate();
  }

  public function generateDescription() {
    return id(new PhutilLipsumContextFreeGrammar())
      ->generateSeveral(rand(30, 40));
  }

  /**
   * @param $project
   * @param integer $start
   * @param integer $end
   * @return
   */
  public function generateTask($project, $start, $end) {
    // Decide when the task was created
    switch (mt_rand(0,10)) {
      case 0:
        // A few are created during sprint
        $date_created = mt_rand($start, $end);
        break;
      default:
        // Most are created sometime in the 3 days before the sprint
        $date_created = mt_rand($start - 3 * 24 * 60 * 60, $start);
        break;
    }

    $author = $this->loadPhabrictorUser();
    $task = ManiphestTask::initializeNewTask($author);
    $task->setDateCreated($date_created);

    $content_source = PhabricatorContentSource::newForSource(
      PhabricatorContentSource::SOURCE_UNKNOWN,
      array());

    $template = new ManiphestTransaction();
    // Accumulate Transactions
    $changes = array();
    $changes[ManiphestTransaction::TYPE_TITLE] =
      $this->generateTitle();
    $changes[ManiphestTransaction::TYPE_DESCRIPTION] =
      $this->generateDescription();
    $changes[ManiphestTransaction::TYPE_OWNER] =
      $this->loadOwnerPHID();
    $changes[ManiphestTransaction::TYPE_STATUS] =
      ManiphestTaskStatus::STATUS_OPEN;
    $changes[ManiphestTransaction::TYPE_PRIORITY] =
      $this->generateTaskPriority();
    $changes[ManiphestTransaction::TYPE_CCS] =
      $this->getCCPHIDs();
    $transactions = array();
    foreach ($changes as $type => $value) {
      $transaction = clone $template;
      $transaction->setTransactionType($type);
      $transaction->setDateCreated($date_created);
      $transaction->setNewValue($value);
      $transactions[] = $transaction;
    }

    // For most tasks, project will be added when created
    // But for a few, let's add it later
    $project_added = (mt_rand(0,10) == 0) ?
      mt_rand($date_created, $end) : $date_created;
    $transactions[] = id(new ManiphestTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
      ->setDateCreated($project_added)
      ->setMetadataValue(
        'edge:type',
        PhabricatorProjectObjectHasProjectEdgeType::EDGECONST)
      ->setNewValue(
        array(
          '=' => array($project->getPHID() => $project->getPHID()),
        ));

    // Set points when created
    $points = mt_rand(0,10);
    $transactions[] = id(new ManiphestTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
      ->setMetadataValue('customfield:key', 'isdc:sprint:storypoints')
      ->setDateCreated($date_created)
      ->setOldValue(null)
      ->setNewValue($points);

    $editor = id(new ManiphestTransactionEditor())
      ->setActor($author)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true)
      ->setContinueOnMissingFields(true);

    // Apply and clear transactions
    $editor->applyTransactions($task, $transactions);
    $transactions = array();

    // For some tasks, change points part way through sprint
    if (mt_rand(0,10) == 0) {
      $transactions[] = id(new ManiphestTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_CUSTOMFIELD)
        ->setMetadataValue('customfield:key', 'isdc:sprint:storypoints')
        ->setDateCreated(mt_rand($date_created, $end))
        ->setOldValue($points)
        ->setNewValue(mt_rand(0,10));
    }

    // Close some amount of the tasks in the sprint
    $total_days = floor(($end - $start)/24*60*60);
    $elapsed_days = floor((time() - $start)/24*60*60);
    if (mt_rand(0,$total_days) <= $elapsed_days) {
      $transactions[] = id(new ManiphestTransaction())
        ->setTransactionType(ManiphestTransaction::TYPE_STATUS)
        ->setDateCreated(mt_rand(max($date_created, $start), $end))
        ->setNewValue(ManiphestTaskStatus::STATUS_CLOSED_RESOLVED);
    }

    // Apply any "during sprint" transactions
    if ($transactions) {
      $editor->applyTransactions($task, $transactions);
    }

    return $task;
  }

  public function loadOwnerPHID() {
    if (rand(0, 3) == 0) {
      return null;
    } else {
      return $this->loadPhabrictorUserPHID();
    }
  }

  public function getCCPHIDs() {
    $ccs = array();
    for ($i = 0; $i < rand(1, 4);$i++) {
      $ccs[] = $this->loadPhabrictorUserPHID();
    }
    return $ccs;
  }

  public function generateTaskPriority() {
    return array_rand(ManiphestTaskPriority::getTaskPriorityMap());
  }

  public function generateTaskSubPriority() {
    return rand(2 << 16, 2 << 32);
  }

}
