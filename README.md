phabricator-sprint
==================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/christopher-johnson/phabricator-extensions-Sprint/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ChristopherHJohnson/phabricator-extensions-Sprint/?branch=master)

Copyright (C) 2014 Michael Peters
Licensed under GNU GPL v3. See LICENSE for full details

Sprints and burndowns in Phabricator

To install, [add this as a library](https://secure.phabricator.com/book/phabricator/article/libraries/) to phabricator.

You can then create projects with a name that includes "Sprint" and edit it to add a start date and end date. Then add some tasks to that project, and edit them to set some story points. After that, go to the project and click "View burndown" in the actions. You can also view a list of projects with burndowns by going to the Burndown application.

You can generate some sample data by running:

```
bin/lipsum generate BurndownTestDataGenerator
```

NOTE: Sample data can be created with transactions in the future. If you edit a task that has transactions in the future, things get very messed up because you create transactions out of order.


![image](https://cloud.githubusercontent.com/assets/139870/3885291/22334e40-21bf-11e4-909c-ef20666bc2bb.png)

Projects that have "Sprint" in their name have a "Sprint Start Date" and "Sprint End Date"
![image](https://cloud.githubusercontent.com/assets/139870/3885306/61a8723a-21bf-11e4-8bba-7487e1885e62.png)

Tasks in Sprints have "Story Points"
![image](https://cloud.githubusercontent.com/assets/139870/3885313/8a3458d6-21bf-11e4-9391-3ecb10fd929c.png)
