<?php

$root = dirname(dirname(__FILE__));
require_once $root.'/constants/SprintConstants.php';
require_once $root.'/tests/Autoloader.php';
require_once SprintConstants::LIBPHUTIL_ROOT_DIR.'/src/internationalization/pht.php';
require_once SprintConstants::LIBPHUTIL_ROOT_DIR.'/src/utils/utils.php';
require_once SprintConstants::LIBPHUTIL_ROOT_DIR.'/src/moduleutils/core.php';
require_once SprintConstants::LIBPHUTIL_ROOT_DIR.'/src/moduleutils/moduleutils.php';
require_once SprintConstants::LIBPHUTIL_ROOT_DIR.'/src/markup/render.php';
AutoLoader::registerDirectory($root);
AutoLoader::registerDirectory(SprintConstants::PHABRICATOR_ROOT_DIR);
AutoLoader::registerDirectory(SprintConstants::LIBPHUTIL_ROOT_DIR);
