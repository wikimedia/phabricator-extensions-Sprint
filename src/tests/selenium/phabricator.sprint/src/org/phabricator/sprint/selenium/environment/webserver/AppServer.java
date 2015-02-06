package org.phabricator.sprint.selenium.environment.webserver;

public interface AppServer {

	  String getHostName();

	  String getAlternateHostName();

	  String whereIs(String relativeUrl);

	  String whereElseIs(String relativeUrl);

	  String whereIsSecure(String relativeUrl);

	  String whereIsWithCredentials(String relativeUrl, String user, String password);

	}
