<?php
final class SprintApplicationTest extends SprintTestCase {

  public function testGetName () {
    $burndown_application = new SprintApplication();
    $name = $burndown_application->getName();
    $this->assertEquals('Sprint', $name);
  }

  public function testBaseURI () {
    $burndown_application = new SprintApplication();
    $baseuri = $burndown_application->getBaseURI();
    $this->assertEquals('/project/sprint/', $baseuri);
  }

  public function testgetIconName() {
    $burndown_application = new SprintApplication();
    $icon_name = $burndown_application->getIconName();
    $this->assertEquals('fa-puzzle-piece', $icon_name);
  }

  public function testgetShortDescription() {
    $burndown_application = new SprintApplication();
    $description = $burndown_application->getShortDescription();
    $this->assertEquals('Build Sprints', $description);
  }

  public function testgetEventListeners() {
    $burndown_application = new SprintApplication();
    $eventlistener = $burndown_application->getEventListeners();
    $this->assertInstanceOf('BurndownActionMenuEventListener', $eventlistener[0]);
  }

}
