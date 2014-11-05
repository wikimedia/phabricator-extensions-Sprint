<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

final class SprintEndDateField extends SprintProjectCustomField {

  private $obj;
  private $date_proxy;

  public function __construct() {
    $this->obj = clone $this;
    $this->date_proxy = id(new PhabricatorStandardCustomFieldDate())
      ->setFieldKey($this->getFieldKey())
      ->setApplicationField($this->obj)
      ->setFieldConfig(array(
        'name' => $this->getFieldName(),
        'description' => $this->getFieldDescription()
      ));

    $this->setProxy($this->date_proxy);
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
    if (!$this->shouldShowSprintFields()) {
      return null;
    }

    if ($this->date_proxy->getFieldValue())
    {
      return parent::renderPropertyViewValue($handles);
    }
    return null;
  }

  public function renderEditControl(array $handles) {
    if (!$this->shouldShowSprintFields()) {
      return null;
    }
    if ($this->date_proxy) {
      return $this->newDateControl('end-of-business', $this->date_proxy);
    }
  }

  // == Search
  public function shouldAppearInApplicationSearch()
  {
    return true;
  }
}
