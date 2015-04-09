'use strict';

app.directive('resize', function ($window, $timeout) {
  return function (scope, element, attr) {
    //console.log('resize() directive - attr:', attr);  
    var w = angular.element($window);

    var changeHeight = function() {
      $timeout(function() { // wait for DOM, then...
        var windowHeight = w.innerHeight();
  
        var elementBefore = angular.element(document.getElementById(attr.resizeBefore));
        var spaceBefore = elementBefore.offset().top + elementBefore.height();
  
        //var elementAfter = angular.element(document.getElementById(attr.resizeAfter));
        var spaceAfter = 76; // TODO: a fixed amount for element-after height

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