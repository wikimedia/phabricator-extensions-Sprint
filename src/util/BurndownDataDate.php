<?php
/**
 * @author Michael Peters
 * @author Christopher Johnson
 * @license GPL version 3
 */

final class BurndownDataDate extends Phobject {

  private $date;
  private $tasks_added_today;
  private $tasks_closed_today;
  private $tasks_reopened_today;
  private $yesterday_tasks_remaining;
  private $points_added_today;
  private $points_closed_today;
  private $points_reopened_today;
  private $yesterday_points_remaining;

  // Totals over time
  private $tasks_total;
  private $tasks_remaining;
  private $points_total;
  private $points_remaining;
  private $points_ideal_remaining;

  public function __construct($date) {
    $this->date = $date;
  }

  public function setTasksAddedToday () {
    return $this->tasks_added_today = $this->tasks_added_today + 1;
  }

  public function getTasksAddedToday () {
    return $this->tasks_added_today;
  }

   public function setTasksClosedToday () {
    return $this->tasks_closed_today = $this->tasks_closed_today + 1;
  }

  public function getTasksClosedToday () {
    return $this->tasks_closed_today;
  }

  public function setTasksReopenedToday () {
      return $this->tasks_reopened_today = $this->tasks_reopened_today + 1;
  }

  public function getTasksReopenedToday () {
    return $this->tasks_reopened_today;
  }

  public function setTasksTotal($tasks_added_today) {
    $this->tasks_total = $tasks_added_today;
  }

  public function getTasksTotal() {
    return $this->tasks_total;
  }

  public function setTasksRemaining($tasks_remaining) {
    $this->tasks_remaining = $tasks_remaining;
  }

  public function setYesterdayTasksRemaining($yesterday_tasks_remaining) {
    $this->yesterday_tasks_remaining = $yesterday_tasks_remaining;
  }

  public function getYesterdayTasksRemaining() {
    return $this->yesterday_tasks_remaining;
  }

  public function getTasksRemaining() {
    return $this->tasks_remaining;
  }

  public function setPointsAddedToday ($points) {
    $this->points_added_today = $this->points_added_today + $points;
  }

  public function getPointsAddedToday () {
    return $this->points_added_today;
  }

  public function setPointsClosedToday ($points) {
    $this->points_closed_today = $this->points_closed_today + $points;
  }

  public function getPointsClosedToday () {
    return $this->points_closed_today;
  }

  public function setPointsReopenedToday ($points) {
    $this->points_reopened_today = $this->points_reopened_today + $points;
  }

  public function getPointsReopenedToday () {
    return $this->points_reopened_today;
  }

  public function getDate() {
    return $this->date;
  }

  public function setPointsTotal($points_total) {
    $this->points_total = $points_total;
  }

  public function getPointsTotal() {
    return $this->points_total;
  }

  public function setPointsRemaining($points_remaining) {
    $this->points_remaining = $points_remaining;
  }

  public function setYesterdayPointsRemaining($yesterday_points_remaining) {
    $this->yesterday_points_remaining = $yesterday_points_remaining;
  }

  public function getYesterdayPointsRemaining() {
    return $this->yesterday_points_remaining;
  }

  public function getPointsRemaining() {
    return $this->points_remaining;
  }

  public function getPointsIdealRemaining() {
    return $this->points_ideal_remaining;
  }

  public function setPointsIdealRemaining($points_total) {
    return $this->points_ideal_remaining = $points_total;
  }
}
