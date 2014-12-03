/**
 * @provides javelin-behavior-c3-chart
<<<<<<< HEAD
 * @requires javelin-behavior
 *           javelin-dom
 *           javelin-vector
=======
>>>>>>> upstream/master
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
                 'Points Closed Today': 'bar'
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

