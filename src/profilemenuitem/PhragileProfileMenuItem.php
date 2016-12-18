<?php

final class PhragileProfileMenuItem
    extends PhabricatorProfileMenuItem {

  const MENUITEMKEY = 'project.phragile';

  public function getMenuItemTypeName() {
    return pht('Phragile');
  }

  private function getDefaultName() {
    return pht('Phragile');
  }

  public function shouldEnableForObject($object) {
    $enable_phragile = PhabricatorEnv::getEnvConfig('sprint.enable-phragile');

    if ($enable_phragile) {
      return true;
    }
    return false;
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
    $icon = 'fa-link';
    $phragile_base_uri = PhabricatorEnv::getEnvConfig('sprint.phragile-uri');
    $href = $phragile_base_uri.$id;

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
