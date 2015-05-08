'use strict';

app.controller('FooterController', function($scope, $rootScope, cfg) {
  $scope.cfg = cfg;
  $scope.personsCount = '?';

  $rootScope.$on('someEvent', function(event, args) {
    console.log('FOOTER - event, args:', event, args);
    $scope.personsCount = args; // TODO: beautify this...
  });
  //console.log('FOOTER - $rootScope.personsCount:', $rootScope.personsCount);
  //$scope.personsCount = $rootScope.personsCount;
});