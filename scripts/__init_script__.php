<?php

function init_phabricator_script() {

  phutil_load_library('/srv/phab/libphutil/src');
  phutil_load_library('/srv/phab/phabricator/src');
  phutil_load_library('/srv/phab/Sprint/src');
  phutil_load_library('/srv/phab/arcanist/src');


  PhabricatorEnv::initializeScriptEnvironment();
}

init_phabricator_script();
