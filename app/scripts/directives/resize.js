'use strict';

app.directive('resize', function ($window, $timeout) {
  return function (scope, element, attr) {
    //console.log('resize() directive - attr:', attr);  
    var w = angular.element($window);

    var changeHeight = function() {
      $timeout(function() { // wait for DOM, then...
        var windowHeight = w.innerHeight();
  
        var spaceBefore;
        if (attr.resizeBefore == parseInt(attr.resizeBefore)) { // if an integer is given, we use it as a pixels value
          spaceBefore = parseInt(attr.resizeBefore);
        } else {
          var elementBefore = angular.element(document.getElementById(attr.resizeBefore));
          spaceBefore = elementBefore.offset().top + elementBefore.height();
        }
        //console.log('RESIZE spaceBefore:', spaceBefore);
  
        var spaceAfter;
        if (attr.resizeAfter == parseInt(attr.resizeAfter)) { // if an integer is given, we use it as space-after (in pixels)
          spaceAfter = parseInt(attr.resizeAfter);
        } else {
          var elementAfter = angular.element(document.getElementById(attr.resizeAfter));
          spaceAfter = elementAfter.offset().top;
        }
        //console.log('RESIZE spaceAfter:', spaceAfter);

        var sizeTotal = windowHeight - spaceBefore - spaceAfter;
        element.css('height', sizeTotal + 'px');
      });
    };

    w.bind('resize', function () {
      changeHeight(); // when window size gets changed
    });

    changeHeight(); // when page loads
  };
});