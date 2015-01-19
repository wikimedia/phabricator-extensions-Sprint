<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

final class SprintApplication extends PhabricatorApplication {

  public function getName() {
    return pht('Sprint');
  }

  public function getBaseURI()
  {
      return '/project/sprint/';
  }

  public function getIconName() {
    return 'slowvote';
  }

  public function getShortDescription() {
    return 'Build Sprints';
  }

  public function getEventListeners() {
    return array(
      new BurndownActionMenuEventListener(),
      new SprintUIEventListener()
    );
  }

  public function getFactObjectsForAnalysis() {
    return array(
        new ManiphestTransaction(),
    );
  }

  public function getRoutes() {
      return array(
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
    );
  }
}
