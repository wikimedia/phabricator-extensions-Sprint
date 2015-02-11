/**
 * @provides javelin-behavior-tasks-table
 */

JX.behavior('tasks-table', function (config) {
    jQuery( document ).ready(function ($) {
        $('#tasks-list').DataTable({
            "order": [[ 5, "desc" ]]
        });
    });
});
