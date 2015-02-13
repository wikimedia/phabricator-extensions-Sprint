<?php

final class SprintTableView extends AphrontView {

  protected $data;
  protected $headers;
  protected $shortHeaders;
  protected $tableId;
  protected $rowClasses = array();
  protected $columnClasses = array();
  protected $cellClasses = array();
  protected $zebraStripes = true;
  protected $noDataString;
  protected $className;
  protected $columnVisibility = array();
  private $deviceVisibility = array();

  protected $sortURI;
  protected $sortParam;
  protected $sortSelected;
  protected $sortReverse;
  protected $sortValues;
  private $deviceReadyTable;

  public function __construct(array $data) {
    $this->data = $data;
  }

  public function setHeaders(array $headers) {
    $this->headers = $headers;
    return $this;
  }

  public function setColumnClasses(array $column_classes) {
    $this->columnClasses = $column_classes;
    return $this;
  }

  public function setTableId($table_id) {
    $this->tableId = $table_id;
    return $this;
  }

  public function setRowClasses(array $row_classes) {
    $this->rowClasses = $row_classes;
    return $this;
  }

  public function setCellClasses(array $cell_classes) {
    $this->cellClasses = $cell_classes;
    return $this;
  }

  public function setNoDataString($no_data_string) {
    $this->noDataString = $no_data_string;
    return $this;
  }

  public function setClassName($class_name) {
    $this->className = $class_name;
    return $this;
  }

  public function setZebraStripes($zebra_stripes) {
    $this->zebraStripes = $zebra_stripes;
    return $this;
  }

  public function setColumnVisibility(array $visibility) {
    $this->columnVisibility = $visibility;
    return $this;
  }

  public function setDeviceVisibility(array $device_visibility) {
    $this->deviceVisibility = $device_visibility;
    return $this;
  }

  public function setDeviceReadyTable($ready) {
    $this->deviceReadyTable = $ready;
    return $this;
  }

  public function setShortHeaders(array $short_headers) {
    $this->shortHeaders = $short_headers;
    return $this;
  }

  public function render() {
    require_celerity_resource('aphront-table-view-css');
    require_celerity_resource('jquery', 'sprint');
    require_celerity_resource('dataTables-css', 'sprint');
    require_celerity_resource('dataTables', 'sprint');

    $table = array();

    $col_classes = array();
    foreach ($this->columnClasses as $key => $class) {
      if (strlen($class)) {
        $col_classes[] = $class;
      } else {
        $col_classes[] = null;
      }
    }

    $visibility = array_values($this->columnVisibility);
    $device_visibility = array_values($this->deviceVisibility);
    $headers = $this->headers;
    $short_headers = $this->shortHeaders;
    $sort_values = $this->sortValues;
    if ($headers) {
      while (count($headers) > count($visibility)) {
        $visibility[] = true;
      }
      while (count($headers) > count($device_visibility)) {
        $device_visibility[] = true;
      }
      while (count($headers) > count($short_headers)) {
        $short_headers[] = null;
      }
      while (count($headers) > count($sort_values)) {
        $sort_values[] = null;
      }

      $thead = array();
      foreach ($headers as $col_num => $header) {
        if (!$visibility[$col_num]) {
          continue;
        }

        $classes = array();

        if (!empty($col_classes[$col_num])) {
          $classes[] = $col_classes[$col_num];
        }

        if (empty($device_visibility[$col_num])) {
          $classes[] = 'aphront-table-view-nodevice';
        }

        if ($classes) {
          $class = implode(' ', $classes);
        } else {
          $class = null;
        }

        if ($short_headers[$col_num] !== null) {
          $header_nodevice = phutil_tag(
              'span',
              array(
                  'class' => 'aphront-table-view-nodevice',
              ),
              $header);
          $header_device = phutil_tag(
              'span',
              array(
                  'class' => 'aphront-table-view-device',
              ),
              $short_headers[$col_num]);

          $header = hsprintf('%s %s', $header_nodevice, $header_device);
        }

        $thead[] = phutil_tag('th', array('class' => $class), $header);
      }
      $table[] = phutil_tag('thead', array(), $thead);
    }

    foreach ($col_classes as $key => $value) {
      if ($value !== null) {
        $col_classes[$key] = $value;
      }
    }

    $data = $this->data;
    if ($data) {
      $row_num = 0;
      foreach ($data as $row) {
        while (count($row) > count($col_classes)) {
          $col_classes[] = null;
        }
        while (count($row) > count($visibility)) {
          $visibility[] = true;
        }
        $tr = array();
        // NOTE: Use of a separate column counter is to allow this to work
        // correctly if the row data has string or non-sequential keys.
        $col_num = 0;
        foreach ($row as $value) {
          if (!$visibility[$col_num]) {
            ++$col_num;
            continue;
          }
          $class = $col_classes[$col_num];
          if (empty($device_visibility[$col_num])) {
            $class = trim($class.' aphront-table-view-nodevice');
          }
          if (!empty($this->cellClasses[$row_num][$col_num])) {
            $class = trim($class.' '.$this->cellClasses[$row_num][$col_num]);
          }
          $tr[] = phutil_tag('td', array('class' => $class), $value);
          ++$col_num;
        }

        $class = idx($this->rowClasses, $row_num);
        if ($this->zebraStripes && ($row_num % 2)) {
          if ($class !== null) {
            $class = 'alt alt-'.$class;
          } else {
            $class = 'alt';
          }
        }

        $table[] = phutil_tag('tr', array('class' => $class), $tr);
        ++$row_num;
      }
    } else {
      $colspan = max(count(array_filter($visibility)), 1);
      $table[] = phutil_tag(
          'tr',
          array('class' => 'no-data'),
          phutil_tag(
              'td',
              array('colspan' => $colspan),
              coalesce($this->noDataString, pht('No data available.'))));
    }

    $table_class = 'aphront-table-view';
    if ($this->className !== null) {
      $table_class .= ' '.$this->className;
    }
    if ($this->deviceReadyTable) {
      $table_class .= ' aphront-table-view-device-ready';
    }

    if ($this->tableId !== null) {
      $table_id = $this->tableId;
    } else {
      $table_id = celerity_generate_unique_node_id();
    }

    $html = phutil_tag('table', array('class' => $table_class,
        'id' => $table_id,), $table);
    return phutil_tag_div('aphront-table-wrap', $html);
  }

  public static function renderSingleDisplayLine($line) {

    // TODO: Is there a cleaner way to do this? We use a relative div with
    // overflow hidden to provide the bounds, and an absolute span with
    // white-space: pre to prevent wrapping. We need to append a character
    // (&nbsp; -- nonbreaking space) afterward to give the bounds div height
    // (alternatively, we could hard-code the line height). This is gross but
    // it's not clear that there's a better appraoch.

    return phutil_tag(
        'div',
        array(
            'class' => 'single-display-line-bounds',
        ),
        array(
            phutil_tag(
                'span',
                array(
                    'class' => 'single-display-line-content',
                ),
                $line),
            "\xC2\xA0",
        ));
  }
}
