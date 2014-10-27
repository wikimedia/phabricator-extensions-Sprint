<?php

abstract class OpenTasksView {

private $commands = array();

  abstract public function execute( $tasks, $recently_closed, $date);

}