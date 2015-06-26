<?php
final class SprintControllerTest extends SprintTestCase {

  public function testshouldAllowPublic() {
    $stub = $this->getMockForAbstractClass('SprintController');

    $this->assertTrue($stub->shouldAllowPublic());
  }

  public function testgetSprintDataView() {
    $projectobj = new PhabricatorProject();
    $viewer = $this->generateNewTestUser();
    $request = new AphrontRequest('phab.wmde.de', '/project/sprint/view/18');
    $dv = new SprintDataViewController();
    $burndownview = $dv->getSprintDataView($request, $projectobj, $viewer);
    $this->assertInstanceOf('SprintDataView', $burndownview);
  }

  public function testgetErrorBox() {
    $e = new Exception();
    $dv = new SprintDataViewController();
    $errorbox = $dv->getErrorBox($e);
    $this->assertInstanceOf('PHUIInfoView', $errorbox);
  }

  public function testhandleRequestDataViewController() {
    $dvcontroller = new SprintDataViewController();
    $sprint = new SprintApplication();
    $dvcontroller->setCurrentApplication($sprint);
    $request = new AphrontRequest('phab.wmde.de', '/project/sprint/view/18');
    $dvcontroller->setRequest($request);
    $data = array();
    $data['id'] =  18;
    $request->setURIMap($data);
    $viewer = $this->generateNewTestUser();
    $request->setUser($viewer);
    $dvcontroller->willProcessRequest($data);
    $response = $dvcontroller->handleRequest($request);
    $this->assertInstanceOf('AphrontResponse', $response);
  }

  public function testhandleRequestDataViewControllerFail() {
    $dvcontroller = new SprintDataViewController();
    $sprint = new SprintApplication();
    $dvcontroller->setCurrentApplication($sprint);
    $request = new AphrontRequest('phab.wmde.de', '/project/sprint/view/18');
    $dvcontroller->setRequest($request);
    $data = array();
    $data['id'] =  3;
    $request->setURIMap($data);
    $viewer = $this->generateNewTestUser();
    $request->setUser($viewer);
    $dvcontroller->willProcessRequest($data);
    $response = $dvcontroller->handleRequest($request);
    $this->assertInstanceOf('Aphront404Response', $response);
  }

  public function testhandleRequestProjectProfileController() {
    $dvcontroller = new SprintProjectProfileController();
    $sprint = new SprintApplication();
    $dvcontroller->setCurrentApplication($sprint);
    $request = new AphrontRequest('phab.wmde.de', '/project/profile/18');
    $dvcontroller->setRequest($request);
    $data = array();
    $data['id'] =  18;
    $request->setURIMap($data);
    $viewer = $this->generateNewTestUser();
    $request->setUser($viewer);
    $dvcontroller->willProcessRequest($data);
    $response = $dvcontroller->handleRequest($request);
    $this->assertInstanceOf('AphrontResponse', $response);
  }

  public function testhandleRequestProjectProfileControllerFail() {
    $dvcontroller = new SprintProjectProfileController();
    $sprint = new SprintApplication();
    $dvcontroller->setCurrentApplication($sprint);
    $request = new AphrontRequest('phab.wmde.de', '/project/profile/18');
    $dvcontroller->setRequest($request);
    $data = array();
    $data['id'] =  3;
    $request->setURIMap($data);
    $viewer = $this->generateNewTestUser();
    $request->setUser($viewer);
    $dvcontroller->willProcessRequest($data);
    $response = $dvcontroller->handleRequest($request);
    $this->assertInstanceOf('Aphront404Response', $response);
  }

  public function testhandleRequestProjectViewController() {
    $dvcontroller = new SprintProjectViewController();
    $sprint = new SprintApplication();
    $dvcontroller->setCurrentApplication($sprint);
    $request = new AphrontRequest('phab.wmde.de', '/project/tag/null_project');
    $dvcontroller->setRequest($request);
    $data = array();
    $data['slug'] =  'null_project';
    $request->setURIMap($data);
    $viewer = $this->generateNewTestUser();
    $request->setUser($viewer);
    $dvcontroller->willProcessRequest($data);
    $response = $dvcontroller->handleRequest($request);
    $this->assertInstanceOf('AphrontResponse', $response);
  }

  public function testhandleRequestProjectViewControllerFail() {
    $dvcontroller = new SprintProjectViewController();
    $sprint = new SprintApplication();
    $dvcontroller->setCurrentApplication($sprint);
    $request = new AphrontRequest('phab.wmde.de', '/project/tag/null_project');
    $dvcontroller->setRequest($request);
    $data = array();
    $data['slug'] =  'fail_project';
    $request->setURIMap($data);
    $viewer = $this->generateNewTestUser();
    $request->setUser($viewer);
    $dvcontroller->willProcessRequest($data);
    $response = $dvcontroller->handleRequest($request);
    $this->assertInstanceOf('Aphront404Response', $response);
  }
}
