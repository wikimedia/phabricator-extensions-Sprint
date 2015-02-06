package org.phabricator.sprint.selenium.testing.drivers;

import static org.openqa.selenium.remote.CapabilityType.HAS_NATIVE_EVENTS;

import org.openqa.selenium.remote.DesiredCapabilities;

public class BrowserToCapabilities {
  public static DesiredCapabilities of(Browser browser) {
    if (browser == null) {
      return null;
    }

    DesiredCapabilities caps;

    switch (browser) {
      case android:
      case android_real_phone:
        caps = DesiredCapabilities.android();
        break;

      case chrome:
        caps = DesiredCapabilities.chrome();
        break;

      case ff:
        caps = DesiredCapabilities.firefox();
        break;

      case htmlunit:
        caps = DesiredCapabilities.htmlUnit();
        caps.setJavascriptEnabled(false);
        break;

      case htmlunit_js:
        caps = DesiredCapabilities.htmlUnit();
        caps.setJavascriptEnabled(true);
        break;

      case ie:
        caps = DesiredCapabilities.internetExplorer();
        break;

      case opera:
        caps = DesiredCapabilities.opera();
        break;

      case phantomjs:
        caps = DesiredCapabilities.phantomjs();
        break;

      case safari:
        caps = DesiredCapabilities.safari();
        break;

      case ipad:
        caps = DesiredCapabilities.ipad();
        break;

      case iphone:
        caps = DesiredCapabilities.iphone();
        break;

      default:
        throw new RuntimeException("Cannot determine browser config to use");
    }

    String version = System.getProperty("selenium.browser.version");
    if (version != null) {
      caps.setVersion(version);
    }

    caps.setCapability(HAS_NATIVE_EVENTS,
        Boolean.getBoolean("selenium.browser.native_events"));

    return caps;
  }
}
