<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

final class SprintDataView extends SprintView
{

  private $request;
  private $timeseries;
  private $sprint_data;
  private $project;
  private $viewer;
  private $tasks;
  private $taskpoints;
  private $events;
  private $xactions;
  private $start;
  private $end;
  private $before;

  public function setProject($project) {
    $this->project = $project;
    return $this;
  }

  public function setViewer($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function setRequest($request) {
    $this->request = $request;
    return $this;
  }

  public function render() {
    $query = id(new SprintQuery())
        ->setProject($this->project)
        ->setViewer($this->viewer)
        ->setPHID($this->project->getPHID());

    $this->taskpoints = $query->getTaskData(SprintConstants::CUSTOMFIELD_INDEX);
    $tasks = $query->getTasks();
    $this->tasks = mpull($tasks, null, 'getPHID');

    $chart = $this->buildC3Chart($query);

    $tasks_table_view = id(new TasksTableView())
        ->setProject($this->project)
        ->setViewer($this->viewer)
        ->setRequest($this->request)
        ->setTasks($this->tasks)
        ->setTaskPoints($this->taskpoints)
        ->setQuery($query);

    $tasks_table = $tasks_table_view->buildTasksTable();
    $pie = $this->buildC3Pie();
    $history_table_view = new HistoryTableView();
    $history_table = $history_table_view->buildHistoryTable($this->before);
    $sprint_table_view = new SprintTableView();
    $sprint_table = $sprint_table_view->buildSprintTable($this->sprint_data,
        $this->before);
    $event_table_view = id(new EventTableView())
        ->setProject($this->project)
        ->setViewer($this->viewer)
        ->setRequest($this->request);
    $event_table = $event_table_view->buildEventTable($this->events,
        $this->xactions,
        $this->tasks, $this->start, $this->end);
    return array($chart, $tasks_table, $pie, $history_table, $sprint_table,
        $event_table);
  }

  private function buildChartDataSet($query) {

    $aux_fields = $query->getAuxFields();
    $this->start = $query->getStartDate($aux_fields);
    $this->end = $query->getEndDate($aux_fields);
    $stats = id(new SprintBuildStats());

    $query->checkNull($this->start, $this->end, $this->tasks);
    $timezone = $stats->setTimezone($this->viewer);
    $this->before = $stats->buildBefore($this->start, $timezone);
    $dates = $stats->buildDateArray($this->start, $this->end, $timezone);
    $this->timeseries = $stats->buildTimeSeries($this->start, $this->end);


    $xactions = $query->getXactions($this->tasks);
    $this->events = $query->getEvents($xactions, $this->tasks);

    $this->xactions = mpull($xactions, null, 'getPHID');

    $sprint_xaction = id(new SprintTransaction())
        ->setViewer($this->viewer)
        ->setTasks($this->tasks)
        ->setTaskPoints($this->taskpoints);

    $sprint_xaction->buildStatArrays();

    $dates = $sprint_xaction->buildDailyData($this->events, $this->before,
        $this->start, $this->end, $dates, $this->xactions, $this->project);

    $this->sprint_data = $stats->setSprintData($dates, $this->before);
    $data = $stats->buildDataSet($this->sprint_data);
    $data = $this->transposeArray($data);
    return $data;
  }

  private function transposeArray($array) {
    $transposed_array = array();
    if ($array) {
      foreach ($array as $row_key => $row) {
        if (is_array($row) && !empty($row)) {
          foreach ($row as $column_key => $element) {
            $transposed_array[$column_key][$row_key] = $element;
          }
        } else {
          $transposed_array[0][$row_key] = $row;
        }
      }
    }
    return $transposed_array;
  }

  private function buildC3Chart($query) {
    $data = $this->buildChartDataSet($query);
    $totalpoints = $data[0];
    $remainingpoints = $data[1];
    $idealpoints = $data[2];
    $pointstoday = $data[3];
    $timeseries = $this->timeseries;

    require_celerity_resource('d3', 'sprint');
    require_celerity_resource('c3-css', 'sprint');
    require_celerity_resource('c3', 'sprint');

    $id = 'chart';
    Javelin::initBehavior('c3-chart', array(
        'hardpoint' => $id,
        'timeseries' => $timeseries,
        'totalpoints' => $totalpoints,
        'remainingpoints' => $remainingpoints,
        'idealpoints' => $idealpoints,
        'pointstoday' => $pointstoday
    ), 'sprint');

    $chart = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Burndown for ' . $this->project->getName()))
        ->appendChild(phutil_tag('div',
            array(
                'id' => 'chart',
                'style' => 'width: 100%; height:400px'
            ), ''));

    return $chart;
  }

  private function buildC3Pie() {
    $sprintpoints = id(new SprintPoints())
        ->setTaskPoints($this->taskpoints)
        ->setTasks($this->tasks);

    list($task_open_status_sum, $task_closed_status_sum) = $sprintpoints
        ->getStatusSums();

    require_celerity_resource('d3', 'sprint');
    require_celerity_resource('c3-css', 'sprint');
    require_celerity_resource('c3', 'sprint');

    $id = 'pie';
    Javelin::initBehavior('c3-pie', array(
        'hardpoint' => $id,
        'open' => $task_open_status_sum,
        'resolved' => $task_closed_status_sum,
    ), 'sprint');

    $pie = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Task Status Report for ' . $this->project->getName()))
        ->appendChild(phutil_tag('div',
            array(
                'id' => 'pie',
                'style' => 'width: 100%; height:200px'
            ), ''));

    return $pie;
  }
}
