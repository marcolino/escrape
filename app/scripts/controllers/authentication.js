'use strict';
 
app.controller('AuthenticationController',
  function ($scope, $rootScope, $location, $aside, $cookieStore, $timeout, cfg, Authentication, Countries) {
    $scope.cfg = cfg;
    $scope.countries = Countries;
    $scope.key = 'filters';

    $scope.openAside = function(position) {
      $aside.open({
        templateUrl: 'views/sidemenu.html',
        placement: position,
        size: 'sm', // 'sm': small / 'lg': large
        backdrop: true, // close if user clicks outside this panel
        controller: function($scope, $modalInstance) {
          $scope.close = function(e) {
            $modalInstance.close();
            if (e) {
              e.stopPropagation();
            }
          };
          /*
          $scope.cancel = function(e) {
            $modalInstance.dismiss();
            e.stopPropagation();
            $scope.storeFilters(); // to save isopen status
          };
          */
        }
      });
    };

    $scope.about = function () {
      $location.path('/about');
      $scope.close();
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
      $scope.storeFilters(); // store search term (really?)
      $scope.close();
    };

    $scope.loadFilters = function () {
      var key = $scope.key;
      if ($scope.signedIn()) {
        key = $rootScope.globals.currentUser.authdata + '-' + $scope.key;
        //console.log('loading filters for user ', $rootScope.globals.currentUser.username);
      }
      $scope.filters = $cookieStore.get(key);
      if (!$scope.filters) {
        $scope.resetFilters();
      }
      //console.log('loading filters:', $scope.filter);
    };

    $scope.storeFilters = function () {
      var key = $scope.key;
      if ($scope.signedIn()) {
        key = $rootScope.globals.currentUser.authdata + '-' + $scope.key;
        //console.log('storing filters for user ', $rootScope.globals.currentUser.username);
      }
      $cookieStore.put(key, $scope.filters);
      //console.log('storing filters:', $scope.filters);
    };

    $scope.resetFilters = function () {
      var key = $scope.key;
      if ($scope.signedIn()) {
        key = $rootScope.globals.currentUser.authdata + ':' + $scope.key;
        //console.log('storing filters for user ', $rootScope.globals.currentUser.username);
      }
      //console.log('re-setting filters to defaults');
      $scope.filters = {
        isopened: true,
        searchTerm: '',
        status: 'any status', // 'any status' / 'active' / 'inactive'
        voteMin: 0,
        commentsCountMin: 0,
        nationality: {
          countryCode: '',
          countryName: '',
        },
      };
      $cookieStore.put(key, $scope.filters);
    };

    $scope.setFilterVoteMin = function (n) {
      if (n > 0) {
        $scope.filters.voteMin = Math.min($scope.cfg.person.vote.max, $scope.filters.voteMin + n);
      } else {
        $scope.filters.voteMin = Math.max($scope.cfg.person.vote.min, $scope.filters.voteMin + n);
      }
      $scope.storeFilters();
    };

    $scope.setFilterCommentsCountMin = function (n) {
      if (n > 0) {
        $scope.filters.commentsCountMin += n;
      } else {
        $scope.filters.commentsCountMin = Math.max(0, $scope.filters.commentsCountMin + n);
      }
      $scope.storeFilters();
    };

    $scope.statuses = function () {
      return [
        'any status',
        'active',
        'not active',
      ];
    };

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
      $scope.filters.status = status;
      $scope.storeFilters();
    };

    $scope.getStatusClass = function(mode) {
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
      $scope.filters.nationality.countryCode = code;
      $scope.filters.nationality.countryName = code ? $scope.countries[code] : $scope.activeCountries()[''];
      $scope.storeFilters();
    };

    // TODO: this function is in person controller: how to have only one instance?
    $scope.getCountryClass = function(code) {
      return code ? 'flag flag-32 flag-' + code : 'glyphicon glyphicon-globe';
    };

    $scope.toggleFilterOpened = function (/*isopened*/) {
      $timeout(function() {
        $scope.storeFilters();
      });
    };

    function setCredentials (response) {
      Authentication.setCredentials(response.user.username, response.user.password, response.user.role);
    }

    // load filters
    $scope.loadFilters();
  }
);