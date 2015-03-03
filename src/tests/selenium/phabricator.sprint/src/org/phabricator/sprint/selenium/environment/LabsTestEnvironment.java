package org.phabricator.sprint.selenium.environment;

import org.phabricator.sprint.selenium.environment.webserver.AppServer;
import org.phabricator.sprint.selenium.environment.webserver.PhabricatorAppServer;
import org.openqa.selenium.net.NetworkUtils;
import org.phabricator.sprint.selenium.testing.drivers.Browser;

public class LabsTestEnvironment implements TestEnvironment {

  private AppServer appServer;

  public LabsTestEnvironment() {
    String servingHost = getServingHost();
    appServer = servingHost == null ? new PhabricatorAppServer() : new PhabricatorAppServer(servingHost);
  }

  public AppServer getAppServer() {
    return appServer;
  }


  public static void main(String[] args) {
    new LabsTestEnvironment();
  }
  
  private String getServingHost() {
    Browser browser = Browser.detect();
    if (browser == Browser.android) {
      return "10.0.2.2";
    }
    if (browser == Browser.android_real_phone) {
      return new NetworkUtils().getIp4NonLoopbackAddressOfThisMachine().getHostName();
    }
    return null;
  }
}
