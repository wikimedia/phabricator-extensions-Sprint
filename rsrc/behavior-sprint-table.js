/**
 * @provides javelin-behavior-sprint-table
 */

JX.behavior('sprint-table', function (config) {
    jQuery( document ).ready(function ($) {
        $('#sprint-list').DataTable({
            "order": [[ 0, "asc" ]],
            "aoColumnDefs": [
                { "bVisible": false, "aTargets": [ 2 ] },
                { "iDataSort": 2, "aTargets": [ 3 ] },
                { "bVisible": false, "aTargets": [ 4 ] },
                { "iDataSort": 4, "aTargets": [ 5 ] }
            ]
        });
    });
});
