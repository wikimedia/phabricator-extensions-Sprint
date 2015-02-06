package org.phabricator.sprint.selenium.testing;

public class DevMode {
	  public static boolean isInDevMode() {
	    return isInDevMode("/org/openqa/selenium/firefox/webdriver.xpi");
	  }

	  public static boolean isInDevMode(String nameOfRequiredResource) {
	    return isInDevMode(DevMode.class, nameOfRequiredResource);
	  }

	  public static boolean isInDevMode(Class<?> resourceLoaderClazz, String nameOfRequiredResource) {
	    return resourceLoaderClazz.getResource(nameOfRequiredResource) == null &&
	        resourceLoaderClazz.getResource("/" + nameOfRequiredResource) == null;
	  }
	}
