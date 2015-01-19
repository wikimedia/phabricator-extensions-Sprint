<?php
final class SprintApplicationTest extends SprintTestCase {

  public function testGetName () {
    $burndown_application = new SprintApplication;
    $name = $burndown_application->getName();
    $this->assertEquals('Sprint', $name);
  }

  public function testBaseURI () {
    $burndown_application = new SprintApplication;
    $baseURI = $burndown_application->getBaseURI();
    $this->assertEquals('/project/sprint/', $baseURI);
  }

  public function testgetIconName() {
    $burndown_application = new SprintApplication;
    $icon_name = $burndown_application->getIconName();
    $this->assertEquals('slowvote', $icon_name);
  }

  public function testgetShortDescription() {
    $burndown_application = new SprintApplication;
    $description = $burndown_application->getShortDescription();
    $this->assertEquals('Build Sprints', $description);
  }

  public function testgetEventListeners() {
    $burndown_application = new SprintApplication;
    $eventlistener = $burndown_application->getEventListeners();
    $this->assertInstanceOf('BurndownActionMenuEventListener', $eventlistener[0]);
    $this->assertInstanceOf('SprintUIEventListener', $eventlistener[1]);
  }

  public function testgetRoutes() {
    $burndown_application = new SprintApplication;
    $routes = $burndown_application->getRoutes();
    $assertion = array(
        '/project/sprint/' => array(
            'archive/(?P<id>[1-9]\d*)/'
            => 'PhabricatorProjectArchiveController',
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
            'board/task/edit/(?P<id>[1-9]\d*)/'
            =>  'SprintBoardTaskEditController',
            'board/task/create/'
            => 'SprintBoardTaskEditController',
            'board/(?P<id>[1-9]\d*)/'.
            '(?P<filter>filter/)?'.
            '(?:query/(?P<queryKey>[^/]+)/)?'
            => 'SprintBoardViewController',
            'burn/(?P<id>\d+)/' => 'SprintDataViewController',
            'details/(?P<id>[1-9]\d*)/'
            => 'PhabricatorProjectEditDetailsController',
            'edit/(?P<id>[1-9]\d*)/' => 'PhabricatorProjectEditMainController',
            '' => 'SprintListController',
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
            'profile/(?P<id>[1-9]\d*)/'
            => 'SprintProjectProfileController',
            'report/' => 'SprintListController',
            'report/list/' => 'SprintListController',
            'report/(?:(?P<view>\w+)/)?' => 'SprintReportController',
            'sboard/(?P<id>[1-9]\d*)/'.
            '(?P<filter>filter/)?'.
            '(?:query/(?P<queryKey>[^/]+)/)?'
            => 'SprintBoardViewController',
            'view/(?P<id>\d+)/' => 'SprintDataViewController',
        ),
        '/tag/' => array(
            '(?P<slug>[^/]+)/sboard/' => 'SprintBoardViewController',
        ),
    );
    $this->assertEquals($assertion, $routes);
  }
}
