<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

class BurndownDataDate {

  private $date;
  private $tasks_added_today;
  private $tasks_closed_today;
  private $tasks_removed_today;
  private $tasks_reopened_today;
  private $points_added_today;
  private $points_closed_today;
  private $points_removed_today;
  private $points_reopened_today;

  // Totals over time
  private $tasks_total;
  private $tasks_remaining;
  private $points_total;
  private $points_remaining;
  private $points_ideal_remaining;

  public function __construct($date) {
    $this->date = $date;
    return $this;
  }


  public function setTasksAddedToday () {
    return $this->tasks_added_today = $this->tasks_added_today +1;
  }

  public function getTasksAddedToday () {
    return $this->tasks_added_today;
  }

  public function setTasksRemovedToday () {
    return $this->tasks_removed_today = $this->tasks_removed_today + 1;
  }

  public function getTasksRemovedToday () {
    return $this->tasks_removed_today;
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

  public function setPointsRemovedToday ($points) {
    $this->points_removed_today = $this->points_removed_today + $points;
  }

  public function getPointsRemovedToday () {
    return $this->points_removed_today;
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

  public function getPointsRemaining() {
    return $this->points_remaining;
  }

  public function getPointsIdealRemaining() {
    return $this->points_ideal_remaining;
  }

  public function setPointsIdealRemaining($points_total) {
    return $this->points_ideal_remaining = $points_total;
  }

  public function sumTasksTotal($current, $previous) {
    $current->tasks_total += $previous->tasks_total;
    return $current->tasks_total ;
  }

  public function sumPointsTotal($current, $previous) {
    $current->points_total += $previous->points_total;
    return $current->points_total;
  }

  public function sumTasksRemaining($added, $closed) {
    $this->tasks_remaining = $added - $closed;
    return $this->tasks_remaining;
  }

  public function sumPointsRemaining($current, $previous) {
   $current->points_remaining = $previous->points_remaining - $current->points_remaining;
    return $current->points_remaining;
  }

  public function changePoints($points, $old_point_value, $dates) {
    // Adjust points for that day
    $points = $points - $old_point_value;
    return $this->setPointsAddedToday($points);
  }

}
