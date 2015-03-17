'use strict';

app.controller('PersonsController', function($scope, $rootScope, $routeParams, $modal, $timeout, cfg, notify, Authentication, /*Sites, */Countries, Persons, Comments) {
/*
$scope.images = [
 'images/users/user-0.jpg',
 'images/users/user-1.jpg',
 'images/users/user-2.jpg',
 'images/users/user-3.jpg',
 'images/users/user-4.jpg',
];
*/
  $scope.persons = [];
  $scope.person = {};

  // TODO: avoid visualization errors before person is fully loaded...

  //$scope.person.timestampCreation = 0; // assigning 0 is of no use..., and ng-cloack seems of no use, either... shell add 0 default on view... :-()
  /* jshint camelcase: false */
  $scope.person.timestamp_creation = 0;
  $scope.person.timestamp_last_sync = 0;
  /* jshint camelcase: true */

  $scope.sites = {
    'google+': 'googleplus.com',
    'linkedin': 'linkedin.com',
    'facebook': 'facebook.com',
  };
  $scope.personId = $routeParams.personId;
  $scope.tabs = {
    'main': {
      'description': 'Main',
      'hidden': false,
    },
    'photos': {
      'description': 'Photos',
      'hidden': false,
    },
    'photosOccurrences': { // TODO: think of a better name, please...
      'description': 'Photos occurrences',
      'hidden': true,
      'loading': false,
    },
    'comments': {
      'description': 'Comments',
      'hidden': false,
    },
    'unions': {
      'description': 'Unions',
      'hidden': true,
    }
  };
  $scope.tabSelected = 'main';
  $scope.countries = Countries;
  $scope.person.streetLocation = '[0, 0]'; // to avoid geolocation prompts...
  $scope.cfg = cfg; // make cfg data available to scope
  $scope.username = $rootScope.username; // TODO: get username from Authentication service...

  // watch for sieves changes
  $scope.authenticationService = Authentication;
  $scope.$watch('authenticationService.getSievesDigest()', function(newValue, oldValue/*, scope*/) {
console.log('WATCH - calling loadPersons()...');
    loadPersons(); // load persons list
  }, false);

  // private methods
  function applyPersons(newPersons) {
    $scope.persons = newPersons;
  }

  function loadPersons() {
console.log('loadPersons() - $rootScope.sieves:', $rootScope.sieves);
    Persons.getPersons($rootScope.sieves).then(function(persons) {
      applyPersons(persons);
    });
  }

  if (!$scope.personId) { // load persons list
/*
console.log('loadPersons() - main');
    //loadPersons();
    Persons.getPersons($rootScope.sieves).then(function(persons) {
      $scope.persons = persons;
    });
*/
  } else { // load single person
    Persons.getPerson($scope.personId).then(function(person) {
      if (!cfg.fake) { console.log('person:', person); }
      angular.copy(person, $scope.person); // TODO: do we need angular.copy(), here?
      console.log('Persons.getPerson:', $scope.person);
      //$scope.person = person;
      $scope.person.nationality = 'it';
      //$scope.person.nat = $scope.person.nationality; // TODO... ???
      $scope.person.vote = 5;
      $scope.person.streetAddress = 'Torino, Via Carlo Pisacane, 39';
      $scope.person.streetRegion = 'it';
      $scope.$watch('person.streetAddress', function() {
         console.log('$watch: Hey, person.streetAddress has changed!');
         $scope.person.streetAddressImageUrl = $scope.streetAddressToImageUrl($scope.person.streetAddress);
         $scope.geocode($scope.person.streetAddress, $scope.person.streetRegion);
      });

      Comments.getCommentsByPhone($scope.person.phone).then(function(comments) {
        if (!cfg.fake) { console.log('comments for ' + $scope.person.phone + ':', comments); }
        $scope.person.comments = comments;
      });

    });
  }

  // public methods
  $scope.setProperty = function(id, prop) {
    console.info('setProperty():', id, prop);
    Persons.setProperty(id, prop).then(
      function(data) {
        console.info('setProperty() success:', data);
      },
      function(error) {
        console.warn(error);
      }
    );
    //// reset the form once values have been consumed
    //$scope.form.name = '';
  };

  $scope.addPerson = function() {
    Persons.addPerson($scope.form.name).then(
      loadPersons,
      function(errorMessage) {
        console.warn(errorMessage);
      }
    );
    //// reset the form once values have been consumed
    //$scope.form.name = '';
  };

  $scope.removePerson = function(person) {
    Persons.removePerson(person.id).then(
      loadPersons,
      function(errorMessage) {
        console.warn(errorMessage);
      }
    );
  };
      
  $scope.tabSelect = function (tabName, data) {
    //console.log('Selecting tab ' + tabName);
    $scope.tabSelected = tabName;
    if (tabName === 'photos') {
      var number = data;
      $scope.person.photos[0].active = false;
      $scope.person.photos[number].active = true;
    }
  };

  $scope.photoGetOccurrences = function(id, url) {
    $scope.tabSelected = 'photosOccurrences';
    $scope.tabs.photosOccurrences.hidden = false;
    $scope.photosOccurrencesLoading = true;
    $scope.photosOccurrences = null;
    //notify.info('photoGetOccurrences(' + url + ')');
    Persons.photoGetOccurrences(id, url).then(
      function(response) {
console.info('+++ photoGetOccurrences response:', response);
        // TODO: restructure these names... :-(
        $scope.photosOccurrences = response.searchResults;
        $scope.photosOccurrencesBestGuess = response.bestGuess;
        console.info('Persons.photoGetOccurrences - typeof response:', typeof response);
        //if (response === []) {
        if (typeof response !== 'undefined' && response.length === 0) {
          console.log('No occurrences found...');
        } else {
          console.info('Occurrences found:', response);
        }
        $scope.photosOccurrencesLoading = false;
      },
      function(errorMessage) {
        console.warn(errorMessage);
        $scope.photosOccurrencesLoading = false;
      }
    );
  };

  $scope.photosOccurrencesThruthful = function() {
    console.log('It is thrutful!');
    $scope.tabSelected = 'photos';
    $scope.photosOccurrences = [];
    $scope.tabs.photosOccurrences.hidden = true;
  };

  $scope.photosOccurrencesFake = function() {
    console.log('It is fake!');
    $scope.tabSelected = 'photos';
    $scope.photosOccurrences = [];
    $scope.tabs.photosOccurrences.hidden = true;
  };

  $scope.photosOccurrencesUndecided = function() {
    console.log('Don\'t know...');
    $scope.tabSelected = 'photos';
    $scope.photosOccurrences = [];
    $scope.tabs.photosOccurrences.hidden = true;
  };

  $scope.formChangeCountry = function(code) {
    console.log('formChangeCountry(): ', code);
    $scope.person.nationality = code;
  };

  $scope.streetAddressToImageUrl = function(streetAddress) {
    return(
      'https://maps.googleapis.com/maps/api/streetview' + '?' + 
      'location=' + encodeURIComponent(streetAddress) + '&' +
      'size=' + '800x600'
    );
  };

  $scope.vote = function(vote) {
    if (vote > 0) {
      $scope.person.vote = Math.min(cfg.person.vote.max, $scope.person.vote + vote);
    } else {
      $scope.person.vote = Math.max(cfg.person.vote.min, $scope.person.vote + vote);
    }
  };
  
  $scope.geocode = function(address, region) {
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({ 'address': address, 'region': region }, function(results, status) {
      if (status === google.maps.GeocoderStatus.OK) {
        //console.info('Address [' + $scope.person.streetAddress + '] found:', results[0].geometry.location);
        $scope.person.streetGeometryLocation = results[0].geometry.location;
        $scope.person.streetLocation = [ $scope.person.streetGeometryLocation.k, $scope.person.streetGeometryLocation.D ];
        console.log('person.streetLocation is now', $scope.person.streetLocation);
      } else {
        if (status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
          // got OVER_QUERY_LIMIT status code: retry in a moment...
          setTimeout(function() {
            $scope.geocode(address);
          }, 200);
        } else {
          console.error('Unable to find address [' + $scope.person.streetAddress + '], status: ', status); // set center of region is set if no address found
        }
      }
    });
  };

/*
  $scope.getCountryName = function(countryCode) {
    return Countries.getCountryName(countryCode);
  };

  $scope.getCountryClass = function(countryCode) {
    return 'flag' + ' ' + countryCode;
  };
*/

  // google maps initialization
  $rootScope.$on('mapsInitialized', function(event, maps) {
    $scope.map = maps[0];
    /* global $:false */
    $('#streetAddressIndicationsModalPopup').on('show.bs.modal', function() {
      $timeout(function() {
        google.maps.event.trigger($scope.map, 'resize');
        $scope.map.setCenter($scope.person.streetGeometryLocation);
      }, 200); // this timeout is needed due to animation delay
    });
  });

});
