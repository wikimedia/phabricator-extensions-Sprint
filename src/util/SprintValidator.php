<?php

final class SprintValidator extends Phobject {

  public function checkForSprint($showfields, $project_phid) {
    $show = $showfields($project_phid);
    if ($show == false) {
      return false;
    } else {
      return true;
    }
  }

  public function isSprint($project_phid) {
    $query = id(new SprintQuery())
        ->setPHID($project_phid);
    $issprint = $query->getIsSprint();
    return $issprint;
  }
}
