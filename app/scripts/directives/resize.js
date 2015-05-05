'use strict';

app.directive('resize', function ($window, $timeout) {
  return function (scope, element, attr) {
    //console.log('resize() directive - attr:', attr);  
    var w = angular.element($window);

    var changeHeight = function() {
      $timeout(function() { // wait for DOM, then...
        var windowHeight = w.innerHeight();
  
        var spaceBefore = 0;
        /* jslint eqeq: true */
        if (attr.resizeBefore == parseInt(attr.resizeBefore)) { // if an integer is given, we use it as a pixels value
          /* jslint eqeq: false */
          spaceBefore = parseInt(attr.resizeBefore);
        } else {
          var elementBefore = angular.element(document.getElementById(attr.resizeBefore));
          if (typeof elementBefore !== 'undefined') {
            spaceBefore = elementBefore.offset().top + elementBefore.height();
          }
        }
        //console.log('RESIZE spaceBefore:', spaceBefore);
  
        var spaceAfter = 0;
        /* jslint eqeq: true */
        if (attr.resizeAfter == parseInt(attr.resizeAfter)) { // if an integer is given, we use it as space-after (in pixels)
          /* jslint eqeq: false */
          spaceAfter = parseInt(attr.resizeAfter);
        } else {
          var elementAfter = angular.element(document.getElementById(attr.resizeAfter));
          console.log(attr.resizeAfter + ':', elementAfter);
          if ((typeof elementAfter !== 'undefined') && (typeof elementAfter.offset !== 'undefined')) {
            spaceAfter = elementAfter.offset().top;
          }
        }
        //console.log('RESIZE spaceAfter:', spaceAfter);

        var sizeTotal = windowHeight - spaceBefore - spaceAfter;
        element.css('height', sizeTotal + 'px');
      });
    };

    w.bind('resize', function () {
      changeHeight(); // when window size gets changed
    });
    scope.$watch('resizeForce', changeHeight); // when controller wants to force a resize

    changeHeight(); // when page loads
  };
});