<?php
final class BurndownApplicationTest extends SprintTestCase {

  public function testGetName () {
    $burndown_application = new BurndownApplication;
    $name = $burndown_application->getName();
    $this->assertEquals('Sprint', $name);
  }

  public function testBaseURI () {
    $burndown_application = new BurndownApplication;
    $baseURI = $burndown_application->getBaseURI();
    $this->assertEquals('/sprint/report/', $baseURI);
  }

  public function testgetIconName() {
    $burndown_application = new BurndownApplication;
    $icon_name = $burndown_application->getIconName();
    $this->assertEquals('slowvote', $icon_name);
  }

  public function testgetShortDescription() {
    $burndown_application = new BurndownApplication;
    $description = $burndown_application->getShortDescription();
    $this->assertEquals('Build burndowns', $description);
  }

  public function testgetEventListeners() {
    $burndown_application = new BurndownApplication;
    $eventlistener = $burndown_application->getEventListeners();
    $this->assertInstanceOf('BurndownActionMenuEventListener', $eventlistener[0]);
  }

  public function testgetRoutes() {
    $burndown_application = new BurndownApplication;
    $routes = $burndown_application->getRoutes();
    $assertion = array(
        '/sprint/' => array(
            '' => 'BurndownListController',
            'report/' => 'BurndownListController',
            'report/list/' => 'BurndownListController',
            'report/(?:(?P<view>\w+)/)?' => 'SprintReportController',
            'view/(?P<id>\d+)/' => 'BurndownDataViewController',
        ));
    $this->assertEquals($assertion, $routes);
  }
}