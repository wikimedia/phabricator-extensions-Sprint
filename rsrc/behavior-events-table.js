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
            ],
            "dom": 'T<"clear">lfrtip',
            "tableTools": {
                "sSwfPath": "/rsrc/libext/copy_csv_xls.swf",
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
