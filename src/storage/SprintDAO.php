<?php

abstract class SprintDAO extends PhabricatorLiskDAO {

  public function getApplicationName() {
    return 'sprint';
  }

  protected function getConfiguration() {
    return array(
        self::CONFIG_NO_TABLE => true,
    ) + parent::getConfiguration();
  }
}
