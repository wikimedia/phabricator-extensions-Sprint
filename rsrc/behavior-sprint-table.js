/**
 * @provides javelin-behavior-sprint-table
 */

JX.behavior('sprint-table', function (config) {
    jQuery( document ).ready(function ($) {
        $('#sprint-list').DataTable();
    });
});
