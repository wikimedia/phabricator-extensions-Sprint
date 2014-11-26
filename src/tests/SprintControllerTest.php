<?php
final class SprintControllerTest extends SprintTestCase {

  public function testshouldAllowPublic()  {
    $stub = $this->getMockForAbstractClass('SprintController');

    $this->assertTrue($stub->shouldAllowPublic());
  }

  public function testbuildSideNavView() {
    $stub = $this->getMockForAbstractClass('SprintController');
    $user = $this->generateNewTestUser();
    $uri = new PhutilURI('/sprint/');
    $nav = $stub->buildSideNavView($for_app = false, $user, $uri);
    $this->assertInstanceOf('AphrontSideNavFilterView', $nav);
  }
}