'use strict';

app.directive('autofocus', function($timeout) {
  return {
    link: function (scope, element, attrs) {
      scope.$watch(attrs.autofocus, function (val) {
        if (angular.isDefined(val) && val) {
          $timeout(function () { element[0].focus(); } );
        }
      }, true);
      element.bind('blur', function () {
        if (angular.isDefined(attrs.autofocusLost)) {
          scope.$apply(attrs.autofocusLost);
        }
      });
    }
  };
});