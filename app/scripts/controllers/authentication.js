'use strict';
 
app.controller('AuthenticationController',
  function ($scope, $rootScope, $location, cfg, Authentication, Countries) {
    $scope.cfg = cfg;
    $scope.countries = Countries;
//$scope.countries = { 'ad': 'Andorra', 'it': 'Italy', };
    console.info('AUTH CTRL start, countries:', $scope.countries);
    var countryCode = '';
    $scope.filter = {
      voteMin: Math.ceil((cfg.person.vote.min + cfg.person.vote.max) / 2),
      commentsCountMin: 0,
      nationality: {
        countryCode: countryCode,
        countryName: $scope.countries[countryCode],
      },
    };

    $scope.register = function () {
      $scope.dataLoading = true;
      Authentication.clearCredentials();
      Authentication.register($scope.username, $scope.password, function(response) {
        $scope.dataLoading = false;
        if (response.success) {
          setCredentials(response);
          $location.path('/#');
        } else {
          $scope.error = response.message;
        }
      });
    };

    $scope.login = function () {
      $scope.dataLoading = true;
      Authentication.clearCredentials();
      Authentication.login($scope.username, $scope.password, function(response) {
        $scope.dataLoading = false;
        if (response.success) {
          setCredentials(response);
          $location.path('/#');
        } else {
          $scope.error = response.message;
        }
      });
    };

    $scope.logout = function () {
      Authentication.clearCredentials();
    };

    $scope.signedIn = function () {
      if (
        (typeof $rootScope.globals !== 'undefined') &&
        (typeof $rootScope.globals.currentUser !== 'undefined') &&
        (typeof $rootScope.globals.currentUser.username !== 'undefined')
      ) {
        return true;
      }
    };
  
    $scope.getUserName = function () { 
      if ($scope.signedIn()) {
        return $rootScope.globals.currentUser.username;
      }
    };

    $scope.getUserRole = function () { 
      if ($scope.signedIn()) {
        return $rootScope.globals.currentUser.role;
      }
    };

    // TODO: do we need this?
    $scope.isActive = function (viewLocation) { 
      return viewLocation === $location.path();
    };

    $scope.search = function () {
console.info('TODO: SEARCHING...');
    };

    $scope.setFilterVoteMin = function (n) {
      if (n > 0) {
        $scope.filter.voteMin = Math.min($scope.cfg.person.vote.max, $scope.filter.voteMin + n);
      } else {
        $scope.filter.voteMin = Math.max($scope.cfg.person.vote.min, $scope.filter.voteMin + n);
      }
      //$('#navbar-collapse-1').trigger('click');
    };

    $scope.setFilterCommentsCountMin = function (n) {
      if (n > 0) {
        $scope.filter.commentsCountMin += n;
      } else {
        $scope.filter.commentsCountMin = Math.max(0, $scope.filter.commentsCountMin + n);
      }
      //$('#navbar-collapse-1').trigger('click');
    };

    $scope.setFilterNationalityCountry = function (code) {
console.log('$scope.setFilterNationalityCountry:', code);
      $scope.filter.nationality.countryCode = code;
      $scope.filter.nationality.countryName = $scope.countries[code];
      //$event.stopPropagation();
    };

    // TODO: this function is in person controller: hot to have only one instance?
    $scope.getCountryClass = function(countryCode) {
      return 'flag flag-32 flag-' + countryCode;
    };

    function setCredentials (response) {
      Authentication.setCredentials(response.user.username, response.user.password, response.user.role);
    }
  }
);