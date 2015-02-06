<?php

final class SprintIsSprintField extends SprintProjectCustomField {

  private $fieldproxy;

  public function __construct() {
    $this->fieldproxy = $this->getBoolFieldProxy($this, $this->getFieldName(),
        $this->getFieldDescription());
  }

  // == General field identity stuff
  public function getFieldKey() {
    return 'isdc:sprint:issprint';
  }

  public function getFieldName() {
    return 'Is Sprint';
  }

  public function getFieldDescription() {
    return 'Project Is Sprint';
  }

  public function renderPropertyViewValue(array $handles) {
    return $this->renderBoolProxyPropertyViewValue($this->fieldproxy,
        $handles);
  }

  public function renderEditControl(array $handles) {
    return $this->fieldproxy->renderEditControl($handles);
  }

  // == Search
  public function shouldAppearInApplicationSearch() {
    return true;
  }
}
