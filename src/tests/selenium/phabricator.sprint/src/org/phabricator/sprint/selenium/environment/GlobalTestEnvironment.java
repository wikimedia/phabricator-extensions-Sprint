package org.phabricator.sprint.selenium.environment;

public class GlobalTestEnvironment {

	  private static TestEnvironment environment;

	  public static boolean isSetUp() {
	    return environment != null;
	  }

	  public static TestEnvironment get() {
	    return environment;
	  }

	  public static void set(TestEnvironment environment) {
	    GlobalTestEnvironment.environment = environment;
	  }

	  public static synchronized <T extends TestEnvironment> T get(
	      Class<T> startThisIfNothingIsAlreadyRunning) {
	    if (environment == null) {
	      try {
	        environment = startThisIfNothingIsAlreadyRunning.newInstance();
	      } catch (Exception e) {
	        throw new RuntimeException(e);
	      }
	    }
	    return (T) environment;
	  }

	}
