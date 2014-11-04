<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

final class SprintTaskStoryPointsField extends ManiphestCustomField
  implements PhabricatorStandardCustomFieldInterface {

  private $obj;
  private $proxy;

  public function __construct() {
    $this->obj = clone $this;
    $this->proxy = id(new PhabricatorStandardCustomFieldText())
      ->setFieldKey($this->getFieldKey())
      ->setApplicationField($this->obj)
      ->setFieldConfig(array(
        'name' => $this->getFieldName(),
        'description' => $this->getFieldDescription(),
      ));

    $this->setProxy($this->proxy);
  }

  public function canSetProxy() {
    return true;
  }

  public function getFieldKey() {
    return 'isdc:sprint:storypoints';
  }

  public function getFieldName() {
    return 'Story Points';
  }

  public function getFieldDescription() {
    return 'Estimated story points for this task';
  }

  public function getStandardCustomFieldNamespace() {
    return 'maniphest';
  }

  public function showField() {
    static $show = null;

    if ($show == null)
    {
      $toTest = $this->getObject()->getProjectPHIDs();
      if (empty($toTest)) {
        return $show = false;
      }
      // Fetch the names from all the Projects associated with this task
      $projects = id(new PhabricatorProject())
        ->loadAllWhere(
        'phid IN (%Ls)',
        $this->getObject()->getProjectPHIDs());
      $names = mpull($projects, 'getName');

      // Set show to true if one of the Projects contains "Sprint"
      $show = false;
      foreach($names as $name) {
        if (strpos($name, SprintConstants::MAGIC_WORD) !== false) {
          $show = true;
        }
      }
    }

    return $show;
  }

  public function renderPropertyViewLabel() {
    if (!$this->showField()) {
      return null;
    }

    if ($this->getProxy()) {
      return $this->getProxy()->renderPropertyViewLabel();
    }
    return $this->getFieldName();
  }

  public function renderPropertyViewValue(array $handles) {
    if (!$this->showField()) {
      return null;
    }

    if ($this->getProxy()) {
      return $this->getProxy()->renderPropertyViewValue($handles);
    }
    throw new PhabricatorCustomFieldImplementationIncompleteException($this);
  }

  public function shouldAppearInEditView() {
    return true;
  }

  public function renderEditControl(array $handles) {
    if (!$this->showField()) {
      return null;
    }

    if ($this->getProxy()) {
      return $this->getProxy()->renderEditControl($handles);
    }
    throw new PhabricatorCustomFieldImplementationIncompleteException($this);
  }

  // == Search
  public function shouldAppearInApplicationSearch()
  {
    return true;
  }

}
