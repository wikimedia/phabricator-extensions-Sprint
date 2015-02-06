package org.phabricator.sprint.selenium.testing.drivers;

import java.util.logging.Logger;

public enum Browser {

  android,
  android_real_phone,
  chrome,
  ff,
  htmlunit {
    @Override
    public boolean isJavascriptEnabled() {
      return false;
    }
  },
  htmlunit_js,
  ie,
  ipad,
  iphone,
  none, // For those cases where you don't actually want a browser
  opera,
  opera_mobile,
  phantomjs,
  safari;

  private static final Logger log = Logger.getLogger(Browser.class.getName());

  public static Browser detect() {
    String browserName = "htmlunit";
    if (browserName == null) {
      log.info("No browser detected, returning null");
      return null;
    }

    try {
      return Browser.valueOf(browserName);
    } catch (IllegalArgumentException e) {
      log.severe("Cannot locate matching browser for: " + browserName);
      return null;
    }
  }

  public boolean isJavascriptEnabled() {
    return true;
  }

}
