<?php

final class C3ChartView {
  private $data;
  private $project;
  private $timeseries;

  public function setChartData($chart_data) {
    $this->data = $chart_data;
    return $this;
  }

  public function setTimeSeries($timeseries) {
    $this->timeseries = $timeseries;
    return $this;
  }

  public function setProject($project) {
    $this->project = $project;
    return $this;
  }

  public function buildC3Chart() {
    $totalpoints = $this->data[0];
    $remainingpoints = $this->data[1];
    $idealpoints = $this->data[2];
    $pointstoday = $this->data[3];
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


}