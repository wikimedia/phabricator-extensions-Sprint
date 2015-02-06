<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

final class SprintTaskStoryPointsField extends ManiphestCustomField
  implements
    PhabricatorStandardCustomFieldInterface {

  private $obj;
  private $textproxy;

  public function __construct() {
    $this->obj = clone $this;
    $this->textproxy = id(new PhabricatorStandardCustomFieldText())
      ->setFieldKey($this->getFieldKey())
      ->setApplicationField($this->obj)
      ->setFieldConfig(array(
        'name' => $this->getFieldName(),
        'description' => $this->getFieldDescription(),
      ));
    $this->setProxy($this->textproxy);
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

    $viewer = $this->getViewer();

    if ($show == null) {

     if ($this->getObject() instanceof ManiphestTask) {
       $id = $this->getObject()->getID();
       if ($id) {
          $task = id(new ManiphestTaskQuery())
             ->setViewer($viewer)
             ->withIds(array($id))
             ->needProjectPHIDs(true)
             ->executeOne();
          $projectphids = $task->getProjectPHIDs();
       }
     }

      if (empty($projectphids)) {
        return $show = false;
      }

      $show = false;
      foreach ($projectphids as $projectphid) {
        if ($this->isSprint($projectphid)) {
          $show = true;
          break;
        }
      }
    }
    return $show;
  }

  public function renderPropertyViewLabel() {
    if ($this->showField() === true) {
      if ($this->textproxy) {
        return $this->textproxy->renderPropertyViewLabel();
      }
      return $this->getFieldName();
    }
  }

  public function renderPropertyViewValue(array $handles) {
    if ($this->showField() === true) {
      if ($this->textproxy) {
        return $this->textproxy->renderPropertyViewValue($handles);
      }
      throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }
  }

  public function shouldAppearInEditView() {
    return true;
  }

  public function renderEditControl(array $handles) {
    if ($this->showField() === true) {
      if ($this->textproxy) {
        return $this->textproxy->renderEditControl($handles);
      }
      throw new PhabricatorCustomFieldImplementationIncompleteException($this);
    }
  }

  // == Search
  public function shouldAppearInApplicationSearch() {
    return true;
  }

  protected function isSprint($projectphid) {
    $validator = new SprintValidator();
    $issprint = call_user_func(array($validator, 'checkForSprint'),
        array($validator, 'isSprint'), $projectphid);
    return $issprint;
  }

}
