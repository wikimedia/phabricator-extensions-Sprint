package org.phabricator.sprint.selenium;

import org.phabricator.sprint.selenium.environment.webserver.AppServer;

public class Pages {
	public String SprintProjectList;
	public String SprintProjectReport;
	public String SprintProjectBurn;
	public String SprintProjectProfile;
	public String SprintProjectTag;
	public String SprintProjectBoard;
	public String SprintProjectBoardMove;
	public String SprintProjectBoardTaskEdit;
	public String SprintProjectBoardTaskCreate;
	public String SprintPhabricatorProjectArchive;
	public String SprintPhabricatorProjectDetails;
	public String SprintPhabricatorProjectFeed;
	public String SprintPhabricatorProjectIcon;
	public String SprintPhabricatorProjectMembers;
	public String SprintPhabricatorProjectPicture;
	public String SprintPhabricatorProjectUpdate;


	 public Pages(AppServer appServer) {
		  SprintProjectList = appServer.whereIs("/project/sprint/");
		  SprintProjectTag = appServer.whereIs("/tag/");
		  SprintPhabricatorProjectArchive = appServer.whereIs("/project/sprint/archive/");
		  SprintProjectBoard = appServer.whereIs("/project/sprint/board/");
		  SprintProjectBoardTaskEdit = appServer.whereIs("/project/sprint/board/task/edit/");
		  SprintProjectBoardTaskCreate = appServer.whereIs("/project/sprint/board/task/create/");
		  SprintProjectBurn = appServer.whereIs("/project/sprint/burn/");
		  SprintPhabricatorProjectDetails = appServer.whereIs("/project/sprint/details/");
		  SprintPhabricatorProjectFeed = appServer.whereIs("/project/sprint/feed/");
		  SprintPhabricatorProjectIcon = appServer.whereIs("/project/sprint/icon/");
		  SprintPhabricatorProjectMembers = appServer.whereIs("/project/sprint/members/");
		  SprintPhabricatorProjectPicture = appServer.whereIs("/project/sprint/picture/");
		  SprintPhabricatorProjectUpdate = appServer.whereIs("/project/sprint/update/");
		  SprintProjectBoardMove = appServer.whereIs("/project/sprint/move/");
		  SprintProjectProfile = appServer.whereIs("/project/sprint/profile/");
		  SprintProjectReport = appServer.whereIs("/project/sprint/report/");
	 }
}
