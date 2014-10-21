<?php

final class SprintReportBurndownView extends SprintView {

  private $request;

  public function setUser ($user) {
    $this->user = $user;
    return $this;
  }

  public function setRequest ($request) {
    $this->request = $request;
    return $this;
  }

  public function render() {

      $handle = null;

      $project_phid = $this->request->getStr('project');
      if ($project_phid) {
        $phids = array($project_phid);
        $handles = $this->loadViewerHandles($phids);
        $handle = $handles[$project_phid];
      }

      $query = id(new SprintQuery())
        ->setPHID($project_phid);

      $data = $query->getXactionData(ManiphestTransaction::TYPE_STATUS);

      $stats = array();
      $day_buckets = array();

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

        $is_open  = ($new_is_open && !$old_is_open);
        $is_close = ($old_is_open && !$new_is_open);

        $data[$key]['_is_open'] = $is_open;
        $data[$key]['_is_close'] = $is_close;

        if (!$is_open && !$is_close) {
          // This is either some kind of bogus events, or a resolution change
          // (e.g., resolved -> invalid). Just skip it.
          continue;
        }

        $day_bucket = phabricator_format_local_time(
            $row['dateCreated'],
            $this->user,
            'Yz');
        $day_buckets[$day_bucket] = $row['dateCreated'];
        if (empty($stats[$day_bucket])) {
          $stats[$day_bucket] = array(
              'open'  => 0,
              'close' => 0,
          );
        }
        $stats[$day_bucket][$is_close ? 'close' : 'open']++;
      }

      $template = array(
          'open'  => 0,
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

        $week_bucket = phabricator_format_local_time(
            $epoch,
            $this->user,
            'YW');
        if ($week_bucket != $last_week) {
          if ($week) {
            $rows[] = $this->formatBurnRow(
                'Week of '.phabricator_date($last_week_epoch, $this->user),
                $week);
            $rowc[] = 'week';
          }
          $week = $template;
          $last_week = $week_bucket;
          $last_week_epoch = $epoch;
        }

        $month_bucket = phabricator_format_local_time(
            $epoch,
            $this->user,
            'Ym');
        if ($month_bucket != $last_month) {
          if ($month) {
            $rows[] = $this->formatBurnRow(
                phabricator_format_local_time($last_month_epoch, $this->user, 'F, Y'),
                $month);
            $rowc[] = 'month';
          }
          $month = $template;
          $last_month = $month_bucket;
          $last_month_epoch = $epoch;
        }

        $rows[] = $this->formatBurnRow(phabricator_date($epoch, $this->user), $info);
        $rowc[] = null;
        $week['open'] += $info['open'];
        $week['close'] += $info['close'];
        $month['open'] += $info['open'];
        $month['close'] += $info['close'];
        $period['open'] += $info['open'];
        $period['close'] += $info['close'];
      }

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

      if ($handle) {
        $inst = pht(
            'NOTE: This table reflects tasks currently in '.
            'the project. If a task was opened in the past but added to '.
            'the project recently, it is counted on the day it was '.
            'opened, not the day it was categorized. If a task was part '.
            'of this project in the past but no longer is, it is not '.
            'counted at all.');
        $header = pht('Task Burn Rate for Project %s', $handle->renderLink());
        $caption = phutil_tag('p', array(), $inst);
      } else {
        $header = pht('Task Burn Rate for All Tasks');
        $caption = null;
      }

      if ($caption) {
        $caption = id(new AphrontErrorView())
            ->appendChild($caption)
            ->setSeverity(AphrontErrorView::SEVERITY_NOTICE);
      }

      $panel = new PHUIObjectBoxView();
      $panel->setHeaderText($header);
      if ($caption) {
        $panel->setErrorView($caption);
      }
      $panel->appendChild($table);

      $tokens = array();
      if ($handle) {
        $tokens = array($handle);
      }

      $filter = parent::renderReportFilters($tokens, $has_window = false);

      $id = celerity_generate_unique_node_id();
      $chart = phutil_tag(
          'div',
          array(
              'id' => $id,
              'style' => 'border: 1px solid #BFCFDA; '.
                  'background-color: #fff; '.
                  'margin: 8px 16px; '.
                  'height: 400px; ',
          ),
          '');

      list($burn_x, $burn_y) = $this->buildSeries($data);

      require_celerity_resource('raphael-core');
      require_celerity_resource('raphael-g');
      require_celerity_resource('raphael-g-line');

      Javelin::initBehavior('line-chart', array(
          'hardpoint' => $id,
          'x' => array(
              $burn_x,
          ),
          'y' => array(
              $burn_y,
          ),
          'xformat' => 'epoch',
          'yformat' => 'int',
      ));

      return array($filter, $chart, $panel);
    }

    private function buildSeries(array $data) {
      $out = array();

      $counter = 0;
      foreach ($data as $row) {
        $t = (int)$row['dateCreated'];
        if ($row['_is_close']) {
          --$counter;
          $out[$t] = $counter;
        } else if ($row['_is_open']) {
          ++$counter;
          $out[$t] = $counter;
        }
      }

      return array(array_keys($out), array_values($out));
    }

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