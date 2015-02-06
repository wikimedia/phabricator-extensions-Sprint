package org.phabricator.sprint.selenium.environment;

import org.phabricator.sprint.selenium.environment.webserver.AppServer;

public interface TestEnvironment {

  AppServer getAppServer();

}
