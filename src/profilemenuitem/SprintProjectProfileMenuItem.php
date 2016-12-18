<?php

final class SprintProjectProfileMenuItem
    extends PhabricatorProfileMenuItem {

  const MENUITEMKEY = 'project.sprint';

  public function getMenuItemTypeName() {
    return pht('Project Burndown');
  }

  private function getDefaultName() {
    return pht('Burndown');
  }

  public function shouldEnableForObject($object) {
    return true;
  }

  public function getDisplayName(
      PhabricatorProfileMenuItemConfiguration $config) {
    $name = $config->getMenuItemProperty('name');

    if (strlen($name)) {
      return $name;
    }

    return $this->getDefaultName();
  }

  public function buildEditEngineFields(
      PhabricatorProfileMenuItemConfiguration $config) {
    return array(
        id(new PhabricatorTextEditField())
            ->setKey('name')
            ->setLabel(pht('Name'))
            ->setPlaceholder($this->getDefaultName())
            ->setValue($config->getMenuItemProperty('name')),
    );
  }

  protected function newNavigationMenuItems(
      PhabricatorProfileMenuItemConfiguration $config) {

    $project = $config->getProfileObject();

    $has_children = ($project->getHasSubprojects()) ||
        ($project->getHasMilestones());

    $id = $project->getID();

    $name = $this->getDisplayName($config);
    $icon = 'fa-calendar';
    $href = "/project/sprint/view/{$id}/";

    $item = $this->newItem()
        ->setHref($href)
        ->setName($name)
        ->setDisabled(!$has_children)
        ->setIcon($icon);

    return array(
        $item,
    );
  }

}
