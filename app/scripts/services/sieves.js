'use strict';

app.service('Sieves', function($rootScope, $cookieStore, cfg, Persons, Authentication) {
  var service = {};
  
  service.sieves = {};
  service.original = {};
  service.defaults = {
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
      countryCode: cfg.sources.countryCode,
      cityCode: cfg.sources.cityCode,
      categoryCode: cfg.sources.category,
    },
    //uniqIds: [],
    user: {
      id: null,
    },
    sort: [
      {
        0: {
          'name': 'name',
          'direction': 'ascending',
        },
      },
    ],
  };
  service.digest = null;

  service.load = function (force) {
    service.sieves = {};
    var key = cfg.site.name;
    if (Authentication.signedIn()) { // add authdata to key, if user is signed in
      key += '-' + $rootScope.globals.currentUser.authdata;
      console.log('loading sieves for user', $rootScope.globals.currentUser.username);
    } else {
      console.log('loading sieves for guest');
    }
    service.sieves = $cookieStore.get(key);

    if (!service.sieves) {
      service.sieves = angular.copy(service.defaults);
      if (Authentication.signedIn()) {
        service.sieves.user.id = $rootScope.globals.currentUser.id;
        //console.info('!!! load() - service.sieves.user.id:', service.sieves.user.id);
      }
    }
    //$rootScope.sieves = service.sieves;

    angular.copy(service.sieves, service.original); // save loaded sieves as sievesOriginal, to be able to check for modifications
    console.log('Sieves.original:', service.original);

    if (force === true) {
      service.setDigest(null); // reset sieves digest (this forces a persons reload)
    }

    Persons.getSourcesCountries().then(function(response) {
      service.sourcesCountries = response;
      service.setOptionSourcesCountryCode();
    });
  };

  service.store = function () {
    var key = cfg.site.name;
    if (Authentication.signedIn()) {
      key += '-' + $rootScope.globals.currentUser.authdata;
    }
    $cookieStore.put(key, service.sieves);
    console.log('stored sieves:', service.sieves);
    //$rootScope.sieves = $scope.sieves; // TODO: IS THIS OKKKKKK ????????? (SEARCH IN FILES "$rootScope.sieves" ...)
  };

  service.reset = function (section) {
    var key = cfg.site.name;
    if (Authentication.signedIn()) {
      key += '-' + $rootScope.globals.currentUser.authdata;
    }
    switch (section) {
      default:
      case null:
        angular.copy(service.defaults, service.sieves);
        break;
      case 'search':
        angular.copy(service.defaults.search, service.sieves.search);
        break;
      case 'filters':
        angular.copy(service.defaults.filters, service.sieves.filters);
        break;
      case 'options':
        angular.copy(service.defaults.options, service.sieves.options);
        break;
      case 'user':
        angular.copy(service.defaults.user, service.sieves.user);
        break;
      case 'sort':
        angular.copy(service.defaults.sort, service.sieves.sort);
        break;
    }
    $cookieStore.put(key, service.sieves);
    console.log('reset sieves to defaults for section ' + section + ':', service.sieves);
    //$rootScope.sieves = $scope.sieves; // TODO: IS THIS OKKKKKK ????????? (SEARCH IN FILES "$rootScope.sieves" ...)
  };

  service.getDigest = function () {
    return service.digest;
  };

  service.setDigest = function (sieves) {
    if (sieves) {
      // TODO: can we write just: "service.digest = sieves.join('\0')" ???
      service.digest =
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
    } else { // a null value sets a random digest (which will force a reload)
      service.digest = Math.random();
    }
    //Authentication.setSievesDigest(digest);
  };

  service.setFilterVoteMin = function (n) {
    if (n > 0) {
      service.sieves.filters.voteMin =
        Math.min(cfg.person.vote.max, service.sieves.filters.voteMin + n);
    } else {
      service.sieves.filters.voteMin =
        Math.max(cfg.person.vote.min, service.sieves.filters.voteMin + n);
    }
    service.store('filters');
  };

  service.setFilterCommentsCountMin = function (n) {
    if (n > 0) {
      service.sieves.filters.commentsCountMin += n;
    } else {
      service.sieves.filters.commentsCountMin =
        Math.max(0, service.sieves.filters.commentsCountMin + n);
    }
    service.store('filters');
  };

  service.setFilterActive = function (mode) {
    service.sieves.filters.active = mode;
    service.store('filters');
  };

  service.setFilterNationalityCountry = function (countryCode) {
    service.sieves.filters.nationality = countryCode;
    service.store('filters');
  };

  service.setOptionSourcesCountryCode = function (countryCode) {
    service.sieves.options.countryCode = countryCode ? countryCode : cfg.sources.countryCode;
    Persons.getSourcesCities(service.sieves.options.countryCode).then(function(response) {
      service.sourcesCities = response;
      if (typeof service.sieves.options.cityCode === 'undefined') {
        if (typeof service.sourcesCountries !== 'undefined' && typeof service.sieves.options.countryCode !== 'undefined') {
          service.sieves.options.cityCode = service.sourcesCountries[service.sieves.options.countryCode].cityCodeDefault;
        }
      }
      //service.sieves.options.cityCode = service.sourcesCountries[countryCode].cityCodeDefault;
    });
    service.store('options');
  };

  service.setOptionSourcesCityCode = function (cityCode) {
    service.sieves.options.cityCode = cityCode;
    service.store('options');
  };

  service.setOptionSourcesCategoryCode = function (categoryCode) {
    service.sieves.options.categoryCode = categoryCode;
    service.store('options');
  };

  return service;

});