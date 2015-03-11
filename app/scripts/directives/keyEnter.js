'use strict';

app.directive('keyEnter', function() {
  var KEY_ENTER = 13;
  return function(scope, element, attrs) {
    element.bind('keydown keypress', function(event) {
      if (event.which === KEY_ENTER) {
        scope.$apply(function(){
          scope.$eval(attrs.keyEnter, { 'event': event });
        });
        event.preventDefault();
      }
    });
  };
});