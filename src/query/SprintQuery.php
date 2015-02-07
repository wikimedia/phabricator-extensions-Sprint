<?php

final class SprintQuery extends SprintDAO {

  private $viewer;
  private $project;
  private $project_phid;

  public function setProject ($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setPHID ($project_phid) {
    $this->project_phid = $project_phid;
    return $this;
  }

  public function getViewerHandles($request, array $phids) {
    return id(new PhabricatorHandleQuery())
        ->setViewer($request->getUser())
        ->withPHIDs($phids)
        ->execute();
  }

  public function getCustomFieldList() {
    $field_list = PhabricatorCustomField::getObjectFields($this->project,
        PhabricatorCustomField::ROLE_EDIT);
    $field_list->setViewer($this->viewer);
    $field_list->readFieldsFromStorage($this->project);
    return $field_list;
  }

  public function getAuxFields($field_list) {
    $aux_fields = $field_list->getFields();
    return $aux_fields;
  }

  public function getStartDate($aux_fields) {
      $start = idx($aux_fields, 'isdc:sprint:startdate')
          ->getProxy()->getFieldValue();
    if (is_null($start)) {
      $help = 'To do this, go to the Project Edit Details Page';
      throw new BurndownException("The project \"".$this->project->getName()
          ."\" is not set up for Sprint because "
          ."it has not been assigned a start date\n", $help);
    } else {
      return $start;
    }
  }

  public function getEndDate($aux_fields) {
    $end = idx($aux_fields, 'isdc:sprint:enddate')
        ->getProxy()->getFieldValue();
    if (is_null($end)) {
      $help = 'To do this, go to the Project Edit Details Page';
      throw new BurndownException("The project \"".$this->project->getName()
          ."\" is not set up for Sprint because "
          ."it has not been assigned an end date\n", $help);
    } else {
      return $end;
    }
  }

  public function getTasks() {
    $tasks = id(new ManiphestTaskQuery())
        ->setViewer($this->viewer)
        ->withAnyProjects(array($this->project->getPHID()))
        ->needProjectPHIDs(true)
        ->execute();
    if (empty($tasks)) {
      $help = "To Create a Task, go to the Sprint Board and select the "
      ."column header menu";
      throw new BurndownException("The project \"".$this->project->getName()
          ."\" is not set up for Sprint because "
          ."it has no tasks\n", $help);
    } else {
      return $tasks;
    }
  }

  public function getStoryPointsForTask($task_phid)  {
    $points = null;
    $object = new ManiphestCustomFieldStorage();
    $corecustomfield = $object->loadRawDataWhere('objectPHID= %s AND
    fieldIndex=%s', $task_phid, SprintConstants::POINTFIELD_INDEX);
    if (!empty($corecustomfield)) {
      foreach ($corecustomfield as $array) {
        $points = idx($array, 'fieldValue');
      }
    } else {
      $points = 0;
    }
    return $points;
  }

  public function getIsSprint() {
    $object = new PhabricatorProjectCustomFieldStorage();
    $boolfield = $object->loadRawDataWhere('objectPHID= %s AND
    fieldIndex=%s', $this->project_phid, SprintConstants::SPRINTFIELD_INDEX);
    if (!empty($boolfield)) {
      foreach ($boolfield as $array) {
        $issprint = idx($array, 'fieldValue');
      }
    } else {
      $issprint = null;
    }
    return $issprint;
  }

  public function getSprintPHIDs() {
    $sprint_phids = array();
    $object = new PhabricatorProjectCustomFieldStorage();
    $data = $object->loadRawDataWhere('fieldValue= %s AND
    fieldIndex=%s', true, SprintConstants::SPRINTFIELD_INDEX);
    $sprintfields = $object->loadAllFromArray($data);
    foreach ($sprintfields as $key => $value) {
        $sprint_phids[] = $value->getObjectPHID();
      }
    return $sprint_phids;
  }

  public function getXactions($tasks) {
    $task_phids = mpull($tasks, 'getPHID');
    $xactions = id(new ManiphestTransactionQuery())
        ->setViewer($this->viewer)
        ->withObjectPHIDs($task_phids)
        ->execute();
    return $xactions;
  }

  public function getXActionObj () {
    $table = new ManiphestTransaction();
    return $table;
  }

  public function getXActionConn () {
    $conn = $this->getXActionObj()->establishConnection('r');
    return $conn;
  }

  public function getCustomFieldObj () {
    $table = new ManiphestCustomFieldStorage();
    return $table;
  }

  public function getCustomFieldConn () {
    $conn = $this->getCustomFieldObj()->establishConnection('r');
    return $conn;
  }

  public function getJoins() {

    $joins = '';
    if ($this->project_phid) {
      $joins = qsprintf(
          $this->getXactionConn(),
          'JOIN %T t ON x.objectPHID = t.phid
          JOIN %T p ON p.src = t.phid AND p.type = %d AND p.dst = %s',
          id(new ManiphestTask())->getTableName(),
          PhabricatorEdgeConfig::TABLE_NAME_EDGE,
          PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
          $this->project_phid);
    }
    return $joins;
  }

  public function getCustomFieldJoins() {

    $joins = '';
    if ($this->project_phid) {
      $joins = qsprintf(
          $this->getCustomFieldConn(),
          'JOIN %T t ON f.objectPHID = t.phid
          JOIN %T p ON p.src = t.phid AND p.type = %d AND p.dst = %s',
          id(new ManiphestTask())->getTableName(),
          PhabricatorEdgeConfig::TABLE_NAME_EDGE,
          PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
          $this->project_phid);
    }
    return $joins;
  }

  public function getXactionData($where) {
    $data = queryfx_all(
        $this->getXactionConn(),
        'SELECT x.objectPHID, x.oldValue, x.newValue, x.dateCreated FROM %T x %Q
        WHERE transactionType = %s
        ORDER BY x.dateCreated ASC',
        $this->getXActionObj()->getTableName(),
        $this->getJoins(),
        $where);
    return $data;
 }

  public function getTaskData() {
    $task_dao = new ManiphestCustomFieldStorage();
    $data = queryfx_all(
        $this->getCustomFieldConn(),
        'SELECT f.* FROM %T f %Q
        WHERE fieldIndex = %s',
        $this->getCustomFieldObj()->getTableName(),
        $this->getCustomFieldJoins(),
        SprintConstants::POINTFIELD_INDEX);

    $task_data = $task_dao->loadAllFromArray($data);
    return $task_data;
  }

  public function getEdges ($tasks) {
    // Load all edges of depends and depended on tasks
    $edges = id(new PhabricatorEdgeQuery())
        ->withSourcePHIDs(array_keys($tasks))
        ->withEdgeTypes(array( ManiphestTaskDependsOnTaskEdgeType::EDGECONST,
            ManiphestTaskDependedOnByTaskEdgeType::EDGECONST))
        ->execute();
    return $edges;
  }

  public function getEvents($xactions) {
    $scope_phid = $this->project->getPHID();
    $events = $this->extractEvents($xactions, $scope_phid);
    return $events;
  }

  public function getProjectColumns() {
    $columns = id(new PhabricatorProjectColumnQuery())
        ->setViewer($this->viewer)
        ->withProjectPHIDs(array($this->project_phid))
        ->execute();
    $columns = msort($columns, 'getSequence');
    return $columns;
  }

  public function getColumnforPHID($column_phid) {
    $column = id(new PhabricatorProjectColumnQuery())
        ->setViewer($this->viewer)
        ->withPHIDs(array($column_phid))
        ->execute();
    return $column;
  }

  public function getProjectColumnPositionforTask($tasks, $columns) {
    if ($tasks) {
        $positions = id(new PhabricatorProjectColumnPositionQuery())
            ->setViewer($this->viewer)
            ->withBoardPHIDs(array($this->project_phid))
            ->withObjectPHIDs(mpull($tasks, 'getPHID'))
            ->withColumns($columns)
            ->needColumns(true)
            ->execute();
        $positions = mpull($positions, null, 'getObjectPHID');
     } else {
        $positions = array();
    }
    return $positions;
  }

  private function setXActionEventType ($xaction, $old, $new, $scope_phid) {
    switch ($xaction->getTransactionType()) {
      case ManiphestTransaction::TYPE_STATUS:
        $old_is_closed = ($old === null) ||
            ManiphestTaskStatus::isClosedStatus($old);
        $new_is_closed = ManiphestTaskStatus::isClosedStatus($new);

        if ($old_is_closed == $new_is_closed) {
          // This was just a status change from one open status to another,
          // or from one closed status to another, so it's not an events we
          // care about.
          break;
        }
        if ($old === null) {
          // This would show as "reopened" even though it's when the task was
          // created so we skip it. Instead we will use the title for created
          // events
          break;
        }

        if ($new_is_closed) {
          return 'close';
        } else {
          return 'reopen';
        }

      case ManiphestTransaction::TYPE_TITLE:
        if ($old === null)
        {
          return 'create';
        }
        break;

      // Project changes are "core:edge" transactions
      case PhabricatorTransactions::TYPE_EDGE:

        // We only care about ProjectEdgeType
        if (idx($xaction->getMetadata(), 'edge:type') !==
            PhabricatorProjectObjectHasProjectEdgeType::EDGECONST)
          break;

        $old = ipull($old, 'dst');
        $new = ipull($new, 'dst');

        $in_old_scope = array_key_exists($scope_phid, $old);
        $in_new_scope = array_key_exists($scope_phid, $new);

        if ($in_new_scope ) {
          return 'task-add';
        } else if ($in_old_scope && !$in_new_scope) {
          // NOTE: We will miss some of these events, becuase we are only
          // examining tasks that are currently in the project. If a task
          // is removed from the project and not added again later, it will
          // just vanish from the chart completely, not show up as a
          // scope contraction. We can't do better until the Facts application
          // is available without examining *every* task.
          return 'task-remove';
        }
        break;

      case PhabricatorTransactions::TYPE_CUSTOMFIELD:
        if ($xaction->getMetadataValue('customfield:key') == 'isdc:sprint:storypoints') {
          // POINTS!
          return 'points';
        }
        break;

      default:
        // This is something else (comment, subscription change, etc) that
        // we don't care about for now.
        break;
    }
  }

  public function extractEvents($xactions, $scope_phid) {
    assert_instances_of($xactions, 'ManiphestTransaction');

    $events = array();
    foreach ($xactions as $xaction) {
      $old = $xaction->getOldValue();
      $new = $xaction->getNewValue();

      $event_type = $this->setXActionEventType ($xaction, $old, $new, $scope_phid);

      if ($event_type !== null) {
        $events[] = array(
            'transactionPHID' => $xaction->getPHID(),
            'objectPHID' => $xaction->getObjectPHID(),
            'epoch' => $xaction->getDateCreated(),
            'key'   => $xaction->getMetadataValue('customfield:key'),
            'type'  => $event_type,
            'title' => $xaction->getTitle(),
        );
      }
    }

    $events = isort($events, 'epoch');

    return $events;
  }
}

