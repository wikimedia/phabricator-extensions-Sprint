Release Notes
==================
Phabricator Sprint Extension release/2015-04-22
2015-04-22

####Description
- The burndown chart now gets its data from the board.  The board columns define the sprint task status.
These are currently statically typed with the names "Backlog", "Doing", "Review" and "Done".
Additional columns can be added, but they are not reflected in the Charts.

- Rather than using the special character to designate a Sprint project, the new method is to simply check the
"Is Sprint" box in the project create/edit form.

- Moving a task into the done column then adds points to "points closed today" on the chart.
This can be "undone", which then changes the points remaining back to where they were before the task was "closed".
The indication of "points closed today" however can not be changed retroactively.

- If a task is added during the Sprint, the "ceiling" of total points for the entire Sprint is increased.
This then always yields a new ideal points line and an accurate points remaining.

- If a task is moved to Done before the Sprint start and still in the Sprint, points remaining will be off by the
points for that task.  Suggestion is to either extend the Sprint Start date to before the task was closed or remove
the closed task from the Sprint.

- Long sprints are now possible to chart, as x-axis tick culling removes extra labels.

- Blocker and Blocked flags are shown in the task table.  However, if a task is blocked by a task that is not in
the Sprint, the flag will not show.  Suggestion is to always included blockers as part of the Sprint, even if they are
not necessarily planning to be worked on.  As long as a task has at least one open blocker, it is considered Blocked.
Hovering over a Blocker flag shows a tool-tip that indicates the Blocked tasks.

- Restriction of Task Card movement on the Sprint board is possible using the Edit policy for the Sprint project.

- The Sprint Board shows all tasks by default.  This differs from the Work Board which filters resolved tasks by default.

- Board Columns can designate a "Point Limit".  If this is exceeded, the column header turns red.

- the task table, the events table and the Sprint Burndown List table are now equipped with pagination, search
filtering and column ordering.

- Exporting of table data to CSV is enabled.

- the default Maniphest Burn Up report has been improved for Sprint.  It can be accessed from the Sprint
application page side bar.

####Features
- T86913 Implement new IconNavView in Sprint board
- T89278 Implement Javascript table sorting
- T819 Restricting modification of tasks when they enter sprints
- T88727 Remove Sprint Burndown Chart Dependency on Maniphest Task Status
- T85455 show avatar of assigned to field on cards displayed in workboard
- T86947 Improve sprint extension's burndown exception error page with name and link
- T89275 Implement serialization for Sprint Data
- T95079 Add conduit methods for Sprint Creation

####Bug Fixes
- T78585 Accessing sprint project URLs requires being logged in
- T78208 https://phabricator.wikimedia.org/tag/XXX/board/ times out
- T78679 Sprint projects load slowly
- T85060 Sprint Extension Raise Error with the newest Phabricator
- T86778 PhabricatorProjectQuery()->withDatasourceQuery(SprintConstants::MAGIC_WORD) does not work when special
            character is not at beginning of string
- T86773 Disable storage workflow check for schema in SprintQuery (SprintDAO-LiskDAO)
- T87020 Add customfield index to where clause for getStoryPointsForTask query
- T85902 "Sprint List" displays "Burndown List"
- T86775 Blocker tasks show as blocked in Sprint Task List
- T87357 Check task and subtask status before showing Blocked and Blocker labels
- T77621 Limit or reduce x axis chart label for long Sprints
- T89006 EXCEPTION: (AphrontParameterQueryException) Array for %Ls conversion is empty
- T87229 Replace Special Character '§' Designation Requirement for Sprint with Custom Field "Is Sprint"
- T87362 Add Sprint Validator to Board View Controller
- T77602 Closed Tasks added to Sprint and not reopened add points to points remaining
- T78263 Subtasks do not appear in Tasks for this Sprint List
- T87335 Can't run storage upgrade if using sprint extension
- T90661 Sprint extension may be causing weirdness with task views (open vs. all)
- T91213 BurnUp Chart does not load on phabricator.wikimedia.org (unfiltered data stream to the D3 JS too large)
- T89876 Long task names do not wrap in DataTable column for Task table
- T91042 The icons for Sprint Board and Burndown view don't display an active blue on gray state

