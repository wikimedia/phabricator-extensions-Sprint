<?php

abstract class OpenTasksView {

  abstract public function execute($tasks, $recently_closed, $date);

}
