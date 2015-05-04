'use strict';

/**
 * Temporary hack needed because carousel in AngularJs 1.[2-3]*... does not coexists with ngAnimate...
 */
app.directive('disableAnimation', function($animate) {
  return {
    restrict: 'A',
    link: function($scope, $element, $attrs) {
      $attrs.$observe('disableAnimation', function(value) {
        $animate.enabled(!value, $element);
      });
    }
  };
});