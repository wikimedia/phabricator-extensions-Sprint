<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

final class SprintEndDateField extends SprintProjectCustomField {

  private $date_proxy;

  public function __construct() {
    $this->date_proxy = $this->getDateFieldProxy($this, $this->getFieldName(), $this->getFieldDescription());
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
    return $this->renderDateProxyPropertyViewValue($this->date_proxy, $handles);
  }

  public function renderEditControl(array $handles) {
    return $this->renderDateProxyEditControl($this->date_proxy,'end-of-business');
  }

  // == Search
  public function shouldAppearInApplicationSearch()
  {
    return true;
  }
}
