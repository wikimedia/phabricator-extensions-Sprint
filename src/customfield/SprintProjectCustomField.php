<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

abstract class SprintProjectCustomField extends PhabricatorProjectCustomField
  implements PhabricatorStandardCustomFieldInterface {


  protected function isSprint() {
    $validator = new SprintValidator;
    $issprint = call_user_func(array($validator, 'checkForSprint'),
        array($validator, 'isSprint'), $this->getObject()->getPHID());
    return $issprint;
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
  public function getDateFieldProxy($datefield, $name, $description) {
    $obj = clone $datefield;
    $dateproxy = id(new PhabricatorStandardCustomFieldDate())
        ->setFieldKey($this->getFieldKey())
        ->setApplicationField($obj)
        ->setFieldConfig(array(
            'name' => $name,
            'description' => $description
        ));
    $this->setProxy($dateproxy);
    return $dateproxy;
  }

  /**
   * @param string $name
   * @param string $description
   */
  public function getBoolFieldProxy($field, $name, $description) {
    $obj = clone $field;
    $fieldproxy = id(new PhabricatorStandardCustomFieldBool())
        ->setFieldKey($this->getFieldKey())
        ->setApplicationField($obj)
        ->setFieldConfig(array(
            'name' => $name,
            'description' => $description,
        ));
    $this->setProxy($fieldproxy);
    return $fieldproxy;
  }

  public function renderBoolProxyPropertyViewValue($boolproxy, $handles) {
      return $boolproxy->renderPropertyViewValue($handles);
  }

  public function renderDateProxyPropertyViewValue($dateproxy, $handles) {
    $issprint = $this->isSprint();

    if ($issprint && ($dateproxy->getFieldValue())) {
        return $dateproxy->renderPropertyViewValue($handles);
    } else {
       return null;
    }
  }

  /**
   * @param string $time
   */
  public function renderDateProxyEditControl($dateproxy, $time) {
    $issprint = $this->isSprint();

    if ($issprint && $dateproxy) {
        return $this->newDateControl($dateproxy, $time);
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
