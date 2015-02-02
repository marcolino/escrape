'use strict';

app.directive('slider', function($timeout) {
  return {
    restrict: 'AE',
	replace: true,
	scope: {
		images: '='
	},
    link: function (scope /*, elem *//*, attrs*/) {
	
		scope.currentIndex = 0;

		scope.next = function() {
			return scope.currentIndex < scope.images.length - 1 ? scope.currentIndex++ : scope.currentIndex = 0;
		};
		
		scope.prev = function() {
			return scope.currentIndex > 0 ? scope.currentIndex-- : scope.currentIndex = scope.images.length - 1;
		};
		
		scope.$watch('currentIndex', function() {
			scope.images.forEach(function(image) {
				image.visible = false;
			});
			scope.images[scope.currentIndex].visible = true;
		});
		
		/* automatic-slideshow */
		var timer;
		var sliderFunc = function() {
			timer = $timeout(function() {
				scope.next();
				timer = $timeout(sliderFunc, 20 * 1000);
			}, 20 * 1000);
		};
		
		sliderFunc();
		
		scope.$on('$destroy', function() {
			$timeout.cancel(timer);
		});
		/* /automatic-slideshow */
    },
	templateUrl:'../views/slider.html'
  };
});