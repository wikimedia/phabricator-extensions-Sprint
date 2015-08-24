<?php

final class BurndownChartView extends Phobject{
  private $data;

  public function setChartData($chart_data) {
    $this->data = $chart_data;
    return $this;
  }

  public function buildBurndownChart() {
    $chartdata = $this->data->getChartData();
    $totalpoints = $chartdata[0];
    $remainingpoints = $chartdata[1];
    $idealpoints = $chartdata[2];
    $pointstoday = $chartdata[3];
    $timeseries = $this->data->getTimeSeries();
    $project_name = $this->data->getProject()->getName();

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
        'pointstoday' => $pointstoday,
    ), 'sprint');

    $chart = id(new PHUIObjectBoxView())
        ->setHeaderText(pht('Burndown for '.$project_name))
        ->setColor('blue')
        ->appendChild(phutil_tag('div',
            array(
                'id' => 'chart',
                'style' => 'width: 100%; height:450px;',
            ), ''));

    return $chart;
  }


}
