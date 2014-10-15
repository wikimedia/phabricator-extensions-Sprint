<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

final class SprintEndDateField extends SprintProjectCustomField {

  public function __construct() {
    $proxy = id(new PhabricatorStandardCustomFieldDate())
      ->setFieldKey($this->getFieldKey())
      ->setApplicationField($this)
      ->setFieldConfig(array(
        'name' => $this->getFieldName(),
        'description' => $this->getFieldDescription(),
      ));

    $this->setProxy($proxy);
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

    if ($this->getProxy()->getFieldValue())
    {
      return parent::renderPropertyViewValue($handles);
    }

    return null;
  }

  // == Search
  public function shouldAppearInApplicationSearch()
  {
    return true;
  }

}
