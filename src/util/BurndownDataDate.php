<?php
/**
 * Copyright (C) 2014 Michael Peters
 * Licensed under GNU GPL v3. See LICENSE for full details
 */

class BurndownDataDate {

  private $date;

  // Tasks and points added and closed today
  public $tasks_added_today = 0;
  public $tasks_closed_today = 0;
  public $points_added_today = 0;
  public $points_closed_today = 0;

  // Totals over time
  public $tasks_total = 0;
  public $tasks_remaining = 0;
  public $points_total = 0;
  public $points_remaining = 0;
  public $points_ideal_remaining = 0;

  public function __construct($date) {
    $this->date = $date;

    return $this;
  }

 /**
  * @return string|null
  */

  public function getDate() {
    return $this->date;
  }
}