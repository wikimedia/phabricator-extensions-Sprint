<?php

final class SprintUIEventListener
  extends PhabricatorEventListener {

  public function register() {
    $this->listen(PhabricatorEventType::TYPE_UI_WILLRENDERPROPERTIES);
  }

  public function handleEvent(PhutilEvent $event) {
    switch ($event->getType()) {
      case PhabricatorEventType::TYPE_UI_WILLRENDERPROPERTIES:
        $this->handlePropertyEvent($event);
        break;
    }
  }

  private function filterSprints ($phandles, $value){
    $handles = array();
    if(is_array($phandles) && count($phandles)>0)
    {
      foreach($phandles as $handle) {
        if (stripos($handle->getName(), $value) !== false) {
            $handles[$handle->getPHID()] = $phandles[$handle->getPHID()];
        }
      }
    }
    return $handles;
  }

  private function handlePropertyEvent($event) {
    $user = $event->getUser();
    $object = $event->getValue('object');

    if (!$object || !$object->getPHID()) {
      // No object, or the object has no PHID yet..
      return;
    }

    if (!($object instanceof PhabricatorProjectInterface)) {
      // This object doesn't have projects.
      return;
    }

    $project_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $object->getPHID(),
      PhabricatorProjectObjectHasProjectEdgeType::EDGECONST);
    if ($project_phids) {
      $project_phids = array_reverse($project_phids);
      $phandles = id(new PhabricatorHandleQuery())
        ->setViewer($user)
        ->withPHIDs($project_phids)
        ->execute();
     $handles = $this->filterSprints($phandles, SprintConstants::MAGIC_WORD);
    } else {
      $handles = array();
    }

    // If this object can appear on boards, build the workboard annotations.
    // Some day, this might be a generic interface. For now, only tasks can
    // appear on boards.
    $can_appear_on_boards = ($object instanceof ManiphestTask);

    $annotations = array();
    if ($handles && $can_appear_on_boards) {

      // TDOO: Generalize this UI and move it out of Maniphest.

      require_celerity_resource('maniphest-task-summary-css');

      $positions = id(new PhabricatorProjectColumnPositionQuery())
        ->setViewer($user)
        ->withBoardPHIDs($project_phids)
        ->withObjectPHIDs(array($object->getPHID()))
        ->needColumns(true)
        ->execute();
      $positions = mpull($positions, null, 'getBoardPHID');

      foreach ($handles as $handle) {
        $project_phid = $handle->getPHID();

        $position = idx($positions, $project_phid);
          if ($position) {
            $column = $position->getColumn();

            $column_name = pht('(%s)', $column->getDisplayName());
            $column_link = phutil_tag(
                'a',
                array(
                    'href' => $handle->getURI().'sboard/',
                    'class' => 'maniphest-board-link',
                ),
                $column_name);

            $annotations[$project_phid] = array(
                ' ',
                $column_link);
          }
        }
      }

    if ($handles) {
      $list = id(new PHUIHandleTagListView())
        ->setHandles($handles)
        ->setAnnotations($annotations);
    } else {
      $list = phutil_tag('em', array(), pht('None'));
    }

    $view = $event->getValue('view');
    $view->addProperty(pht('Sprint'), $list);
  }

}
