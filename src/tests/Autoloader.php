<?php

class AutoLoader {

  private static $classNames = array();

  /**
   * Store the filename (sans extension) & full path of all ".php" files found
   */
  public static function registerDirectory($dirName) {

    $di = new DirectoryIterator($dirName);
    foreach ($di as $file) {

      if ($file->isDir() && !$file->isLink() && !$file->isDot()) {
        // recurse into directories other than a few special ones
        self::registerDirectory($file->getPathname());
      } else if (substr($file->getFilename(), -4) === '.php') {
        // save the class name / path of a .php file found
        $className = substr($file->getFilename(), 0, -4);
        self::registerClass($className, $file->getPathname());
      }
    }
  }

  /**
   * @param string $className
   * @param string $fileName
   */
  public static function registerClass($className, $fileName) {
    self::$classNames[$className] = $fileName;
  }

  public static function loadClass($className) {
    if (isset(self::$classNames[$className])) {
      require_once self::$classNames[$className];
    }
  }

}

spl_autoload_register(array('AutoLoader', 'loadClass'));
