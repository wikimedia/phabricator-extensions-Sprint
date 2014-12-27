<?php
final class ProjectOpenTasksView extends OpenTasksView {

  /**
   * @param string $date
   */
  public function execute( $tasks, $recently_closed, $date) {
    $result = array();
    $leftover = array();
    foreach ($tasks as $task) {
      $phids = $task->getProjectPHIDs();
      if ($phids) {
        foreach ($phids as $project_phid) {
          $result[$project_phid][] = $task;
        }
      } else {
        $leftover[] = $task;
      }
    }

    $result_closed = array();
    $leftover_closed = array();
    foreach ($recently_closed as $task) {
      $phids = $task->getProjectPHIDs();
      if ($phids) {
        foreach ($phids as $project_phid) {
          $result_closed[$project_phid][] = $task;
        }
      } else {
        $leftover_closed[] = $task;
      }
    }

    $base_link = '/maniphest/?allProjects=';
    $leftover_name = phutil_tag('em', array(), pht('(No Project)'));
    $col_header = pht('Project');
    $header = pht('Open Tasks by Project and Priority (%s)', $date);

    return array ($leftover, $base_link, $leftover_name, $col_header, $header, $result_closed, $leftover_closed, $result );
  }

}
