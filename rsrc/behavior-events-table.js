/**
 * @provides javelin-behavior-events-table
 */

JX.behavior('events-table', function (config) {
    jQuery( document ).ready(function ($) {
        $('#events-list').DataTable({
            "order": [[ 0, "desc" ]],
            "aoColumnDefs": [
                { "bVisible": false, "aTargets": [ 0 ] },
                { "iDataSort": 0, "aTargets": [ 1 ] }
            ]
        });
    });
});
