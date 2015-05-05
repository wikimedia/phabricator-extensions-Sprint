package org.phabricator.sprint.selenium;

import static org.hamcrest.MatcherAssert.assertThat;
import static org.hamcrest.Matchers.equalTo;

import java.util.Arrays;
import java.util.Collection;

import org.junit.Test;
import org.junit.runner.RunWith;
import org.junit.runners.Parameterized;
import org.openqa.selenium.By;
import org.openqa.selenium.WebElement;
import org.phabricator.sprint.selenium.testing.JUnit4TestBase;

@RunWith(value = Parameterized.class)
public class SprintTestCase extends JUnit4TestBase {
	private final String projectName;
	private final String projectId;
	private final String boardColumnId;
	private final String elementId;
	private final String classValue;

	public SprintTestCase(String projectName, String projectId, String boardColumnId, String elementId, String classValue) {
		this.projectName = projectName;
		this.projectId = projectId;
		this.boardColumnId = boardColumnId;
		this.elementId = elementId;
		this.classValue = classValue;
	}

	@Parameterized.Parameters
	public static Collection<Object[]> params() {
		return Arrays.asList(new Object[][] {
				{ "new_test_sprint", "27", "35", "UQ0_0", "phui-workboard-view " },
		});
	}

	@Test
	public void test01() {
		driver.get(pages.SprintProjectList);
		WebElement div = driver.findElement(By.id(elementId));
		String attribute = div.getAttribute("class");
		assertThat(attribute, equalTo("phabricator-nav-content plb"));
		System.out.print("SprintListController is executed\n");
	}

	@Test
	public void test02() {
		driver.get((pages.SprintProjectTag) + projectName);
		WebElement div = driver.findElement(By.id(elementId));
		String attribute = div.getAttribute("class");
		assertThat(attribute, equalTo(classValue));
		System.out.print("/tag/<slug> route for " + projectName + " to SprintBoardViewController is executed\n");
	}

	@Test
	public void test03() {
		driver.get((pages.SprintProjectTag) + projectName + "/board");
		WebElement div = driver.findElement(By.id(elementId));
		String attribute = div.getAttribute("class");
		assertThat(attribute, equalTo(classValue));
		System.out.print("/tag/<slug>/board route for " + projectName + " to SprintBoardViewController is executed\n");
	}

	@Test
	public void test04() {
		driver.get((pages.SprintProjectBoard) + projectId);
		WebElement div = driver.findElement(By.id(elementId));
		String attribute = div.getAttribute("class");
		assertThat(attribute, equalTo(classValue));
		System.out.print("/project/sprint/board/<id> route for " + projectName + " to SprintBoardViewController is executed\n");
		driver.quit();
	}

	@Test
	public void test05() {
		driver.get((pages.SprintProjectBoard) + projectId + "/column/" + boardColumnId + "/");
		WebElement div = driver.findElement(By.xpath("//div[contains(@class, 'phui-property-list-actions')]"));
		String attribute = div.getAttribute("class");
		assertThat(attribute, equalTo("phui-property-list-actions"));
		System.out.print("/project/sprint/board/<id>/column/<columnid> route to SprintBoardColumnDetailController is executed\n");
		driver.quit();
	}

	@Test
	public void test06() {
		driver.get((pages.SprintProjectBurn) + projectId + "/");
		WebElement div = driver.findElement(By.id("UQ0_2"));
		String attribute = div.getAttribute("class");
		assertThat(attribute, equalTo("phabricator-nav-local phabricator-side-menu"));
		System.out.print("/project/sprint/burn/<id> route to SprintDataViewController is executed\n");
		driver.quit();
	}

	@Test
	public void test07() {
		driver.get((pages.SprintProjectReport) + "project/");
		WebElement table = driver.findElement(By.tagName("table"));
		String attribute = table.getAttribute("class");
		assertThat(attribute, equalTo("aphront-table-view"));
		System.out.print("/project/sprint/report/project route to SprintReportController Project View is executed\n");
		driver.quit();
	}

	@Test
	public void test08() {
		driver.get((pages.SprintProjectReport) + "user/");
		WebElement table = driver.findElement(By.tagName("table"));
		String attribute = table.getAttribute("class");
		assertThat(attribute, equalTo("aphront-table-view"));
		System.out.print("/project/sprint/report/user route to SprintDataViewController User View is executed\n");
		driver.quit();
	}

	@Test
	public void test09() {
		driver.get((pages.SprintProjectReport) + "burn/");
		WebElement div = driver.findElement(By.xpath("//div[contains(@class, 'aphront-form-control')]"));
		String attribute = div.getAttribute("class");
		assertThat(attribute, equalTo("aphront-form-control grouped aphront-form-control-tokenizer"));
		System.out.print("/project/sprint/report/burn route to SprintDataViewController Burn View is executed\n");
		driver.quit();
	}

	@Test
	public void test10() {
		driver.get((pages.SprintPhabricatorProjectFeed) + projectId);
		WebElement div = driver.findElement(By.xpath("//div[contains(@class, 'phui-feed-story-head')]"));
		String attribute = div.getAttribute("class");
		assertThat(attribute, equalTo("phui-feed-story-head"));
		System.out.print("/project/sprint/feed/<id> route to PhabricatorProjectFeedController is executed\n");
		driver.quit();
	}

	@Test
	public void test11() {
		driver.get((pages.SprintProjectProfile) + projectId);
		WebElement div = driver.findElement(By.xpath("//div[contains(@class, 'phui-property-list-actions')]"));
		String attribute = div.getAttribute("class");
		assertThat(attribute, equalTo("phui-property-list-actions"));
		System.out.print("/project/sprint/profile/<id> route to SprintProjectProfileController is executed\n");
		driver.quit();
	}
}
