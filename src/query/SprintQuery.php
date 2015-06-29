<?php

final class SprintQuery extends SprintDAO {

  private $viewer;
  private $project;
  private $projectPHID;

  public function setProject ($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setPHID ($project_phid) {
    $this->projectPHID = $project_phid;
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
 //     $help = pht('To do this, go to the Project Edit Details Page');
 //     throw new BurndownException("The project \"".$this->project->getName()
 //         ."\" is not set up for Sprint because "
 //         ."it has not been assigned a start date\n", $help);
    return PhabricatorTime::getNow() - 1209600;
    } else {
      return $start;
    }
  }

  public function getEndDate($aux_fields) {
    $end = idx($aux_fields, 'isdc:sprint:enddate')
        ->getProxy()->getFieldValue();
    if (is_null($end)) {
//      $help = pht('To do this, go to the Project Edit Details Page');
//      throw new BurndownException("The project \"".$this->project->getName()
//          ."\" is not set up for Sprint because "
//          ."it has not been assigned an end date\n", $help);
      return PhabricatorTime::getNow() + 1209600;
    } else {
      return $end;
    }
  }

  public function getTasks() {
    $tasks = id(new ManiphestTaskQuery())
        ->setViewer($this->viewer)
        ->withEdgeLogicPHIDs(
            PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
            PhabricatorQueryConstraint::OPERATOR_OR,
            array($this->project->getPHID()))
        ->needProjectPHIDs(true)
        ->execute();
    if (empty($tasks)) {
      $help = pht('To Create a Task, go to the Sprint Board and select the '
      .'column header menu');
      throw new BurndownException("The project \"".$this->project->getName()
          ."\" is not set up for Sprint because "
          ."it has no tasks\n", $help);
    } else {
      return $tasks;
    }
  }

  public function getTasksforProject($project) {
    $tasks = id(new ManiphestTaskQuery())
        ->setViewer($this->viewer)
        ->withEdgeLogicPHIDs(
            PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
            PhabricatorQueryConstraint::OPERATOR_OR,
            array($project))
        ->needProjectPHIDs(true)
        ->execute();
    return $tasks;
  }

  public function getAllTasks() {
    $tasks = id(new ManiphestTaskQuery())
        ->setViewer($this->viewer)
        ->needProjectPHIDs(true)
        ->execute();
    return $tasks;
  }

