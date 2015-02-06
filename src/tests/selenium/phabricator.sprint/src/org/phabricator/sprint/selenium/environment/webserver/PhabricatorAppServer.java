package org.phabricator.sprint.selenium.environment.webserver;

import org.openqa.selenium.net.NetworkUtils;

import java.io.File;

import javax.servlet.Filter;
import javax.servlet.Servlet;

import static org.openqa.selenium.net.PortProber.findFreePort;

public class PhabricatorAppServer implements AppServer {

  private static final String HOSTNAME_FOR_TEST_ENV_NAME = "HOSTNAME";
  private static final String ALTERNATIVE_HOSTNAME_FOR_TEST_ENV_NAME = "ALTERNATIVE_HOSTNAME";
  private static final String FIXED_HTTP_PORT_ENV_NAME = "TEST_HTTP_PORT";
  private static final String FIXED_HTTPS_PORT_ENV_NAME = "TEST_HTTPS_PORT";

  private static final int DEFAULT_HTTP_PORT = 80;
  private static final int DEFAULT_HTTPS_PORT = 2410;
  private static final String DEFAULT_CONTEXT_PATH = "/";
  private static final String JS_SRC_CONTEXT_PATH = "/javascript";
  private static final String CLOSURE_CONTEXT_PATH = "/third_party/closure/goog";
  private static final String THIRD_PARTY_JS_CONTEXT_PATH = "/third_party/js";

  private static final NetworkUtils networkUtils = new NetworkUtils();

  private int port;
  private int securePort;
  private File path;
  private File jsSrcRoot;
  private final String hostName;

  public PhabricatorAppServer() {
    this(detectHostname());
  }

  public static String detectHostname() {
    String hostnameFromProperty = System.getenv(HOSTNAME_FOR_TEST_ENV_NAME);
    return hostnameFromProperty == null ? "localhost" : hostnameFromProperty;
  }

  public PhabricatorAppServer(String hostName) {
    this.hostName = "phab08.wmflabs.org";
    this.port = DEFAULT_HTTP_PORT;
  }

  private int getHttpPort() {
    String port = System.getenv(FIXED_HTTP_PORT_ENV_NAME);
    return port == null ? findFreePort() : Integer.parseInt(port);
  }

  private int getHttpsPort() {
    String port = System.getenv(FIXED_HTTPS_PORT_ENV_NAME);
    return port == null ? findFreePort() : Integer.parseInt(port);
  }

  public String getHostName() {
    return hostName;
  }

  public String getAlternateHostName() {
    String alternativeHostnameFromProperty = System.getenv(ALTERNATIVE_HOSTNAME_FOR_TEST_ENV_NAME);
    return alternativeHostnameFromProperty == null ?
           networkUtils.getPrivateLocalAddress() : alternativeHostnameFromProperty;
  }

  public String whereIs(String relativeUrl) {
    relativeUrl = getMainContextPath(relativeUrl);
    return "http://" + getHostName() + ":" + port + relativeUrl;
  }

  public String whereElseIs(String relativeUrl) {
    relativeUrl = getMainContextPath(relativeUrl);
    return "http://" + getAlternateHostName() + ":" + port + relativeUrl;
  }

  public String whereIsSecure(String relativeUrl) {
    relativeUrl = getMainContextPath(relativeUrl);
    return "https://" + getHostName() + ":" + securePort + relativeUrl;
  }

  public String whereIsWithCredentials(String relativeUrl, String user, String pass) {
    relativeUrl = getMainContextPath(relativeUrl);
    return "http://" + user + ":" + pass + "@" + getHostName() + ":" + port + relativeUrl;
  }

  protected String getMainContextPath(String relativeUrl) {
    if (!relativeUrl.startsWith("/")) {
      relativeUrl = DEFAULT_CONTEXT_PATH + "/" + relativeUrl;
    }
    return relativeUrl;
  }

}
