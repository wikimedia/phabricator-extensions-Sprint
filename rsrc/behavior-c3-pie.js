/**
 * @provides javelin-behavior-c3-pie
 */

JX.behavior('c3-pie', function(config) {

    var h = JX.$(config.hardpoint);
    var l = c3.generate({
        bindto: h,
        data: {
            columns: [
                ['open', config.open],
                ['resolved', config.resolved]
            ],
            type: 'pie'
        },
        color: {
            pattern: ['#1f77b4', '#D62728']
        }
    });
});
