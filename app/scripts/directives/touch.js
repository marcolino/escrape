'use strict';

app.directive('touchstart', function() {
  return function(scope, element, attr) {
    element.on('touchstart', function(/*event*/) {
      scope.$apply(function () { 
        scope.$eval(attr.touchstart); 
      });
    });
  };
});

app.directive('touchend', function() {
  return function(scope, element, attr) {
    element.on('touchend', function(/*event*/) {
      scope.$apply(function () { 
        scope.$eval(attr.touchend); 
      });
    });
  };
});