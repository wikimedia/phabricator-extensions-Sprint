/**
 * @provides javelin-behavior-events-table
 */

JX.behavior('events-table', function (config) {
    jQuery( document ).ready(function ($) {
        $('#events-list').DataTable({
            "order": [[ 0, "desc" ]]
        });
    });
});
