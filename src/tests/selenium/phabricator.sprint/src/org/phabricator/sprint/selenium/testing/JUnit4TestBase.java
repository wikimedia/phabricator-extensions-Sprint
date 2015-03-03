package org.phabricator.sprint.selenium.testing;

import java.util.logging.Logger;

import org.junit.Before;
import org.junit.Rule;
import org.junit.rules.TestRule;
import org.junit.rules.TestWatcher;
import org.junit.runner.Description;
import org.junit.runner.RunWith;
import org.phabricator.sprint.selenium.Pages;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.htmlunit.HtmlUnitDriver;
import org.phabricator.sprint.selenium.environment.GlobalTestEnvironment;
import org.phabricator.sprint.selenium.environment.LabsTestEnvironment;
import org.phabricator.sprint.selenium.environment.TestEnvironment;
import org.phabricator.sprint.selenium.environment.webserver.AppServer;

import org.phabricator.sprint.selenium.testing.drivers.WebDriverBuilder;

import static org.hamcrest.Matchers.equalTo;
import static org.hamcrest.Matchers.is;
import static org.hamcrest.core.IsNot.not;
import static org.junit.Assert.assertThat;

@RunWith(SeleniumTestRunner.class)
public abstract class JUnit4TestBase {

  private static final Logger logger = Logger.getLogger(JUnit4TestBase.class.getName());

  protected TestEnvironment environment;
  protected AppServer appServer;
  protected Pages pages;
  private static ThreadLocal<WebDriver> storedDriver = new ThreadLocal<WebDriver>();
  protected WebDriver driver;

  @Before
  public void prepareEnvironment() throws Exception {
    environment = GlobalTestEnvironment.get(LabsTestEnvironment.class);
    appServer = environment.getAppServer();

    pages = new Pages(appServer);

    String hostName = environment.getAppServer().getHostName();
    String alternateHostName = environment.getAppServer().getAlternateHostName();

    assertThat(hostName, is(not(equalTo(alternateHostName))));
  }

  @Before
  public void createDriver() throws Exception {
    driver = new HtmlUnitDriver();
  }

  @Rule
  public TestRule traceMethodName = new TestWatcher() {
    @Override
    protected void starting(Description description) {
      super.starting(description);
      logger.info(">>> Starting " + description);
    }

    @Override
    protected void finished(Description description) {
      super.finished(description);
      logger.info("<<< Finished " + description);
    }
  };
  
  public WebDriver getWrappedDriver() {
    return storedDriver.get();
  }

  public static WebDriver actuallyCreateDriver() {
    WebDriver driver = storedDriver.get();

    if (driver == null) {
      driver = new WebDriverBuilder().get();
      storedDriver.set(driver);
    }
    return storedDriver.get();
  }

  public static void removeDriver() {
    if (Boolean.getBoolean("webdriver.singletestsuite.leaverunning")) {
      return;
    }

    WebDriver current = storedDriver.get();

    if (current == null) {
      return;
    }

    try {
      current.quit();
    } catch (RuntimeException ignored) {
      // fall through
    }

    storedDriver.remove();
  }

  protected boolean isIeDriverTimedOutException(IllegalStateException e) {
    // The IE driver may throw a timed out exception
    return e.getClass().getName().contains("TimedOutException");
  }

}
