<?php
final class UserOpenTasksView extends OpenTasksView {

  /**
   * @param string $date
   */
  public function execute( $tasks, $recently_closed, $date) {
    $result = mgroup($tasks, 'getOwnerPHID');
    $leftover = idx($result, '', array());
    unset($result['']);

    $result_closed = mgroup($recently_closed, 'getOwnerPHID');
    $leftover_closed = idx($result_closed, '', array());
    unset($result_closed['']);

    $base_link = '/maniphest/?assigned=';
    $leftover_name = phutil_tag('em', array(), pht('(Up For Grabs)'));
    $col_header = pht('User');
    $header = pht('Open Tasks by User and Priority (%s)', $date);
    return array ( $leftover, $leftover_closed, $base_link, $leftover_name, $col_header, $header, $result_closed, $result );
  }
}