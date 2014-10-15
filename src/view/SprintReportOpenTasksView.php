<?php

final class SprintReportOpenTasksView extends SprintView {

    protected $user;
    private $request;
    private $view;

  public function setUser ($user) {
    $this->user = $user;
    return $this;
  }

  public function setRequest ($request) {
    $this->request = $request;
    return $this;
  }

  public function setView ($view) {
    $this->view = $view;
    return $this;
  }

  public function getOpenStatusList() {
    $open_status_list = array();

    foreach (ManiphestTaskStatus::getOpenStatusConstants() as $constant) {
      $open_status_list[] = json_encode((string)$constant);
    }
  return $open_status_list;
  }

  protected function loadViewerHandles(array $phids) {
    return id(new PhabricatorHandleQuery())
        ->setViewer($this->request->getUser())
        ->withPHIDs($phids)
        ->execute();
  }

  public function render() {

    $project_phid = $this->request->getStr('project');
    $project_handle = null;

    if ($project_phid) {
      $phids = array($project_phid);
      $project_handle = $this->getProjectHandle($phids, $project_phid);
      $tasks = $this->getOpenTasksforProject($this->user, $phids);
    } else {
      $tasks = $this->getOpenTasks($this->user);
    }
    $recently_closed = $this->loadRecentlyClosedTasks();

    $date = phabricator_date(time(), $this->user);

    switch ($this->view) {
      case 'user':
        $result = mgroup($tasks, 'getOwnerPHID');
        $leftover = idx($result, '', array());
        unset($result['']);

        $result_closed = mgroup($recently_closed, 'getOwnerPHID');
        $leftover_closed = idx($result_closed, '', array());
        unset($result_closed['']);

        $base_link = '/maniphest/?assigned=';
        $leftover_name = phutil_tag('em', array(), pht('(Up For Grabs)'));
        $col_header = pht('User');
        $header = pht('Open Tasks by User and Priority (%s)', $date);
        break;
      case 'project':
        $result = array();
        $leftover = array();
        foreach ($tasks as $task) {
          $phids = $task->getProjectPHIDs();
          if ($phids) {
            foreach ($phids as $project_phid) {
              $result[$project_phid][] = $task;
            }
          } else {
            $leftover[] = $task;
          }
        }

        $result_closed = array();
        $leftover_closed = array();
        foreach ($recently_closed as $task) {
          $phids = $task->getProjectPHIDs();
          if ($phids) {
            foreach ($phids as $project_phid) {
              $result_closed[$project_phid][] = $task;
            }
          } else {
            $leftover_closed[] = $task;
          }
        }

        $base_link = '/maniphest/?allProjects=';
        $leftover_name = phutil_tag('em', array(), pht('(No Project)'));
        $col_header = pht('Project');
        $header = pht('Open Tasks by Project and Priority (%s)', $date);
        break;
      default:
        $result = array();
        $result_closed = array();
        $base_link = null;
        $leftover = array();
        $leftover_closed = array();
        $leftover_name = null;
        $col_header = '';
        $header = '';
        break;
    }

    $phids = array_keys($result);
    $handles = $this->loadViewerHandles($phids);
    $handles = msort($handles, 'getName');

    $order = $this->request->getStr('order', 'name');
    list($order, $reverse) = AphrontTableView::parseSort($order);

    require_celerity_resource('aphront-tooltip-css');
    Javelin::initBehavior('phabricator-tooltips', array());

    $rows = array();

    foreach ($handles as $handle) {
      if ($handle) {
        if (($project_handle) &&
            ($project_handle->getPHID() == $handle->getPHID())) {
          $tasks = idx($result, $handle->getPHID(), array());
          $closed = idx($result_closed, $handle->getPHID(), array());
        } else {
          $tasks = idx($result, $handle->getPHID(), array());
          $closed = idx($result_closed, $handle->getPHID(), array());
        }

        $name = phutil_tag(
            'a',
            array(
                'href' => $base_link.$handle->getPHID(),
            ),
            $handle->getName());

      } else {
        $tasks = $leftover;
        $name  = $leftover_name;
        $closed = $leftover_closed;
      }

      $taskv = $tasks;
      $tasks = mgroup($tasks, 'getPriority');

      $row = array();
      $row[] = $name;
      $total = 0;
      foreach (ManiphestTaskPriority::getTaskPriorityMap() as $pri => $label) {
        $n = count(idx($tasks, $pri, array()));
        if ($n == 0) {
          $row[] = '-';
        } else {
          $row[] = number_format($n);
        }
        $total += $n;
      }
      $row[] = number_format($total);

      list($link, $oldest_all) = $this->renderOldest($taskv);
      $row[] = $link;

      $normal_or_better = array();
      foreach ($taskv as $id => $task) {
        // TODO: This is sort of a hard-code for the default "normal" status.
        // When reports are more powerful, this should be made more general.
        if ($task->getPriority() < 50) {
          continue;
        }
        $normal_or_better[$id] = $task;
      }

      list($link, $oldest_pri) = $this->renderOldest($normal_or_better);
      $row[] = $link;

      if ($closed) {
        $task_ids = implode(',', mpull($closed, 'getID'));
        $row[] = phutil_tag(
            'a',
            array(
                'href' => '/maniphest/?ids='.$task_ids,
                'target' => '_blank',
            ),
            number_format(count($closed)));
      } else {
        $row[] = '-';
      }

      switch ($order) {
        case 'total':
          $row['sort'] = $total;
          break;
        case 'oldest-all':
          $row['sort'] = $oldest_all;
          break;
        case 'oldest-pri':
          $row['sort'] = $oldest_pri;
          break;
        case 'closed':
          $row['sort'] = count($closed);
          break;
        case 'name':
        default:
          $row['sort'] = $handle ? $handle->getName() : '~';
          break;
      }

      $rows[] = $row;
    }

    $rows = isort($rows, 'sort');
    foreach ($rows as $k => $row) {
      unset($rows[$k]['sort']);
    }
    if ($reverse) {
      $rows = array_reverse($rows);
    }

    $cname = array($col_header);
    $cclass = array('pri left narrow');
    $pri_map = ManiphestTaskPriority::getShortNameMap();
    foreach ($pri_map as $pri => $label) {
      $cname[] = $label;
      $cclass[] = 'center narrow';
    }
    $cname[] = 'Total';
    $cclass[] = 'center narrow';
    $cname[] = javelin_tag(
        'span',
        array(
            'sigil' => 'has-tooltip',
            'meta'  => array(
                'tip' => pht('Oldest open task.'),
                'size' => 200,
            ),
        ),
        pht('Oldest (All)'));
    $cclass[] = 'center narrow';
    $cname[] = javelin_tag(
        'span',
        array(
            'sigil' => 'has-tooltip',
            'meta'  => array(
                'tip' => pht('Oldest open task, excluding those with Low or '.
                    'Wishlist priority.'),
                'size' => 200,
            ),
        ),
        pht('Oldest (Pri)'));
    $cclass[] = 'center narrow';

    list($window_epoch) = $this->getWindow();
    $edate = phabricator_datetime($window_epoch, $this->user);
    $cname[] = javelin_tag(
        'span',
        array(
            'sigil' => 'has-tooltip',
            'meta'  => array(
                'tip'  => pht('Closed after %s', $edate),
                'size' => 260
            ),
        ),
        pht('Recently Closed'));
    $cclass[] = 'center narrow';

    $table = new AphrontTableView($rows);
    $table->setHeaders($cname);
    $table->setColumnClasses($cclass);
    $table->makeSortable(
        $this->request->getRequestURI(),
        'order',
        $order,
        $reverse,
        array(
            'name',
            null,
            null,
            null,
            null,
            null,
            null,
            'total',
            'oldest-all',
            'oldest-pri',
            'closed',
        ));

    $panel = new PHUIObjectBoxView();
    $panel->setHeaderText($header);
    $panel->appendChild($table);

    $tokens = array();
    if ($project_handle) {
      $tokens = array($project_handle);
    }
    $filter = parent::renderReportFilters($tokens);

    return array($filter, $panel);
  }


