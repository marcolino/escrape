'use strict';
 
app.controller('AuthenticationController',
  function ($scope, $rootScope, $location, $aside, $cookieStore, $timeout, cfg, Authentication, Countries) {
    $scope.cfg = cfg;
    $scope.countries = Countries;
    $scope.dataDefaults = {
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
          max: 70,
        },
        nationality: '',
      },
      options: {
      },
    };
    $scope.dataOriginal = {};

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
            this.setSievesDigest($scope.data);
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

    $scope.setSievesDigest = function (data) {
      var digest;
      if (data) {
        digest =
          data.search.term + '\0' +
          data.filters.active + '\0' +
          data.filters.voteMin + '\0' +
          data.filters.commentsCountMin + '\0' +
          data.filters.age.min + '\0' +
          data.filters.age.max + '\0' +
          data.filters.nationality.countryCode + '\0'
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
      $scope.storeData('search'); // store search term (really we want to store search terms?)
      $scope.close();
    };

    $scope.searchClear = function () {
      console.log('searchClear');
      $scope.resetData('search');
    };

    $scope.setFilterVoteMin = function (n) {
      if (n > 0) {
        $scope.data.filters.voteMin =
          Math.min($scope.cfg.person.vote.max, $scope.data.filters.voteMin + n);
      } else {
        $scope.data.filters.voteMin =
          Math.max($scope.cfg.person.vote.min, $scope.data.filters.voteMin + n);
      }
      $scope.storeData('filters');
    };

    $scope.setFilterCommentsCountMin = function (n) {
      if (n > 0) {
        $scope.data.filters.commentsCountMin += n;
      } else {
        $scope.data.filters.commentsCountMin =
          Math.max(0, $scope.data.filters.commentsCountMin + n);
      }
      $scope.storeData('filters');
    };

    $scope.setFilterAgeRange = function () {
      // filter values are automatically updated via the model
      $scope.storeData('filters');
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
      $scope.data.filters.active = mode;
      $scope.storeData('filters');
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
      $scope.data.filters.nationality =code;
      $scope.storeData('filters');
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
        $scope.storeData(section);
      });
    };

    function setCredentials (response) {
      Authentication.setCredentials(response.user.username, response.user.password, response.user.role);
    }

    $scope.loadData = function () {
      $scope.data = {};
      var key = cfg.site.name;
      if ($scope.signedIn()) { // add authdata to key, if user is signed in
        key += '-' + $rootScope.globals.currentUser.authdata;
        console.log('loading data for user', $rootScope.globals.currentUser.username);
      } else {
        console.log('loading data for guest');
      }
      $scope.data = $cookieStore.get(key);
      if (!$scope.data) {
        $scope.data = angular.copy($scope.dataDefaults);
      }
      console.log('loaded data:', $scope.data);
      $rootScope.data = $scope.data; // TODO: assign reference or copy object, here? (AND, do we need $rootScope.data???)
      angular.copy($scope.data, $scope.dataOriginal); // save loaded data as dataOriginal, to be able to check for modifications
      console.log('$scope.dataOriginal:', $scope.dataOriginal);
    };

    $scope.storeData = function () {
      var key = cfg.site.name;
      if ($scope.signedIn()) {
        key += '-' + $rootScope.globals.currentUser.authdata;
      }
      $cookieStore.put(key, $scope.data);
      console.log('stored data:', $scope.data);
      $rootScope.data = $scope.data; // TODO: assign reference or copy object, here? (AND, do we need $rootScope.data???)
    };

    $scope.resetData = function (section) {
      var key = cfg.site.name;
      if ($scope.signedIn()) {
        key += '-' + $rootScope.globals.currentUser.authdata;
      }
      switch (section) {
        default:
        case null:
          //$scope.data = angular.copy($scope.dataDefaults);
          angular.copy($scope.dataDefaults, $scope.data);
          break;
        case 'search':
          //$scope.data.search = angular.copy($scope.dataDefaults.search);
          angular.copy($scope.dataDefaults.search, $scope.data.search);
          break;
        case 'filters':
          //$scope.data.filters = angular.copy($scope.dataDefaults.filters);
          angular.copy($scope.dataDefaults.filters, $scope.data.filters);
          break;
        case 'options':
          //$scope.data.options = angular.copy($scope.dataDefaults.options);
          angular.copy($scope.dataDefaults.options, $scope.data.options);
          break;
      }
      $cookieStore.put(key, $scope.data);
      console.log('reset data to defaults for section ' + section + ':', $scope.data);
      $rootScope.data = $scope.data; // TODO: assign reference or copy object, here?
    };

  // load data (filters, options, ...)
    $scope.loadData();
  }
);