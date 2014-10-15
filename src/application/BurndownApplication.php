<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

final class BurndownApplication extends PhabricatorApplication {

  public function getName() {
    return pht('Sprint');
  }

  public function getBaseURI() {
    return '/sprint/report/';
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
      '/sprint/' => array(
        '' => 'BurndownListController',
        'report/' => 'BurndownListController',
        'report/list/' => 'BurndownListController',
        'report/(?:(?P<view>\w+)/)?' => 'SprintReportController',
        'view/(?P<id>\d+)/' => 'BurndownDataViewController',
      ),
    );
  }

}
