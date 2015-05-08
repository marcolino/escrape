'use strict';

app.directive('back', function() {
  return {
    restrict: 'A',
    link: function(scope, element, attrs) {
      element.bind('click', function goBack() {
        history.back();
        scope.$apply();
      });
    }
  }
});