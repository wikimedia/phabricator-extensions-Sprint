/**
 * @provides javelin-behavior-tasks-table
 */

JX.behavior('tasks-table', function (config) {
    jQuery( document ).ready(function ($) {
        $('#tasks-list').DataTable({
            "order": [[ 8, "desc" ]],
            "aoColumnDefs": [
                { "aTargets": [ 0 ], "sWidth": "50%" },
                { "bVisible": false, "aTargets": [ 1 ] },
                { "iDataSort": 1, "aTargets": [ 2 ], "sWidth": "8%" },
                { "bVisible": false, "aTargets": [ 3 ] },
                { "iDataSort": 3, "aTargets": [ 4 ], "sWidth": "8%" },
                { "aTargets": [ 5 ], "sWidth": "8%" },
                { "bVisible": false, "aTargets": [ 6 ] },
                { "iDataSort": 6, "aTargets": [ 7 ], "sWidth": "8%" },
                { "aTargets": [ 8 ], "sWidth": "8%" },
                { "aTargets": [ 9 ], "sWidth": "10%" }
            ],
            "dom": 'T<"clear">lfrtip',
            "tableTools": {
                "sSwfPath": "/rsrc/sprint/copy_csv_xls.swf",
                "aButtons": [
                    {
                        "sExtends": "copy",
                        "sButtonText": "Copy to clipboard"
                    },
                    {
                        "sExtends": "csv",
                        "sButtonText": "Save to CSV"
                    }
                ]
            }
        });
    });
});
