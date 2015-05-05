<?php
/**
 * @author Michael Peters
 * @author Christopher Johnson
 * @license GPL version 3
 */

final class SprintApplication extends PhabricatorApplication {

  public function getName() {
    return pht('Sprint');
  }

  public function getBaseURI() {
      return '/project/sprint/';
  }

  public function getIconName() {
    return 'fa-puzzle-piece';
  }

  public function getShortDescription() {
    return 'Build Sprints';
  }

  public function getEventListeners() {
    return array(
      new BurndownActionMenuEventListener(),
    );
  }

  public function getRoutes() {

    return array(
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
              'board/batch/'
              => 'SprintBoardBatchEditController',
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
  }

  protected function getCustomCapabilities() {
    return array(
        SprintDefaultViewCapability::CAPABILITY => array(
            'caption' => pht(
                'Default view policy for newly created sprints.'),
        ),
        ProjectCreateProjectsCapability::CAPABILITY => array(),
        ProjectCanLockProjectsCapability::CAPABILITY => array(
            'default' => PhabricatorPolicies::POLICY_ADMIN,
        ),
        ManiphestDefaultViewCapability::CAPABILITY => array(
            'caption' => pht('Default view policy for newly created tasks.'),
        ),
        ManiphestDefaultEditCapability::CAPABILITY => array(
            'caption' => pht('Default edit policy for newly created tasks.'),
        ),
        ManiphestEditStatusCapability::CAPABILITY => array(),
        ManiphestEditAssignCapability::CAPABILITY => array(),
        ManiphestEditPoliciesCapability::CAPABILITY => array(),
        ManiphestEditPriorityCapability::CAPABILITY => array(),
        ManiphestEditProjectsCapability::CAPABILITY => array(),
        ManiphestBulkEditCapability::CAPABILITY => array(),
    );
  }
}
