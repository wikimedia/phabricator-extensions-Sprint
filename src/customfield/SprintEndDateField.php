<?php
/**
 * @author Michael Peters
 * @author Christopher Johnson
 * @license GPL version 3
 */

final class SprintEndDateField extends SprintProjectCustomField {

  private $dateproxy;

  public function __construct() {
    $this->dateproxy = $this->getDateFieldProxy($this, $this->getFieldName(),
        $this->getFieldDescription());
  }

  // == General field identity stuff
  public function getFieldKey() {
    return 'isdc:sprint:enddate';
  }

  public function getFieldName() {
    return 'Sprint End Date';
  }

  public function getFieldDescription() {
    return 'When a sprint ends';
  }

  public function renderPropertyViewValue(array $handles) {
    return $this->renderDateProxyPropertyViewValue($this->dateproxy, $handles);
  }

  public function renderEditControl(array $handles) {
    return $this->renderDateProxyEditControl($this->dateproxy,
        'end-of-business');
  }

  // == Search
  public function shouldAppearInApplicationSearch() {
    return true;
  }
}
