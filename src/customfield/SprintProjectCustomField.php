<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

abstract class SprintProjectCustomField extends PhabricatorProjectCustomField
  implements PhabricatorStandardCustomFieldInterface {

  /**
   * Use this function to determine whether to show sprint fields
   *
   *    public function renderPropertyViewValue(array $handles) {
   *      if (!$this->shouldShowSprintFields()) {
   *        return
   *      }
   *      // Actually show something
   *
   * NOTE: You can NOT call this in functions like "shouldAppearInEditView" because
   * $this->getObject() is not available yet.
   *
   */
  protected function shouldShowSprintFields()
  {
    if ($this->getObject() instanceof PhabricatorProject) {
      return (strpos($this->getObject()->getName(), SprintConstants::MAGIC_WORD) !== FALSE);
    }
  }

  /**
   * Required in order to implement PhabricatorStandardCustomFieldInterface
   */
  public function getStandardCustomFieldNamespace() {
    return 'project';
  }

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

  public function renderChildPropertyViewValue($date_proxy, $handles) {
    if (!$this->shouldShowSprintFields()) {
      return null;
    }
    if ($date_proxy->getFieldValue())
    {
      return $date_proxy->renderPropertyViewValue($handles);
    }
    return null;
  }

  public function renderChildEditControl($date_proxy, $time) {
    if (!$this->shouldShowSprintFields()) {
      return null;
    }
    if ($date_proxy) {
      return $this->newDateControl($date_proxy, $time);
    }
   }

  public function renderPropertyViewLabel() {
    if ($this->getProxy()) {
      return $this->getProxy()->renderPropertyViewLabel();
    }
    return $this->getFieldName();

  }

  public function renderPropertyViewValue(array $handles) {
    if ($this->getProxy()) {
      return $this->getProxy()->renderPropertyViewValue($handles);
    }
    throw new PhabricatorCustomFieldImplementationIncompleteException($this);
  }

  // == Edit View
  public function shouldAppearInEditView() {
    return true;
  }

  public function renderEditControl(array $handles) {
    if ($this->getProxy()) {
      return $this->getProxy()->renderEditControl($handles);
    }
    throw new PhabricatorCustomFieldImplementationIncompleteException($this);
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
