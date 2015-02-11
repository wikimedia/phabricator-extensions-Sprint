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

  public function testgetRoutes() {
    $burndown_application = new SprintApplication();
    $routes = $burndown_application->getRoutes();
    $assertion = array(
      // this is the default application route controller
        '/project/sprint/' => array(
            '' => 'SprintListController',
          // these are forked controllers for the Sprint Board
            'board/(?P<projectID>[1-9]\d*)/' => array(
                'edit/(?:(?P<id>\d+)/)?'
                => 'SprintBoardColumnEditController',
                'hide/(?:(?P<id>\d+)/)?'
                => 'SprintBoardColumnHideController',
                'column/(?:(?P<id>\d+)/)?'
                => 'SprintBoardColumnDetailController',
                'import/'
                => 'SprintBoardImportController',
                'reorder/'
                => 'SprintBoardReorderController',
            ),
          // these allow task creation and editing from a Sprint Board
            'board/task/edit/(?P<id>[1-9]\d*)/'
            => 'SprintBoardTaskEditController',
            'board/task/create/'
            => 'SprintBoardTaskEditController',
          // these are for board filters and column queries
            'board/(?P<id>[1-9]\d*)/'.
            '(?P<filter>filter/)?'.
            '(?:query/(?P<queryKey>[^/]+)/)?'
            => 'SprintBoardViewController',
          // these are native Sprint application controllers
            'burn/(?P<id>\d+)/' => 'SprintDataViewController',
            'profile/(?P<id>[1-9]\d*)/'
            => 'SprintProjectProfileController',
            'report/list/' => 'SprintListController',
            'report/(?:(?P<view>\w+)/)?' => 'SprintReportController',
            'view/(?P<id>\d+)/' => 'SprintDataViewController',
          // all routes following point to default controllers
            'archive/(?P<id>[1-9]\d*)/'
            => 'PhabricatorProjectArchiveController',
            'details/(?P<id>[1-9]\d*)/'
            => 'PhabricatorProjectEditDetailsController',
            'feed/(?P<id>[1-9]\d*)/'
            => 'PhabricatorProjectFeedController',
            'icon/(?P<id>[1-9]\d*)/'
            => 'PhabricatorProjectEditIconController',
            'members/(?P<id>[1-9]\d*)/'
            => 'PhabricatorProjectMembersEditController',
            'members/(?P<id>[1-9]\d*)/remove/'
            => 'PhabricatorProjectMembersRemoveController',
            'move/(?P<id>[1-9]\d*)/' => 'SprintBoardMoveController',
            'picture/(?P<id>[1-9]\d*)/'
            => 'PhabricatorProjectEditPictureController',
            'update/(?P<id>[1-9]\d*)/(?P<action>[^/]+)/'
            => 'PhabricatorProjectUpdateController',
        ),
      // primary tag route override
        '/tag/' => array(
            '(?P<slug>[^/]+)/' => 'SprintProjectViewController',
            '(?P<slug>[^/]+)/board/' => 'SprintBoardViewController',
        ),
    );
    $this->assertEquals($assertion, $routes);
  }
}
