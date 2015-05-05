'use strict';

app.directive('numberMask', function() {
  return {
    require: '?ngModel',
    link: function(scope, element, attrs, ngModelCtrl) {
      var min = parseInt(attrs.min),
          max = parseInt(attrs.max)
      ;
      if (!ngModelCtrl) {
        return; 
      }

      ngModelCtrl.$parsers.push(function(val) {
        if (angular.isUndefined(val)) {
          val = '';
        }
        var clean = val.replace(/[^0-9]+/g, '');
        if (min && parseInt(clean) < min) {
          clean = '' + min;
        }
        if (max && parseInt(clean) > max) {
          clean = '' + max;
        }
        if (val !== clean) {
          ngModelCtrl.$setViewValue(clean);
          ngModelCtrl.$render();
        }
        return clean;
      });

      element.bind('keypress', function(event) {
        if (event.keyCode === 32) {
          event.preventDefault();
        }
      });
    }
  };
});