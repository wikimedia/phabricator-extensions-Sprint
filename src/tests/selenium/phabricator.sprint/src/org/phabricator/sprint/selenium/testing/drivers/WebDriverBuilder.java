package org.phabricator.sprint.selenium.testing.drivers;

import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;
import java.util.List;
import java.util.logging.Level;

import org.openqa.selenium.Capabilities;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.remote.DesiredCapabilities;

import com.google.common.base.Supplier;
import com.google.common.base.Throwables;
import com.google.common.collect.Lists;

public class WebDriverBuilder implements Supplier<WebDriver> {
  private Capabilities desiredCapabilities;
  private Capabilities requiredCapabilities;
  private final Browser browser;

  public WebDriverBuilder() {
    this(Browser.detect());
  }

  public WebDriverBuilder(Browser browser) {
    this.browser = browser;
  }

  @Override
public WebDriver get() {
    Capabilities standardCapabilities = BrowserToCapabilities.of(browser);
    Capabilities desiredCaps = new DesiredCapabilities(standardCapabilities,
        desiredCapabilities);

    List<Supplier<WebDriver>> suppliers = getSuppliers(desiredCaps,
        requiredCapabilities);

    for (Supplier<WebDriver> supplier : suppliers) {
      WebDriver driver = supplier.get();
      if (driver != null) {
        modifyLogLevel(driver);
        return driver;
      }
    }

    throw new RuntimeException("Cannot instantiate driver instance: " + desiredCapabilities);
  }

  private void modifyLogLevel(WebDriver driver) {
    Class<?>[] args = {Level.class};
    Method setLogLevel;
    try {
      setLogLevel = driver.getClass().getMethod("setLogLevel", args);

      String value = System.getProperty("selenium.browser.log_level", "INFO");
      LogLevel level = LogLevel.valueOf(value);
      setLogLevel.invoke(driver, level.getLevel());
    } catch (NoSuchMethodException e) {
      return;
    } catch (InvocationTargetException e) {
      throw Throwables.propagate(e);
    } catch (IllegalAccessException e) {
      throw Throwables.propagate(e);
    }
  }

  private List<Supplier<WebDriver>> getSuppliers(Capabilities desiredCaps,
      Capabilities requiredCaps) {
    List<Supplier<WebDriver>> suppliers = Lists.newArrayList();
    suppliers.add(new DefaultDriverSupplier(desiredCaps, requiredCaps));
    return suppliers;
  }

  public WebDriverBuilder setDesiredCapabilities(Capabilities caps) {
    this.desiredCapabilities = caps;
    return this;
  }

  public WebDriverBuilder setRequiredCapabilities(Capabilities caps) {
    this.requiredCapabilities = caps;
    return this;
  }

  private enum LogLevel {
    OFF("OFF", Level.OFF),
    DEBUG("DEBUG", Level.FINE),
    INFO("INFO", Level.INFO),
    WARNING("WARNING", Level.WARNING),
    ERROR("ERROR", Level.SEVERE);

    private final String value;
    private final Level level;

    LogLevel(String value, Level level) {
      this.value = value;
      this.level = level;
    }

    public Level getLevel() {
      return level;
    }
  }
}
