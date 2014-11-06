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
      stripos($object->getName(), '§') !== false) {
      $actions = $this->renderUserItems($event);
    }

    $this->addActionMenuItems($event, $actions);
  }

  private function renderUserItems(PhutilEvent $event) {
    if (!$this->canUseApplication($event->getUser())) {
      return null;
    }

    $project = $event->getValue('object');

    $view_uri = '/sprint/view/'.$project->getId();

    return id(new PhabricatorActionView())
      ->setIcon('fa-bar-chart-o')
      ->setName(pht('View Burndown'))
      ->setHref($view_uri);
  }

}
