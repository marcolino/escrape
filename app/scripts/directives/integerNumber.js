// TODO: REMOVE-ME (if this code is not used...)
'use strict';

app.directive('numberMask', function() {
  return function(scope, element, attrs) {
    var min = parseInt(attrs.min, 10) || 0,
        max = parseInt(attrs.max, 10) || 10, 
        value = element.val();
    element.on('keyup', function(/*e*/) {
      if (!between(element.val(), min, max)) {
        element.val(value);
      } else {
        value = element.val();
      }
    });
      
    function between(n, min, max) {
      return n >= min && n <= max;
    }
  };
});