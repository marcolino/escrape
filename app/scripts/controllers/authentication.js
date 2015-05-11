'use strict';
 
app.controller('AuthenticationController',
  function ($scope, $rootScope, $location, $aside, $timeout, cfg, Authentication, Countries, Persons, Sieves) {
    $scope.cfg = cfg;
    $scope.countries = Countries;
    $scope.Sieves = Sieves;

    $scope.init = function () {
      Sieves.load();
      $scope.loadActiveCountries();
    };

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
            // apply sieves
            Sieves.finalize();
          };
        }
      }).result.then(
        function () { // aside modal closed
        },
        function () { // aside modal dismissed (backdrop): force a reload
          Sieves.finalize(null);
        }
      );
    };

    $scope.about = function () {
      $location.path('/about');
      $scope.close();
    };

    $scope.register = function () {
      $scope.dataLoading = true;
      Authentication.clearCredentials();
      Authentication.register($scope.username, $scope.password, function(response) {
        //console.log('register():', response);
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
        //console.log('login():', response);
        $scope.dataLoading = false;
        if (response.success) {
          setCredentials(response);
          Sieves.load(true); // reload sieves, (forcing the reloading)
          $location.path('/#');
        } else {
          $scope.error = response.message;
        }
      });
    };

    $scope.logout = function () {
      //console.log('logout()');
      Authentication.clearCredentials();
      Sieves.load(true); // reload sieves, (forcing the reloading)
    };

    $scope.signedIn = function () {
      return Authentication.signedIn();
    };

    $scope.getUserId = function () {
      if ($scope.signedIn()) {
        return $rootScope.globals.currentUser.id;
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
      Sieves.store('search'); // store search term (really we want to store search terms?)
      $scope.close();
    };

    $scope.searchClear = function () {
      console.log('searchClear');
      Sieves.reset('search');
    };

    $scope.setFilterVoteMin = function (n) {
      return Sieves.setFilterVoteMin(n);
    };

    $scope.setFilterCommentsCountMin = function (n) {
      return Sieves.setFilterCommentsCountMin(n);
    };

    $scope.setFilterAgeRange = function () {
      // filter values are automatically updated via the model
      Sieves.store('filters');
    };

    $scope.actives = function () {
      return [
        'any',
        'yes',
        'no',
      ];
    };

    $scope.getSourcesCountries = function() {
      return Sieves.sourcesCountries;
    };

    $scope.getSourcesCities = function() {
      return Sieves.sourcesCities;
    };

    $scope.getSourcesCity = function(cityCode) {
      var sourcesCities = Sieves.sourcesCities;
      return sourcesCities[cityCode].name;
    };

    $scope.getSourcesCategories = function() {
      return $scope.cfg.fake ? {
        'x': 'X',
        'y': 'Y',
      } : {
        'f': 'Female',
        'm': 'Male',
        't': 'Transgender',
      };
    };

    $scope.getSourcesCategory = function(categoryCode) {
      var sourcesCategories = $scope.getSourcesCategories();
      return sourcesCategories[categoryCode];
    };

    $scope.loadActiveCountries = function () {
      var userId = $scope.getUserId();
      Persons.getActiveCountries(userId).then(function(countries) {
        countries.unshift('');
        // transform array of countryCodes to array of objects { countryCode: countryName }
        $scope.activeCountries = {};
        angular.forEach(countries, function(value) {
          var name = $scope.getCountryName(value);
          if (name) { // skip empty country names
            this[value] = name;
          }
        }, $scope.activeCountries);
      });
    };
    
    $scope.getCountryName = function (countryCode) {
      return (countryCode === '') ? 'any country' : Countries.getCountryName(countryCode);
    };

    $scope.setFilterActive = function (mode) {
      Sieves.setFilterActive(mode);
      //$scope.sieves = Sieves.sieves;
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

    $scope.setFilterNationalityCountry = function (countryCode) {
      Sieves.setFilterNationalityCountry(countryCode);
    };

    $scope.setOptionSourcesCountryCode = function (countryCode) {
      Sieves.setOptionSourcesCountryCode(countryCode);
    };

    $scope.setOptionSourcesCityCode = function (cityCode) {
      Sieves.setOptionSourcesCityCode(cityCode);
    };

    $scope.setOptionSourcesCategoryCode = function (categoryCode) {
      Sieves.setOptionSourcesCategoryCode(categoryCode);
    };

    $scope.toggleSectionOpened = function (section) {
      // store filters on section opened toggle to save opened status
      $timeout(function() {
        Sieves.store(section);
      });
    };

    function setCredentials (data) {
      Authentication.setCredentials(data.user.id, data.user.username, data.user.password, data.user.role);
    }

    $scope.init();
  }
);