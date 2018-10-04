<?php

final class SprintReportBurnUpView extends SprintView {

  private $request;
  private $viewer;

  public function setViewer(PhabricatorUser $viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest($request) {
    $this->request = $request;
    return $this;
  }

  public function render() {
    require_celerity_resource('sprint-report-css', 'sprint');
    $filter = $this->BuildFilter($this->request, $this->viewer);
    if ($this->request->getStr('project')) {
      $chart = $this->buildBurnDownChart();
      $table = $this->buildStatsTable();
    } else {
      $chart = null;
      $table = null;
    }

    return array($filter, $chart, $table);
  }

  private function getXactionData($project_phid) {
     $query = id(new SprintQuery())
        ->setPHID($project_phid);
      $data = $query->getXactionData(ManiphestTaskStatusTransaction::TRANSACTIONTYPE);
    return $data;
  }

  private function addTaskStatustoData($data) {
   foreach ($data as $key => $row) {

     // NOTE: Hack to avoid json_decode().
     $oldv = trim($row['oldValue'], '"');
     $newv = trim($row['newValue'], '"');

     if ($oldv == 'null') {
       $old_is_open = false;
     } else {
       $old_is_open = ManiphestTaskStatus::isOpenStatus($oldv);
     }

     $new_is_open = ManiphestTaskStatus::isOpenStatus($newv);

     $is_open = ($new_is_open && !$old_is_open);
     $is_close = ($old_is_open && !$new_is_open);

     $data[$key]['_is_open'] = $is_open;
     $data[$key]['_is_close'] = $is_close;

     if (!$is_open && !$is_close) {
       // This is either some kind of bogus events, or a resolution change
       // (e.g., resolved -> invalid). Just skip it.
       continue;
     }
   }
   return $data;
 }

  private function buildStatsfromEvents($data) {
   $stats = array();
   $data = $this->addTaskStatustoData($data);

    foreach ($data as $key => $row) {

     $day_bucket = phabricator_format_local_time(
          $row['dateCreated'],
          $this->viewer,
          'Yz');

      if (empty($stats[$day_bucket])) {
        $stats[$day_bucket] = array(
            'open' => 0,
            'close' => 0,
        );
      }

      $stats[$day_bucket][$data[$key]['_is_close'] ? 'close' : 'open']++;
    }
    return $stats;
  }

  private function buildDayBucketsfromEvents($data) {
      $day_buckets = array();
      foreach ($data as $key => $row) {

       $day_bucket = phabricator_format_local_time(
            $row['dateCreated'],
            $this->viewer,
            'Yz');
       $day_buckets[$day_bucket] = $row['dateCreated'];
       }
    return $day_buckets;
  }

  /**
   * @param string $format
   */
  private function buildBucket($epoch, $format) {
    $bucket = phabricator_format_local_time(
        $epoch,
        $this->viewer,
        $format);
    return $bucket;
  }


  private function formatBucketRows($stats, $day_buckets) {
    $template = array(
        'open' => 0,
        'close' => 0,
    );

    $rows = array();
    $rowc = array();
    $last_month = null;
    $last_month_epoch = null;
    $last_week = null;
    $last_week_epoch = null;
    $week = null;
    $month = null;

    $period = $template;

    foreach ($stats as $bucket => $info) {
      $epoch = $day_buckets[$bucket];

      $week_bucket = $this->buildBucket($epoch, 'YW');

      if ($week_bucket != $last_week) {
        if ($week) {
          $rows[] = $this->formatBurnRow(
              'Week of '.phabricator_date($last_week_epoch, $this->viewer),
              $week);
          $rowc[] = 'week';
        }
        $week = $template;
        $last_week = $week_bucket;
        $last_week_epoch = $epoch;
      }

      $month_bucket = $this->buildBucket($epoch, 'Ym');

      if ($month_bucket != $last_month) {
        if ($month) {
          $rows[] = $this->formatBurnRow(
              phabricator_format_local_time($last_month_epoch,
                  $this->viewer, 'F, Y'),
              $month);
          $rowc[] = 'month';
        }
        $month = $template;
        $last_month = $month_bucket;
        $last_month_epoch = $epoch;
      }

      $rows[] = $this->formatBurnRow(phabricator_date($epoch, $this->viewer),
          $info);
      $rowc[] = null;
      $week['open'] += $info['open'];
      $week['close'] += $info['close'];
      $month['open'] += $info['open'];
      $month['close'] += $info['close'];
      $period['open'] += $info['open'];
      $period['close'] += $info['close'];
    }
    return array($rows, $rowc, $week, $month, $period);
  }

  private function renderCaption($handle) {
      $inst = pht(
          'NOTE: This table reflects tasks currently in '.
          'the project. If a task was opened in the past but added to '.
          'the project recently, it is counted on the day it was '.
          'opened, not the day it was categorized. If a task was part '.
          'of this project in the past but no longer is, it is not '.
          'counted at all.');
      $header = pht('Task Burn Rate for Project %s', $handle->renderLink());
      $caption = phutil_tag('p', array(), $inst);
   return array($caption, $header);
  }

  private function formatStatsTableHeaders($week, $month, $period, $rows,
                                           $rowc) {
     if ($week) {
      $rows[] = $this->formatBurnRow(
          pht('Week To Date'),
          $week);
      $rowc[] = 'week';
    }

    if ($month) {
      $rows[] = $this->formatBurnRow(
          pht('Month To Date'),
          $month);
      $rowc[] = 'month';
    }

    $rows[] = $this->formatBurnRow(
        pht('All Time'),
        $period);
    $rowc[] = 'aggregate';

    $rows = array_reverse($rows);
    $rowc = array_reverse($rowc);
  return array($rows, $rowc);
  }

  private function buildStatsTable() {
    $handle = null;
    $project_phid = $this->request->getStr('project');

    if ($project_phid) {
      $phids = array($project_phid);
      $handle = $this->getProjectHandle($phids, $project_phid, $this->request);
    }

    $data = $this->getXactionData($project_phid);

    $stats = $this->buildStatsfromEvents($data);
    $day_buckets = $this->buildDayBucketsfromEvents($data);

    list($rows, $rowc, $week, $month, $period) =
        $this->formatBucketRows($stats, $day_buckets);
    list($rows, $rowc) = $this->formatStatsTableHeaders($week, $month, $period,
        $rows, $rowc);

    $table = $this->statsTableView($rows, $rowc);

  if ($handle) {
    list($caption, $header) = $this->renderCaption($handle);
    $caption = id(new PHUIInfoView())
        ->appendChild($caption)
        ->setSeverity(PHUIInfoView::SEVERITY_NOTICE);
  } else {
    $header = pht('Task Burn Rate for All Tasks');
    $caption = null;
  }

    $panel = new PHUIObjectBoxView();
    $panel->setHeaderText($header);
    if ($caption) {
      $panel->setInfoView($caption);
    }
    $panel->setTable($table);

    return $panel;
  }

  /**
   * @param string[] $rowc
   */
  private function statsTableView($rows, $rowc) {
    $table = new AphrontTableView($rows);
    $table->setRowClasses($rowc);
    $table->setHeaders(
        array(
            pht('Period'),
            pht('Opened'),
            pht('Closed'),
            pht('Change'),
        ));
    $table->setColumnClasses(
        array(
            'left narrow',
            'center narrow',
            'center narrow',
            'center narrow',
        ));

    return $table;
  }

  private function buildBurnDownChart() {
    $project_phid = $this->request->getStr('project');
    $data = $this->getXactionData($project_phid);
      $id = 'burnup chart';
      $data = $this->buildSeries($data);
      $stats = new SprintStats();
      $data = $stats->transposeArray($data);
    require_celerity_resource('d3', 'sprint');
    require_celerity_resource('c3-css', 'sprint');
    require_celerity_resource('c3', 'sprint');

      Javelin::initBehavior('burndown-report-chart', array(
          'hardpoint' => $id,
          'x' => $data[0],
          'y' => $data[1],
      ), 'sprint');

    $chart = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Burn Up Report'))
        ->appendChild(phutil_tag('div',
            array(
                'id' => $id,
                'style' => 'border: 1px solid #BFCFDA; '.
                    'background-color: #fff; '.
                    'margin: 8px 16px; '.
                    'height: 400px; ',
            ), ''));

      return $chart;
    }

  private function buildSeries(array $data) {
    $output = array(
    array(
        pht('Dates'),
        pht('Tasks'),
    ),);
      $tdata = $this->addTaskStatustoData($data);
      $counter = 0;
      foreach ($tdata as $key => $row) {
        $t = (int)$row['dateCreated'] * 1000;
        if ($row['_is_close']) {
          --$counter;
        } else if ($row['_is_open']) {
          ++$counter;
        }
        $output[] = array(
        $t,
        $counter,
        );

      }
      return $output;
    }

    /**
     * @param string $label
     */
    private function formatBurnRow($label, $info) {
      $delta = $info['open'] - $info['close'];
      $fmt = number_format($delta);
      if ($delta > 0) {
        $fmt = '+'.$fmt;
        $fmt = phutil_tag('span', array('class' => 'red'), $fmt);
      } else {
        $fmt = phutil_tag('span', array('class' => 'green'), $fmt);
      }

      return array(
          $label,
          number_format($info['open']),
          number_format($info['close']),
          $fmt,
      );
    }
 }
