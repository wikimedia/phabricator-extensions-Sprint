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

  public function getRoutes() {
      return array(
        // this is the default application route controller
          '/project/sprint/' => array(
              '' => 'SprintListController',
            // these are native Sprint application controllers
              'burn/(?P<id>\d+)/' => 'SprintDataViewController',
              'profile/(?P<id>[1-9]\d*)/'
              => 'SprintProjectProfileController',
              'report/list/' => 'SprintListController',
              'report/history/' => 'SprintHistoryController',
              'report/(?:(?P<view>\w+)/)?' => 'SprintReportController',
              'view/(?P<id>\d+)/' => 'SprintDataViewController',
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
        )
        /*
        ,
        ManiphestEditAssignCapability::CAPABILITY => array(),
        ManiphestEditPoliciesCapability::CAPABILITY => array(),
        ManiphestEditPriorityCapability::CAPABILITY => array(),
        ManiphestEditProjectsCapability::CAPABILITY => array(),
        ManiphestBulkEditCapability::CAPABILITY => array(),
        */
    );
  }
}
