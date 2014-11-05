<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

final class SprintStartDateField extends SprintProjectCustomField {

  private $obj;
  private $proxy;

  public function __construct() {
    $this->obj = clone $this;
    $this->proxy = id(new PhabricatorStandardCustomFieldDate())
      ->setFieldKey($this->getFieldKey())
      ->setApplicationField($this->obj)
      ->setFieldConfig(array(
        'name' => $this->getFieldName(),
        'description' => $this->getFieldDescription(),
      ));

    $this->setProxy($this->proxy);
  }

  // == General field identity stuff
  public function getFieldKey() {
    return 'isdc:sprint:startdate';
  }

  public function getFieldName() {
    return 'Sprint Start Date';
  }

  public function getFieldDescription() {
    return 'When a sprint starts';
  }

  public function renderPropertyViewValue(array $handles) {
    if (!$this->shouldShowSprintFields()) {
      return null;
    }

    if ($this->proxy->getFieldValue())
    {
      return parent::renderPropertyViewValue($handles);
    }
    return null;
  }

  public function renderEditControl(array $handles) {
    if (!$this->shouldShowSprintFields()) {
      return null;
    }
    if ($this->proxy) {
      return $this->newDateControl('start-of-business', $this->proxy);
    }
  }

  // == Search
  public function shouldAppearInApplicationSearch() {
    return true;
  }
}
