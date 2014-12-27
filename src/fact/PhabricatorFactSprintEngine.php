<?php

/**
 * New fact engine for Sprint that looks at Maniphest Transactions.
 */
final class PhabricatorFactSprintEngine extends PhabricatorFactEngine {

  public function getFactSpecs(array $fact_types) {
    $results = array();
    foreach ($fact_types as $type) {
      if (!strncmp($type, '+N:', 3)) {
        if ($type == '+N:*') {
          $name = 'Total Objects';
        } else {
          $name = 'Total Objects of type '.substr($type, 3);
        }

        $results[] = id(new PhabricatorFactSimpleSpec($type))
          ->setName($name)
          ->setUnit(PhabricatorFactSimpleSpec::UNIT_COUNT);
      }

      if (!strncmp($type, 'N:', 2)) {
        if ($type == 'N:*') {
          $name = 'Objects';
        } else {
          $name = 'Objects of type '.substr($type, 2);
        }
        $results[] = id(new PhabricatorFactSimpleSpec($type))
          ->setName($name)
          ->setUnit(PhabricatorFactSimpleSpec::UNIT_COUNT);
      }

    }
    return $results;
  }

  public function shouldComputeRawFactsForObject(PhabricatorLiskDAO $object) {
    return true;
  }

  public function computeRawFactsForObject(PhabricatorLiskDAO $object) {
    $facts = array();

    $phid = $object->getPHID();
    $type = phid_get_type($phid);

    if ($object instanceof ManiphestTransaction) {
      $xacttype = $object->getTransactionType();
      if ($xacttype == 'core:customfield') {
        $oldvalue = $object->getOldValue();
        $newvalue = $object->getNewValue();
        $objectPHID = $object->getObjectPHID();
        foreach (array('N:*', 'N:' . $xacttype) as $fact_type) {
          $facts[] = id(new PhabricatorFactRaw())
              ->setFactType($fact_type)
              ->setObjectPHID($objectPHID)
              ->setValueX($oldvalue)
              ->setValueY($newvalue)
              ->setEpoch($object->getDateCreated());
        }
      } elseif ($xacttype == 'status') {
        $oldstatus = null;
        $newstatus = null;

        if ($object->getOldValue() == 'open') {
          $oldstatus = 1;
        } elseif ($object->getOldValue() == 'resolved') {
          $oldstatus = 0;
        }

        if ($object->getNewValue() == 'open') {
          $newstatus = 1;
        } elseif ($object->getNewValue() == 'resolved') {
          $newstatus = 0;
        }

        $objectPHID = $object->getObjectPHID();
        foreach (array('N:*', 'N:' . $xacttype) as $fact_type) {
          $facts[] = id(new PhabricatorFactRaw())
              ->setFactType($fact_type)
              ->setObjectPHID($objectPHID)
              ->setValueX($oldstatus)
              ->setValueY($newstatus)
              ->setEpoch($object->getDateCreated());
        }
      }
    } else {
        foreach (array('N:*', 'N:'.$type) as $fact_type) {
          $facts[] = id(new PhabricatorFactRaw())
              ->setFactType($fact_type)
              ->setObjectPHID($phid)
              ->setValueX(1)
              ->setValueY()
              ->setEpoch($object->getDateCreated());
        }
    }

    return $facts;
  }

  public function shouldComputeAggregateFacts() {
    return true;
  }

  public function computeAggregateFacts() {
    $table = new PhabricatorFactRaw();
    $table_name = $table->getTableName();
    $conn = $table->establishConnection('r');

    $counts = queryfx_all(
      $conn,
      'SELECT factType, SUM(valueX) N FROM %T WHERE factType LIKE %>
        GROUP BY factType',
      $table_name,
      'N:');

    $facts = array();
    foreach ($counts as $count) {
      $facts[] = id(new PhabricatorFactAggregate())
        ->setFactType('+'.$count['factType'])
        ->setValueX($count['N']);
    }

    return $facts;
  }


}
