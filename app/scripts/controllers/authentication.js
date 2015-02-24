'use strict';
 
app.controller('AuthenticationCtrl',
  function ($scope, $rootScope, $location, AuthenticationService) {
    // reset login status
    AuthenticationService.ClearCredentials();
    $scope.register = function () {
      $scope.dataLoading = true;
      AuthenticationService.Register($scope.username, $scope.password, function(response) {
        if (response.success) {
          AuthenticationService.SetCredentials($scope.username, $scope.password); // TODO: set credentials on registration?
          $rootScope.username = $scope.username;
          $location.path('/#');
        } else {
          $scope.error = response.message;
          $scope.dataLoading = false;
        }
      });
    };
    $scope.login = function () {
      $scope.dataLoading = true;
      AuthenticationService.Login($scope.username, $scope.password, function(response) {
        if (response.success) {
          AuthenticationService.SetCredentials($scope.username, $scope.password);
          $rootScope.username = $scope.username;
          $location.path('/#');
        } else {
          $scope.error = response.message;
          $scope.dataLoading = false;
        }
      });
    };
  }
);