  /**
   * Load all the tasks that have been recently closed.
   */
  private function loadRecentlyClosedTasks() {

    list(,, $window_epoch) = $this->getWindow();

    $table = new ManiphestTask();
    $xtable = new ManiphestTransaction();
    $conn_r = $table->establishConnection('r');

    // TODO: Gross. This table is not meant to be queried like this. Build
    // real stats tables.

    $rows = queryfx_all(
        $conn_r,
        'SELECT t.id FROM %T t JOIN %T x ON x.objectPHID = t.phid
        WHERE t.status NOT IN (%Ls)
        AND x.oldValue IN (null, %Ls)
        AND x.newValue NOT IN (%Ls)
        AND t.dateModified >= %d
        AND x.dateCreated >= %d',
        $table->getTableName(),
        $xtable->getTableName(),
        ManiphestTaskStatus::getOpenStatusConstants(),
        $this->getOpenStatusList(),
        $this->getOpenStatusList(),
        $window_epoch,
        $window_epoch);

    if (!$rows) {
      return array();
    }

    $ids = ipull($rows, 'id');

    return id(new ManiphestTaskQuery())
        ->setViewer($this->request->getUser())
        ->withIDs($ids)
        ->execute();
  }

  private function getOpenTasks($user) {

    $query = $this->openStatusQuery($user);
    $tasks = $query->execute();
    return $tasks;
  }

