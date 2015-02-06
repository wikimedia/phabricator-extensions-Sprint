package org.phabricator.sprint.selenium.testing;

import com.google.common.base.Throwables;
import static org.phabricator.sprint.selenium.testing.DevMode.isInDevMode;
import org.junit.internal.runners.model.ReflectiveCallable;
import org.junit.internal.runners.statements.Fail;
import org.junit.runner.Description;
import org.junit.runner.notification.RunNotifier;
import org.junit.runners.BlockJUnit4ClassRunner;
import org.junit.runners.model.FrameworkMethod;
import org.junit.runners.model.InitializationError;
import org.junit.runners.model.Statement;
import org.phabricator.sprint.selenium.testing.drivers.Browser;
import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;

public class SeleniumTestRunner extends BlockJUnit4ClassRunner {


  /**
   * Creates a BlockJUnit4ClassRunner to run {@code klass}
   *
   * @param klass The class under test
   * @throws org.junit.runners.model.InitializationError
   *          if the test class is malformed.
   */
  public SeleniumTestRunner(Class<?> klass) throws InitializationError {
    super(klass);

    Browser browser = Browser.detect();
    if (browser == null && isInDevMode()) {
      browser = Browser.ff;
    }

  }
}
