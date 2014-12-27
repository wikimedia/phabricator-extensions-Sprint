<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

final class SprintTaskStoryPointsField extends ManiphestCustomField
  implements
    PhabricatorStandardCustomFieldInterface {

  private $obj;
  private $text_proxy;

  public function __construct() {
    $this->obj = clone $this;
    $this->text_proxy = id(new PhabricatorStandardCustomFieldText())
      ->setFieldKey($this->getFieldKey())
      ->setApplicationField($this->obj)
      ->setFieldConfig(array(
        'name' => $this->getFieldName(),
        'description' => $this->getFieldDescription(),
      ));
    $this->setProxy($this->text_proxy);
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
          $project_phids = $task->getProjectPHIDs();
       }
     }

      if (empty($project_phids)) {
        return $show = false;
      }
      // Fetch the names from all the Projects associated with this task
      $projects = id(new PhabricatorProject())
        ->loadAllWhere(
        'phid IN (%Ls)',
        $project_phids);
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

    if ($this->text_proxy) {
      return $this->text_proxy->renderPropertyViewLabel();
    }
    return $this->getFieldName();
  }

  public function renderPropertyViewValue(array $handles) {
    if (!$this->showField()) {
      return null;
    }

    if ($this->text_proxy) {
      return $this->text_proxy->renderPropertyViewValue($handles);
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

    if ($this->text_proxy) {
      return $this->text_proxy->renderEditControl($handles);
    }
    throw new PhabricatorCustomFieldImplementationIncompleteException($this);
  }

  // == Search
  public function shouldAppearInApplicationSearch()
  {
    return true;
  }

}
