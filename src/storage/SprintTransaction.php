<?php

final class SprintTransaction extends PhabricatorApplicationTransaction {

  public function getApplicationTransactionType() {
    return DifferentialRevisionPHIDType::TYPECONST;
  }
}