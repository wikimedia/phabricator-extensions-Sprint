<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

abstract class SprintProjectCustomField extends PhabricatorProjectCustomField
  implements PhabricatorStandardCustomFieldInterface {


  protected function isSprint() {
    $validator = new SprintValidator;
    $is_sprint = call_user_func(array($validator, 'checkForSprint'),
        array($validator, 'shouldShowSprintFields'), $this->getObject());
    return $is_sprint;
  }

  /**
   * Required in order to implement PhabricatorStandardCustomFieldInterface
   */
  public function getStandardCustomFieldNamespace() {
    return 'project';
  }

  /**
   * @param string $name
   * @param string $description
   */
  public function getDateFieldProxy($date_field, $name, $description) {
    $obj = clone $date_field;
    $date_proxy = id(new PhabricatorStandardCustomFieldDate())
        ->setFieldKey($this->getFieldKey())
        ->setApplicationField($obj)
        ->setFieldConfig(array(
            'name' => $name,
            'description' => $description
        ));
    $this->setProxy($date_proxy);
    return $date_proxy;
  }

  public function renderDateProxyPropertyViewValue($date_proxy, $handles) {
    $is_sprint = $this->isSprint();

    if ($is_sprint && ($date_proxy->getFieldValue())) {
        return $date_proxy->renderPropertyViewValue($handles);
    } else {
       return null;
    }
  }

  /**
   * @param string $time
   */
  public function renderDateProxyEditControl($date_proxy, $time) {
    $is_sprint = $this->isSprint();

    if ($is_sprint && $date_proxy) {
        return $this->newDateControl($date_proxy, $time);
    } else {
      return null;
    }
   }

  public function renderPropertyViewLabel() {
    if ($this->getProxy()) {
      return $this->getProxy()->renderPropertyViewLabel();
    }
    return $this->getFieldName();
  }

  public function renderPropertyViewValue(array $handles) {
      return $this->getProxy()->renderPropertyViewValue($handles);
  }

  // == Edit View
  public function shouldAppearInEditView() {
    return true;
  }

  public function renderEditControl(array $handles) {
      return $this->getProxy()->renderEditControl($handles);
  }

  public function newDateControl($proxy, $time) {
    $control = id(new AphrontFormDateControl())
        ->setLabel($proxy->getFieldName())
        ->setName($proxy->getFieldKey())
        ->setUser($proxy->getViewer())
        ->setCaption($proxy->getCaption())
        ->setAllowNull(!$proxy->getRequired())
        ->setInitialTime($time);

    $value = $proxy->getFieldValue();
    if (!ctype_digit($value)) {
      $value = PhabricatorTime::parseLocalTime($value, $proxy->getViewer());
    }

    $control->setValue(nonempty($value, null));

    return $control;
  }
}
