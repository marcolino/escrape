'use strict';

app.controller('FooterController', function($scope, $rootScope, cfg) {
  $scope.cfg = cfg;
  $scope.personsCount = null;

  $rootScope.$on('personsLoaded', function(event, args) {
    $scope.personsCount = args.personsCount;
  });
});