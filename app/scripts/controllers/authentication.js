'use strict';
 
app.controller('AuthenticationController',
  function ($scope, $rootScope, $location, $aside, $cookieStore, $timeout, cfg, Authentication, Countries) {
    $scope.cfg = cfg;
    $scope.countries = Countries;
    $scope.sievesDefaults = {
      search: {
        term: '',
      },
      filters: {
        isopened: true,
        active: 'any', // 'any' / 'yes' / 'no'
        voteMin: 0,
        commentsCountMin: 0,
        age: {
          min: 18,
          max: 75,
        },
        nationality: '',
      },
      options: {
      },
    };
    $scope.sievesOriginal = {};

    $scope.openSideMenu = function(position) {
      $aside.open({ // side menu instance
        templateUrl: 'views/sidemenu.html',
        placement: position,
        size: 'sm', // 'sm': small / 'lg': large
        backdrop: true, // don't close if user clicks outside this panel
        controller: function($scope, $modalInstance) {
          $scope.close = function(e) {
            $modalInstance.close();
            if (e) {
              e.stopPropagation();
            }
            // set new sieves digest in service
            this.setSievesDigest($scope.sieves);
          };
        }
      }).result.then(
        function () { // aside modal closed
        },
        function () { // aside modal dismissed (backdrop): force a reload
          $scope.setSievesDigest(null);
        }
      );
    };

    $scope.setSievesDigest = function (sieves) {
      var digest;
      if (sieves) {
        digest =
          sieves.search.term + '\0' +
          sieves.filters.active + '\0' +
          sieves.filters.voteMin + '\0' +
          sieves.filters.commentsCountMin + '\0' +
          sieves.filters.age.min + '\0' +
          sieves.filters.age.max + '\0' +
          sieves.filters.nationality.countryCode + '\0'
        ;
      }
      Authentication.setSievesDigest(digest);
    };

    $scope.about = function () {
      $location.path('/about');
      $scope.close();
    };

    $scope.register = function () {
      $scope.dataLoading = true;
      Authentication.clearCredentials();
      Authentication.register($scope.username, $scope.password, function(response) {
        console.log('register():', response);
        $scope.dataLoading = false;
        //if (response.contents.success) {
        if (response.success) {
          //setCredentials(response.contents);
          setCredentials(response);
          $location.path('#/');
        } else {
          //$scope.error = response.contents.message;
          $scope.error = response.message;
        }
      });
    };

    $scope.login = function () {
      $scope.dataLoading = true;
      Authentication.clearCredentials();
      Authentication.login($scope.username, $scope.password, function(response) {
        console.log('login():', response);
        $scope.dataLoading = false;
        //if (response.contents.success) {
        if (response.success) {
          //setCredentials(response.contents);
          setCredentials(response);
          $location.path('/#');
        } else {
          //$scope.error = response.contents.message;
          $scope.error = response.message;
        }
      });
    };

    $scope.logout = function () {
      console.log('logout()');
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

    $scope.isActive = function (viewLocation) { // TODO: do we need/use this?
      return viewLocation === $location.path();
    };

    $scope.search = function () {
      $scope.storeSieves('search'); // store search term (really we want to store search terms?)
      $scope.close();
    };

    $scope.searchClear = function () {
      console.log('searchClear');
      $scope.resetSieves('search');
    };

    $scope.setFilterVoteMin = function (n) {
      if (n > 0) {
        $scope.sieves.filters.voteMin =
          Math.min($scope.cfg.person.vote.max, $scope.sieves.filters.voteMin + n);
      } else {
        $scope.sieves.filters.voteMin =
          Math.max($scope.cfg.person.vote.min, $scope.sieves.filters.voteMin + n);
      }
      $scope.storeSieves('filters');
    };

    $scope.setFilterCommentsCountMin = function (n) {
      if (n > 0) {
        $scope.sieves.filters.commentsCountMin += n;
      } else {
        $scope.sieves.filters.commentsCountMin =
          Math.max(0, $scope.sieves.filters.commentsCountMin + n);
      }
      $scope.storeSieves('filters');
    };

    $scope.setFilterAgeRange = function () {
      // filter values are automatically updated via the model
      $scope.storeSieves('filters');
    };

    $scope.actives = function () {
      return [
        'any',
        'yes',
        'no',
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

    $scope.setFilterActive = function (mode) {
      $scope.sieves.filters.active = mode;
      $scope.storeSieves('filters');
    };

    $scope.getClassActive = function(mode) {
      switch (mode) {
        default:
        case 'any':
          return 'glyphicon glyphicon-th-large';
        case 'yes':
          return 'glyphicon glyphicon-ok';
        case 'no':
          return 'glyphicon glyphicon-remove';
      }
    };

    $scope.setFilterNationalityCountry = function (code) {
      $scope.sieves.filters.nationality =code;
      $scope.storeSieves('filters');
    };

/*
    // TODO: these two functions are similar in person controller: how to have only one instance?
    $scope.getCountry = function(countryCode) {
      return Countries.getCountry(countryCode);
    };

    $scope.getCountryClass = function(countryCode) {
      return countryCode ? 'flag' + ' ' + countryCode : 'glyphicon glyphicon-globe';
    };
*/
    $scope.toggleSectionOpened = function (section/*, isopened*/) {
      // store filters on filters opened toggle to save opened status
      $timeout(function() {
        $scope.storeSieves(section);
      });
    };

    function setCredentials (response) {
      Authentication.setCredentials(response.user.username, response.user.password, response.user.role);
    }

    $scope.loadSieves = function () {
      $scope.sieves = {};
      var key = cfg.site.name;
      if ($scope.signedIn()) { // add authdata to key, if user is signed in
        key += '-' + $rootScope.globals.currentUser.authdata;
        console.log('loading sieves for user', $rootScope.globals.currentUser.username);
      } else {
        console.log('loading sieves for guest');
      }
      $scope.sieves = $cookieStore.get(key);
      if (!$scope.sieves) {
        $scope.sieves = angular.copy($scope.sievesDefaults);
      }
      console.log('loaded sieves:', $scope.sieves);
      $rootScope.sieves = $scope.sieves;
      angular.copy($scope.sieves, $scope.sievesOriginal); // save loaded sieves as sievesOriginal, to be able to check for modifications
      console.log('$scope.sievesOriginal:', $scope.sievesOriginal);
    };

    $scope.storeSieves = function () {
      var key = cfg.site.name;
      if ($scope.signedIn()) {
        key += '-' + $rootScope.globals.currentUser.authdata;
      }
      $cookieStore.put(key, $scope.sieves);
      console.log('stored sieves:', $scope.sieves);
      $rootScope.sieves = $scope.sieves;
    };

    $scope.resetSieves = function (section) {
      var key = cfg.site.name;
      if ($scope.signedIn()) {
        key += '-' + $rootScope.globals.currentUser.authdata;
      }
      switch (section) {
        default:
        case null:
          //$scope.sieves = angular.copy($scope.sievesDefaults);
          angular.copy($scope.sievesDefaults, $scope.sieves);
          break;
        case 'search':
          //$scope.sieves.search = angular.copy($scope.sievesDefaults.search);
          angular.copy($scope.sievesDefaults.search, $scope.sieves.search);
          break;
        case 'filters':
          //$scope.sieves.filters = angular.copy($scope.sievesDefaults.filters);
          angular.copy($scope.sievesDefaults.filters, $scope.sieves.filters);
          break;
        case 'options':
          //$scope.sieves.options = angular.copy($scope.sievesDefaults.options);
          angular.copy($scope.sievesDefaults.options, $scope.sieves.options);
          break;
      }
      $cookieStore.put(key, $scope.sieves);
      console.log('reset sieves to defaults for section ' + section + ':', $scope.sieves);
      $rootScope.sieves = $scope.sieves;
    };

    // load sieves (search, filters, options, ...)
    $scope.loadSieves();
  }
);