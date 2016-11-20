<?php

final class SprintValidator extends Phobject {

  public function checkForSprint($showfields, $project_phid) {
    $show = call_user_func(array($showfields[0],$showfields[1]),$project_phid);
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