  private function getOpenTasksforProject($user, $phids) {

    $query = $this->openStatusQuery($user)->withAnyProjects($phids);
    $tasks = $query->execute();
    return $tasks;
  }

  private function openStatusQuery($user) {
    $query = id(new ManiphestTaskQuery())
        ->setViewer($user)
        ->withStatuses(ManiphestTaskStatus::getOpenStatusConstants());
    return $query;
  }

  private function getWindow() {

    $window_str = $this->request->getStr('window', '12 AM 7 days ago');

    $error = null;
    $window_epoch = null;

    // Do locale-aware parsing so that the user's timezone is assumed for
    // time windows like "3 PM", rather than assuming the server timezone.

    $window_epoch = PhabricatorTime::parseLocalTime($window_str, $this->user);
    if (!$window_epoch) {
      $error = 'Invalid';
      $window_epoch = time() - (60 * 60 * 24 * 7);
    }

    // If the time ends up in the future, convert it to the corresponding time
    // and equal distance in the past. This is so users can type "6 days" (which
    // means "6 days from now") and get the behavior of "6 days ago", rather
    // than no results (because the window epoch is in the future). This might
    // be a little confusing because it casues "tomorrow" to mean "yesterday"
    // and "2022" (or whatever) to mean "ten years ago", but these inputs are
    // nonsense anyway.

    if ($window_epoch > time()) {
      $window_epoch = time() - ($window_epoch - time());
    }

    return array($window_str, $window_epoch, $error);
  }

  private function getProjectHandle($phids,$project_phid) {

    $handles = $this->loadViewerHandles($phids);
    $project_handle = $handles[$project_phid];
    return $project_handle;
  }

  private function renderOldest(array $tasks) {
    assert_instances_of($tasks, 'ManiphestTask');
    $oldest = null;
    foreach ($tasks as $id => $task) {
      if (($oldest === null) ||
          ($task->getDateCreated() < $tasks[$oldest]->getDateCreated())) {
        $oldest = $id;
      }
    }

    if ($oldest === null) {
      return array('-', 0);
    }

    $oldest = $tasks[$oldest];

    $raw_age = (time() - $oldest->getDateCreated());
    $age = number_format($raw_age / (24 * 60 * 60)).' d';

    $link = javelin_tag(
        'a',
        array(
            'href'  => '/T'.$oldest->getID(),
            'sigil' => 'has-tooltip',
            'meta'  => array(
                'tip' => 'T'.$oldest->getID().': '.$oldest->getTitle(),
            ),
            'target' => '_blank',
        ),
        $age);

    return array($link, $raw_age);
  }
}