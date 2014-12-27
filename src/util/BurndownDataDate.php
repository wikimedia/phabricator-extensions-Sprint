<?php
/**
 * @author Michael Peters
 * @license GPL version 3
 */

class BurndownDataDate {

  private $date;
  private $tasks_added_today;
  private $tasks_closed_today;
  private $tasks_reopened_today;
  private $yesterday_tasks_remaining;
  private $points_added_today;
  private $points_closed_today;
  private $points_reopened_today;
  private $yesterday_points_remaining;
  private $tasks_added_before;
  private $tasks_closed_before;
  private $tasks_reopened_before;
  private $points_added_before;
  private $points_closed_before;
  private $points_reopened_before;

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

  public function setTasksAddedBefore () {
    return $this->tasks_added_before = $this->tasks_added_before + 1;
  }

  public function getTasksAddedBefore () {
    return $this->tasks_added_before;
  }

  public function setTasksClosedBefore () {
    return $this->tasks_closed_before = $this->tasks_closed_before + 1;
  }

  public function getTasksClosedBefore () {
    return $this->tasks_closed_before;
  }

  public function setTasksReopenedBefore () {
    return $this->tasks_reopened_before = $this->tasks_reopened_before + 1;
  }

  public function getTasksReopenedBefore () {
    return $this->tasks_reopened_before;
  }

  public function getTasksForwardfromBefore () {
    return $this->tasks_added_before + $this->tasks_reopened_before - $this->tasks_closed_before;
  }

  public function setTasksAddedToday () {
    return $this->tasks_added_today = $this->tasks_added_today +1;
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

  public function setPointsAddedBefore ($points) {
    $this->points_added_before = $this->points_added_before + $points;
  }

  public function getPointsAddedBefore () {
    return $this->points_added_before;
  }

  public function setPointsClosedBefore ($points) {
    $this->points_closed_before = $this->points_closed_before + $points;
  }

  public function getPointsClosedBefore () {
    return $this->points_closed_before;
  }

  public function setPointsReopenedBefore ($points) {
    $this->points_reopened_before = $this->points_reopened_before + $points;
  }

  public function getPointsReopenedBefore () {
    return $this->points_reopened_before;
  }

  public function getPointsForwardfromBefore () {
    return $this->points_added_before + $this->points_reopened_before - $this->points_closed_before;
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

  public function sumTasksTotal($current, $previous) {
    $current->tasks_total += $previous->tasks_total;
    return $current->tasks_total ;
  }

  public function sumPointsTotal($current, $previous) {
    $current->points_total += $previous->points_total;
    return $current->points_total;
  }

  public function sumTasksRemaining($current, $previous) {
    $current->tasks_remaining = $previous->tasks_remaining - $current->tasks_closed_today;
    return $current->tasks_remaining;
  }

  public function sumPointsRemaining($current, $previous) {
    $current->points_remaining = $previous->points_remaining - $current->points_closed_today;
    return $current->points_remaining;
  }

}
