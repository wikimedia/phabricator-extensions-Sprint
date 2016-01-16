<?php

final class SprintException extends AphrontException {

  private $title;
  private $isUnlogged;

  public function __construct($title, $message, $unlogged = true) {
    $this->title = $title;
    $this->isUnlogged = $unlogged;
    parent::__construct($message);
  }

  public function getTitle() {
    return $this->title;
  }

  public function getIsUnlogged() {
    return $this->isUnlogged;
  }
}
