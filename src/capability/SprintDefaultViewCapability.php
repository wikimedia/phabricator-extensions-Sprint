<?php

final class SprintDefaultViewCapability
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'sprint.default.view';

  public function getCapabilityName() {
    return pht('Default View Policy');
  }

  public function shouldAllowPublicPolicySetting() {
    return true;
  }
}
