/**
 * @provides javelin-behavior-c3-board-data-pie
 */

JX.behavior('c3-board-data-pie', function(config) {

    var h = JX.$(config.hardpoint);
    var l = c3.generate({
        bindto: h,
        data: {
            columns: [
                ['Backlog', config.Backlog],
                ['Doing', config.Doing],
                ['Review', config.Review],
                ['Done', config.Done]
            ],
            type: 'pie'
        },
        color: {
            pattern: ['#BDBDBD', '#FF7F0E', '#2CA02C', '#D62728' ]
        }
    });
});
