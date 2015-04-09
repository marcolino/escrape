'use strict';
 
app.controller('AuthenticationController',
  function ($scope, $rootScope, $location, $aside, $timeout, cfg, Authentication, Countries, Sieves) {
    $scope.cfg = cfg;
    $scope.countries = Countries;
/*
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
        isopened: false,
        countryCode: cfg.countryCode,
        cityCode: cfg.cityCode,
        categoryCode: cfg.category,
      },
      //uniqIds: [],
      user: {
        id: null,
      },
      sort: [
        'name', 
      ],
    };
    $scope.sievesOriginal = {};
*/

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
//console.error($scope.sieves);
              Sieves.setDigest($scope.sieves);
          };
        }
      }).result.then(
        function () { // aside modal closed
        },
        function () { // aside modal dismissed (backdrop): force a reload
          Sieves.setDigest(null);
        }
      );
    };

/*
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
          sieves.filters.nationality.countryCode + '\0' +
          sieves.options.countryCode + '\0' +
          sieves.options.cityCode + '\0' +
          sieves.options.categoryCode + '\0' +
          sieves.user.id + '\0' +
          sieves.sort.join('\0')
        ;
      }
      Authentication.setSievesDigest(digest);
    };
*/

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
        console.log('login():', response);
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
      console.log('logout()');
      Authentication.clearCredentials();
      Sieves.load(true); // reload sieves, (forcing the reloading)
    };

    $scope.signedIn = function () {
      return Authentication.signedIn();
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

    $scope.activeCountries = function () {
      /*
      console.info('activeCountries:', {
        '': 'any country',
        'ar': 'Argentina',
        '..': '...',
      });
      */
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
      return Sieves.setFilterActive(mode);
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
      return Sieves.setFilterNationalityCountry(countryCode);
    };

    $scope.setOptionSourcesCountryCode = function (countryCode) {
      return Sieves.setOptionSourcesCountryCode(countryCode);
    };

    $scope.setOptionSourcesCityCode = function (cityCode) {
      return Sieves.setOptionSourcesCityCode(cityCode);
    };

    $scope.setOptionSourcesCategoryCode = function (categoryCode) {
      return Sieves.setOptionSourcesCategoryCode(categoryCode);
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

    // load sieves (search, filters, options, ...)
    //$scope.loadSieves();
    Sieves.load();
  }
);