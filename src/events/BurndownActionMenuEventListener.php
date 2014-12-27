<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

final class BurndownActionMenuEventListener extends PhabricatorEventListener {

  public function register() {
    $this->listen(PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS);
  }

  public function handleEvent(PhutilEvent $event) {
    switch ($event->getType()) {
      case PhabricatorEventType::TYPE_UI_DIDRENDERACTIONS:
        $this->handleActionsEvent($event);
      break;
    }
  }

  private function handleActionsEvent(PhutilEvent $event) {
    $object = $event->getValue('object');

    $actions = null;
    if ($object instanceof PhabricatorProject &&
      stripos($object->getName(), SprintConstants::MAGIC_WORD) !== false) {
      $actions = $this->renderUserItems($event);
    }

    $this->addActionMenuItems($event, $actions);
  }

  private function renderUserItems(PhutilEvent $event) {
    if (!$this->canUseApplication($event->getUser())) {
      return null;
    }

    $project = $event->getValue('object');
    $projectid = $project->getId();

    $view_uri = '/sprint/view/'.$projectid;
    $board_uri = '/sprint/board/'.$projectid;

    $burndown = id(new PhabricatorActionView())
        ->setIcon('fa-bar-chart-o')
        ->setName(pht('View Burndown'))
        ->setHref($view_uri);

    $board = id(new PhabricatorActionView())
        ->setIcon('fa-columns')
        ->setName(pht('View Sprint Board'))
        ->setHref($board_uri);

    return array ($burndown, $board);
  }



}
