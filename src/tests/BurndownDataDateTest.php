<?php
final class BurndownDataDateTest extends SprintTestCase {

  public function testSumPointsTotal() {
    $date = new BurndownDataDate('test date');
    $previous = id(new BurndownDataDate('monday'));
    $previous->setPointsTotal('14');
    $current = id(new BurndownDataDate('tuesday'));
    $current->setPointsTotal('4');
    $total = $date->sumPointsTotal($current, $previous);
    $this->assertEquals(18, $total);
  }

  public function testSumTasksTotal() {
    $date = new BurndownDataDate('test date');
    $previous = id(new BurndownDataDate('monday'));
    $previous->setTasksTotal('5');
    $current = id(new BurndownDataDate('tuesday'));
    $current->setTasksTotal('8');
    $total = $date->sumTasksTotal($current, $previous);
    $this->assertEquals(13, $total);
  }
}