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
        status: 'any status', // 'any status' / 'active' / 'inactive'
        voteMin: 0,
        commentsCountMin: 0,
        nationality: {
          countryCode: '',
          countryName: '',
        },
      },
      options: {
      },
    };

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
      $scope.data.filters.status = status;
      $scope.storeData('filters');
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
      $scope.data.filters.nationality.countryCode = code;
      $scope.data.filters.nationality.countryName = code ? $scope.countries[code] : $scope.activeCountries()[''];
      $scope.storeData('filters');
    };

    // TODO: this function is similar in person controller: how to have only one instance?
    $scope.getCountryClass = function(countryCode) {
      return countryCode ? 'flag' + ' ' + countryCode : 'glyphicon glyphicon-globe';
    };

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
        console.log('loading data for user ', $rootScope.globals.currentUser.username);
      } else {
        console.log('loading data for guest');
      }
      $scope.data = $cookieStore.get(key);
      if (!$scope.data) {
        $scope.data = angular.copy($scope.dataDefaults);
      }
      console.log('loaded data:', $scope.data);
      $rootScope.data = $scope.data; // TODO: assign reference or copy object, here?
    };

    $scope.storeData = function () {
      var key = cfg.site.name;
      if ($scope.signedIn()) {
        key += '-' + $rootScope.globals.currentUser.authdata;
      }
      $cookieStore.put(key, $scope.data);
      console.log('stored data:', $scope.data);
      $rootScope.data = $scope.data; // TODO: assign reference or copy object, here?
    };

    $scope.resetData = function (section) {
      var key = cfg.site.name;
      if ($scope.signedIn()) {
        key += '-' + $rootScope.globals.currentUser.authdata;
      }
      switch (section) {
        default:
        case null:
          $scope.data = angular.copy($scope.dataDefaults);
          break;
        case 'search':
          $scope.data.search = angular.copy($scope.dataDefaults.search);
          break;
        case 'filters':
          $scope.data.filters = angular.copy($scope.dataDefaults.filters);
          break;
        case 'options':
          $scope.data.options = angular.copy($scope.dataDefaults.options);
          break;
      }
      $cookieStore.put(key, $scope.data);
      console.log('reset data to defaults for section ' + section + ':', $scope.data);
      $rootScope.data = $scope.data; // TODO: assign reference or copy object, here?
    };

console.log('$scope.dataDefaults:', $scope.dataDefaults);
  // load data (filters, options, ...)
    $scope.loadData();
  }
);