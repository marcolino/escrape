'use strict';

app.directive('resize', function ($window) {
  return function (scope, element, attr) {
console.log('resize() directive - attr:', attr);
    var w = angular.element($window);
    var changeHeight = function() {
      element.css('height', (w.height() + parseInt(attr.resize)) + 'px');
    };
    w.bind('resize', function () {
      changeHeight(); // when window size gets changed
    });
    changeHeight(); // when page loads
  };
});