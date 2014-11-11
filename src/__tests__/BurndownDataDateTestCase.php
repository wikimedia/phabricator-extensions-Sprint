<?php
final class BurndownDataDateTestCase extends SprintTestCase {

  public function testSumPointsTotal() {
    $date = new BurndownDataDate('test date');
    $previous = id(new BurndownDataDate('monday'));
    $previous->setPointsTotal('14');
    $current = id(new BurndownDataDate('tuesday'));
    $current->setPointsTotal('4');
    $total = $date->sumPointsTotal($current, $previous);
    $this->assertEqual(18, $total);
  }

  public function testSumTasksTotal() {
    $date = new BurndownDataDate('test date');
    $previous = id(new BurndownDataDate('monday'));
    $previous->setTasksTotal('5');
    $current = id(new BurndownDataDate('tuesday'));
    $current->setTasksTotal('8');
    $total = $date->sumTasksTotal($current, $previous);
    $this->assertEqual(13, $total);
  }

  public function testSumTasksRemaining() {
    $date = new BurndownDataDate('test date');
    $previous = id(new BurndownDataDate('monday'));
    $previous->setTasksRemaining('5');
    var_dump($previous);
    $current = id(new BurndownDataDate('tuesday'));
    for ($i=0;$i<2; $i++) {
      $current->setTasksClosedToday();
    }
   // var_dump($current->getTasksClosedToday());
    $total = $date->sumTasksRemaining($current, $previous);
    $this->assertEqual(3, $total);
  }

  public function testSumPointsRemaining() {
    $date = new BurndownDataDate('test date');
    $previous = id(new BurndownDataDate('monday'));
    $previous->setPointsRemaining('5');
    $current = id(new BurndownDataDate('tuesday'));
    $current->setPointsClosedToday('2');
    $total = $date->sumPointsRemaining($current, $previous);
    $this->assertEqual(3, $total);
  }
}