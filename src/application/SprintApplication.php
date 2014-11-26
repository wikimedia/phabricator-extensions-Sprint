<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

final class SprintApplication extends PhabricatorApplication {

  public function getName() {
    return pht('Sprint');
  }

  public function getBaseURI() {
    return '/sprint/';
  }

  public function getIconName() {
    return 'slowvote';
  }

  public function getShortDescription() {
    return 'Build burndowns';
  }

  public function getEventListeners() {
    return array(
      new BurndownActionMenuEventListener()
    );
  }

  public function getRoutes() {
      return array(
          '/project/' => array(
              'view/(?P<id>[1-9]\d*)/'
              => 'SprintProjectProfileController',
          ),
          '/sprint/' => array(
              'edit/(?P<id>[1-9]\d*)/' => 'PhabricatorProjectEditMainController',
              '' => 'BurndownListController',
              'report/' => 'BurndownListController',
              'report/list/' => 'BurndownListController',
              'report/(?:(?P<view>\w+)/)?' => 'SprintReportController',
              'view/(?P<id>\d+)/' => 'BurndownDataViewController',
              'details/(?P<id>[1-9]\d*)/'
              => 'PhabricatorProjectEditDetailsController',
              'archive/(?P<id>[1-9]\d*)/'
              => 'PhabricatorProjectArchiveController',
              'members/(?P<id>[1-9]\d*)/'
              => 'PhabricatorProjectMembersEditController',
              'members/(?P<id>[1-9]\d*)/remove/'
              => 'PhabricatorProjectMembersRemoveController',
              'move/(?P<id>[1-9]\d*)/' => 'SprintBoardMoveController',
              'picture/(?P<id>[1-9]\d*)/'
              => 'PhabricatorProjectEditPictureController',
              'icon/(?P<id>[1-9]\d*)/'
              => 'PhabricatorProjectEditIconController',
              'board/task/edit/(?P<id>[1-9]\d*)/'
              =>  'SprintBoardTaskEditController',
              'board/task/create/'
              => 'SprintBoardTaskEditController',
              'board/(?P<id>[1-9]\d*)/' .
              '(?P<filter>filter/)?' .
              '(?:query/(?P<queryKey>[^/]+)/)?'
              => 'SprintBoardViewController',
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
          ),
          '/tag/' => array(
              '(?P<slug>[^/]+)/' => 'SprintProjectProfileController',
              '(?P<slug>[^/]+)/board/' => 'SprintBoardViewController',
          ),
      );
    }

  protected function getCustomCapabilities() {
    return array(
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
