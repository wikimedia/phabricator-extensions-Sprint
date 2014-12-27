<?php

final class SprintValidator {

  public function checkForSprint($showfields, $project) {
    $show = $showfields($project);
    if ($show === false) {
      return false;
    } else {
      return true;
    }
  }

  public function shouldShowSprintFields($project) {
    return (stripos($project->getName(), SprintConstants::MAGIC_WORD));
  }
}