'use strict';
 
app.controller('AuthenticationController',
  function ($scope, $rootScope, $location, $aside, $cookieStore, cfg, Authentication, Countries) {
    $scope.cfg = cfg;
    $scope.countries = Countries;

    $scope.openAside = function(position) {
      $aside.open({
        templateUrl: 'views/sidemenu.html',
        placement: position,
        size: 'sm', // 'sm': small / 'lg': large
        backdrop: true,
        controller: function($scope, $modalInstance) {
          $scope.close = function(e) {
            $modalInstance.close();
            e.stopPropagation();
          };
          /*
          $scope.cancel = function(e) {
            $modalInstance.dismiss();
            e.stopPropagation();
          };
          */
        }
      });
    };

    $scope.about = function () {
      console.info('ABOUT');
      $location.path('/about');
    };

    $scope.register = function () {
      $scope.dataLoading = true;
      Authentication.clearCredentials();
      Authentication.register($scope.username, $scope.password, function(response) {
        $scope.dataLoading = false;
        if (response.success) {
          setCredentials(response);
          $location.path('#/');
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

    $scope.loadFilters = function () {
      $scope.filter = $cookieStore.get('filter');
      if (!$scope.filter) {
        $scope.resetFilters();
      }
      console.log('loading filter:', $scope.filter);
    }

    $scope.resetFilters = function () {
      console.log('re-setting filter to defaults');
      $scope.filter = {
        isopened: true,
        status: 'any status', // 'any status' / 'active' / 'inactive'
        voteMin: 0,
        commentsCountMin: 0,
        nationality: {
          countryCode: '',
          countryName: '',
        },
      };
      $cookieStore.put('filter', $scope.filter);
    }

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

    $scope.statuses = function () {
      return [
        'any status',
        'active',
        'not active',
      ];
    }

    $scope.activeCountries = function () {
      // TODO: get from persons...
      return {
        '': 'any country',
        'ar': 'Argentina',
        'br': 'Brasil',
        'cu': 'Cuba',
        'es': 'Spain',
        'fr': 'France',
        'it': 'Italy',
        'ru': 'Russia',
        'th': 'Thailand',
      };
    };

    $scope.setFilterStatus = function (status) {
      console.log('$scope.filter:', $scope.filter);
      $scope.filter.status = status;
      $cookieStore.put('filter', $scope.filter);
    };

    $scope.getStatusClass = function(mode) {
      console.log('ACTIVE:', mode);
      switch (mode) {
        default:
        case '':
          return 'glyphicon glyphicon-th-large';
        case 'active':
          return 'glyphicon glyphicon-ok';
        case 'not active':
          return 'glyphicon glyphicon-remove';
      }
    };
    $scope.setFilterNationalityCountry = function (code) {
      console.log('$scope.filter:', $scope.filter);
      $scope.filter.nationality.countryCode = code;
      $scope.filter.nationality.countryName = code ? $scope.countries[code] : $scope.activeCountries()[0]; /// ??????????????????
      $cookieStore.put('filter', $scope.filter);
    };

    // TODO: this function is in person controller: how to have only one instance?
    $scope.getCountryClass = function(countryCode) {
      return 'flag flag-32 flag-' + countryCode;
    };

    function setCredentials (response) {
      Authentication.setCredentials(response.user.username, response.user.password, response.user.role);
    }

    // load filters
    $scope.loadFilters();
  }
);