  public function getStoryPointsForTask($task_phid) {
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
    $issprint = null;
    $object = new PhabricatorProjectCustomFieldStorage();
    $boolfield = $object->loadRawDataWhere('objectPHID= %s AND
    fieldIndex=%s', $this->projectPHID, SprintConstants::SPRINTFIELD_INDEX);
    if (!empty($boolfield)) {
      foreach ($boolfield as $array) {
        $issprint = idx($array, 'fieldValue');
      }
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
    if (empty($sprint_phids)) {
      $help = pht('To Create a Sprint, go to /project/create/ and make sure that'
          .' the "Is Sprint" box has been checked');
      throw new BurndownException("There are no Sprints to show yet\n", $help);
    } else {
      return $sprint_phids;
    }
  }

  public function getXactions($tasks) {
    $task_phids = mpull($tasks, 'getPHID');
    $xactions = id(new ManiphestTransactionQuery())
        ->setViewer($this->viewer)
        ->withObjectPHIDs($task_phids)
        ->execute();
    return $xactions;
  }

  public function getEdgeXactions($tasks) {
    $xactions = id(new ManiphestTransactionQuery())
        ->setViewer($this->viewer)
        ->withTransactionTypes(array(PhabricatorTransactions::TYPE_EDGE))
        ->withObjectPHIDs($tasks)
        ->execute();
    return $xactions;
  }

  public function getXactionsforProject($project_phid) {
    $xactions = id(new ManiphestTransactionQuery())
        ->setViewer($this->viewer)
        ->withObjectPHIDs($project_phid)
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
    if ($this->projectPHID) {
      $joins = qsprintf(
          $this->getXactionConn(),
          'JOIN %T t ON x.objectPHID = t.phid
          JOIN %T p ON p.src = t.phid AND p.type = %d AND p.dst = %s',
          id(new ManiphestTask())->getTableName(),
          PhabricatorEdgeConfig::TABLE_NAME_EDGE,
          PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
          $this->projectPHID);
    }
    return $joins;
  }

  public function getCustomFieldJoins() {

    $joins = '';
    if ($this->projectPHID) {
      $joins = qsprintf(
          $this->getCustomFieldConn(),
          'JOIN %T t ON f.objectPHID = t.phid
          JOIN %T p ON p.src = t.phid AND p.type = %d AND p.dst = %s',
          id(new ManiphestTask())->getTableName(),
          PhabricatorEdgeConfig::TABLE_NAME_EDGE,
          PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
          $this->projectPHID);
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
            ManiphestTaskDependedOnByTaskEdgeType::EDGECONST,
        ))
        ->execute();
    return $edges;
  }

  public function getEvents($xactions) {
    $events = $this->extractEvents($xactions);
    return $events;
  }

  public function getProjectColumns() {
    $columns = id(new PhabricatorProjectColumnQuery())
        ->setViewer($this->viewer)
        ->withProjectPHIDs(array($this->projectPHID))
        ->execute();
    if (!empty($columns)) {
      $columns = msort($columns, 'getSequence');
      return $columns;
    } else {
      $help = pht('To Create a Sprint Board, go to the Project profile page'
          .' and select the Sprint Board icon from the left side bar');
      throw new BurndownException("There is no Sprint Board yet\n", $help);
    }
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
            ->withBoardPHIDs(array($this->projectPHID))
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

  public function getProjectNamefromPHID ($phid) {
      $project = id(new PhabricatorProjectQuery())
          ->setViewer(PhabricatorUser::getOmnipotentUser())
          ->withPHIDs(array($phid))
          ->executeOne();
        $name = $project->getName();
      return $name;
  }

  public function getTaskNamefromPHID ($phid) {
    $task = id(new ManiphestTaskQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs(array($phid))
        ->executeOne();
    $name = $task->getMonogram();
    return $name;
  }

  public function extractEvents($xactions) {
    assert_instances_of($xactions, 'ManiphestTransaction');

    $events = array();
    foreach ($xactions as $xaction) {
        $events[] = array(
            'transactionPHID' => $xaction->getPHID(),
            'objectPHID' => $xaction->getObjectPHID(),
            'epoch' => $xaction->getDateCreated(),
            'key'   => $xaction->getMetadataValue('customfield:key'),
            'title' => $xaction->getTitle(),
        );
    }

    $events = isort($events, 'epoch');

    return $events;
  }

  public function getTaskHistory($project_phid) {
    $all_task_phids = null;
    $project_added_map = null;
    $project_removed_map = null;
    $task_added_proj_log = null;
    $task_removed_proj_log = null;

    if ($project_phid) {
      $tasks = $this->getTasksforProject($project_phid);

      if ($tasks) {
        foreach ($tasks as $task) {
          $all_task_phids[] = $task->getPHID();
        }
        $all_xactions = $this->getEdgeXactions($all_task_phids);

        foreach ($all_xactions as $xaction) {
          $new = $xaction->getNewValue();
          $old = $xaction->getOldValue();
          $oldtype = ipull($old, 'type');
          $newtype = ipull($new, 'type');
          $add_diff = array_diff_key($newtype, $oldtype);
          $rem_diff = array_diff_key($oldtype, $newtype);
          if (!empty($add_diff)) {
            foreach ($add_diff as $key => $value) {
              if ($value == '41') {
                $project_added_map[$key][] = $xaction;
              }
            }
          } else if (!empty($rem_diff)) {
            foreach ($rem_diff as $key => $value) {
              if ($value == '41') {
                $project_removed_map[$key][] = $xaction;
              }
            }
          } else {}
        }
      }

      if (!empty($project_added_map)) {
        $distinct_projects = array_unique($project_added_map, SORT_REGULAR);
        foreach ($distinct_projects as $project => $proj_xactions) {
          foreach ($proj_xactions as $proj_xaction) {
            $task_added_proj_log[] = array(
                'projectadded' => true,
                'projectremoved' => false,
                'projPHID' => $project,
                'projName' => $this->getProjectNamefromPHID($project),
                'transactionPHID' => $proj_xaction->getPHID(),
                'objectPHID' => $proj_xaction->getObjectPHID(),
                'taskName' => $this->getTaskNamefromPHID($proj_xaction->getObjectPHID()),
                'createdEpoch' => $proj_xaction->getDateCreated(),
            );
          }
        }
      }

      if (!empty($project_removed_map)) {
        $distinct_removed = array_unique($project_removed_map, SORT_REGULAR);
        foreach ($distinct_removed as $project => $proj_xactions) {
          foreach ($proj_xactions as $proj_xaction) {
            $task_removed_proj_log[] = array(
                'projectadded' => false,
                'projectremoved' => true,
                'projPHID' => $project,
                'projName' => $this->getProjectNamefromPHID($project),
                'transactionPHID' => $proj_xaction->getPHID(),
                'objectPHID' => $proj_xaction->getObjectPHID(),
                'taskName' => $this->getTaskNamefromPHID($proj_xaction->getObjectPHID()),
                'createdEpoch' => $proj_xaction->getDateCreated(),
            );
          }
        }
      }

      if (!empty($task_added_proj_log) && !empty($task_removed_proj_log)) {
        $task_proj_log = array_merge($task_added_proj_log, $task_removed_proj_log);
        return $task_proj_log;
      } else if (!empty($task_added_proj_log) && empty($task_removed_proj_log)) {
        return $task_added_proj_log;
      } else {}
    } else {
      return null;
    }
  }
}
