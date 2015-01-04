<?php
final class SprintQueryTest extends SprintTestCase {

  public function getRequestObject()
  {
    $r = new AphrontRequest('example.com', '/');
    return $r;
  }

  public function testRequestSetUser()
  {
    $r = new AphrontRequest('example.com', '/');
    $user = $this->generateNewTestUser();
    $r->setUser($user);
    $this->assertEquals($user, $r->getUser());
    return $r;
  }


  public function PHIDProvider()
  {
    return array(
        'PHID-PROJ-mp777tqnkvivubj26ufu',
    );
  }

  private function createProject(PhabricatorUser $user) {
    $project = PhabricatorProject::initializeNewProject($user);
    $project->setName('Test Project '.mt_rand());
    return $project;
  }

//  public function testgetTasks() {
//    $viewer = $this->generateNewTestUser();
//    $project = $this->createProject($viewer);
//    $phid[] = $project->generatePHID();
//    $project->attachMemberPHIDs($phid);
//    $query = id(new SprintQuery())
//        ->setProject($project)
//        ->setViewer($viewer);
//    $tasks = $query->getTasks();
//    $this->assertInstanceOf('ManiphestTask', $tasks[0]);

//  }
  /**
   * @depends testRequestSetUser
   */
//  public function testGetViewerHandles()
//  {
//    $r = new AphrontRequest('example.com', '/');
//    $r->setUser($this->generateNewTestUser());
//    $q = new SprintQuery();
//    $phids = $this->PHIDProvider();
//    $handle = $q->getViewerHandles($r, $phids);
//    $this->assertInstanceof('PhabricatorObjectHandle', $handle);
//  }

}