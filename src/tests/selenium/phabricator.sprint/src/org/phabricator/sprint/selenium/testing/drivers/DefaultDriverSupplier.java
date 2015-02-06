package org.phabricator.sprint.selenium.testing.drivers;

import static org.phabricator.sprint.selenium.testing.DevMode.isInDevMode;

import java.lang.reflect.InvocationTargetException;
import java.util.logging.Logger;

import org.openqa.selenium.Capabilities;
import org.openqa.selenium.WebDriver;

import com.google.common.base.Supplier;
import com.google.common.base.Throwables;

public class DefaultDriverSupplier implements Supplier<WebDriver> {

  private static final Logger log = Logger.getLogger(DefaultDriverSupplier.class.getName());
  private Class<? extends WebDriver> driverClass;
  private final Capabilities desiredCapabilities;
  private final Capabilities requiredCapabilities;

  public DefaultDriverSupplier(Capabilities desiredCapabilities,
      Capabilities requiredCapabilities) {
    this.desiredCapabilities = desiredCapabilities;
    this.requiredCapabilities = requiredCapabilities;

    try {
      // Only support a default driver if we're actually in dev mode.
      if (isInDevMode()) {
        driverClass = Class.forName("org.openqa.selenium.htmlunit.HtmlUnitDriver")
            .asSubclass(WebDriver.class);
      } else {
        driverClass = null;
      }
    } catch (ClassNotFoundException e) {
      log.severe("Unable to find the default class on the classpath. Tests will fail");
    }
  }

  @Override
public WebDriver get() {
    log.info("Providing default driver instance");

    try {
      return driverClass.getConstructor(Capabilities.class, Capabilities.class).
          newInstance(desiredCapabilities, requiredCapabilities);
    } catch (InstantiationException e) {
      throw Throwables.propagate(e);
    } catch (IllegalAccessException e) {
      throw Throwables.propagate(e);
    } catch (NoSuchMethodException e) {
      throw Throwables.propagate(e);
    } catch (InvocationTargetException e) {
      throw Throwables.propagate(e.getTargetException());
    }
  }
}
