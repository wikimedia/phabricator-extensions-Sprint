/**
 * @provides javelin-behavior-priority-pie
 */

JX.behavior('priority-pie', function(config) {

    var h = JX.$(config.hardpoint);
    var l = c3.generate({
        bindto: h,
        data: {
            columns: [
                ['Needs Triage', config.Triage],
                ['Unbreak Now!', config.Unbreak],
                ['High', config.High],
                ['Normal', config.Normal],
                ['Low', config.Low],
                ['Wishlist', config.Wishlist]
            ],
            type: 'pie'
        },
        color: {
            pattern: ['#8e44ad', '#6e5cb6', '#c0392b', '#e67e22', '#f1c40f', '#3498db']
        }
    });
});
