/**
 * @provides javelin-behavior-c3-chart
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-vector
 */

JX.behavior('c3-chart', function(config) {

    var h = JX.$(config.hardpoint);
    var l = c3.generate({
         bindto: h,
         data: {
           columns: [
               config.totalpoints,
               config.remainingpoints,
               config.idealpoints,
               config.pointstoday
           ],
             type: 'line',
             types: {
                 'Ideal Points': 'spline',
                 'Points Today': 'bar'
             },
         },
         axis: {
            x: {
                type: 'category',
                categories: config.timeseries
            }
         }
     });
});

