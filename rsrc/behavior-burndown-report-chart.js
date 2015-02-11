/**
 * @provides javelin-behavior-burndown-report-chart
 */

JX.behavior('burndown-report-chart', function(config) {

    var h = JX.$(config.hardpoint);
    var l = c3.generate({
        bindto: h,
        data: {
            x: 'Dates',
            columns: [
                config.x,
                config.y
            ],
            type: 'area'
        },

        axis: {
            x: {
                type: 'timeseries',
                tick: {
                    count: 40,
                    format: '%Y-%m-%d'
                }
            }
        }
    });
});
