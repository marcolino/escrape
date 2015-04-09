'use strict';

app.controller('PersonsController', function($scope, $rootScope, $routeParams, $modal, $timeout, $location, $anchorScroll, cfg, notify, Authentication, Countries, Persons, Comments/*, Sieves*/) {
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
/*
  $scope.person.timestamp_creation = 0;
  $scope.person.timestamp_last_sync = 0;
*/
  /* jshint camelcase: true */

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
  $scope.sortCriteria = {};
  $scope.detailsId = null;

  // watch for sieves changes
  $scope.authenticationService = Authentication;
  $scope.$watch('authenticationService.getSievesDigest()', function(/*newValue, oldValue*//*, scope*/) {
console.log('WATCH - calling loadPersons()...');
    loadPersons(); // load persons list
  }, false);

/*
 $scope.$on('$viewContentLoaded', function() {
    console.info('***************** viewContentLoaded');
    $scope.goto('18');
  });
*/

  // private methods
  function applyPersons(persons) {
    console.log('PERSONS: ', persons);
    $scope.persons = persons;
    //$scope.sortCriteria.name = true;
    $scope.personsList = sortObjectToList(persons, $scope.sortCriteria);
    if ($rootScope.detailsId) { // scroll to remembered row id
      console.info('scope.detailsId:', $rootScope.detailsId);
      $scope.scrollTo($rootScope.detailsId);
    }
  }

  function loadPersons() {
    //console.log('loadPersons() - $rootScope.sieves:', $rootScope.sieves);
    Persons.getPersons($rootScope.sieves).then(function(persons) {
      applyPersons(persons);
    });
  }

  function sortObjectToList(object, criteria) { // obj is an object of objects
    /* jshint camelcase: false */
//console.log('sortObjectToList():', object);

    var list = Object.keys(object).sort(function(a, b) { // sort object of objects according to criteria returning keys
      if (criteria.name) {
        //return object[a].name >= object[b].name;
        return object[a].name.localeCompare(object[b].name);
      }
      if (criteria.phone) {
        //return object[a].phone >= object[b].phone;
        return object[a].phone.localeCompare(object[b].phone);
      }
      if (criteria.comments_count) {
        return object[a].comments_count < object[b].comments_count;
      }
      //return object[a].name >= object[b].name;
      return object[a].name.localeCompare(object[b].name);
    }).map(function(key) { return object[key]; }); // map resulting array of keys to array of objects

    // aggregate uniq lists in sorted list
    var len = list.length;
    for (var i = 0; i < len; i++) {
      if ((list[i].uniq_prev === null) && (list[i].uniq_next !== null)) { // a uniq primary
        var next;
        for (var j = searchArrayById(list, list[i].uniq_next); j !== null; j = next) {
          next = searchArrayById(list, list[j].uniq_next);
          var src = j;
          var dst = ++i;
          list.move(src, dst);
        }
      }
    }
    /* jshint camelcase: true */
    return list;
  }

  function searchArrayById(array, id) {
    for (var i = 0; i < array.length; i++) {
      if (array[i].id === id) {
        return i;
      }
    }
    return null;
  }

  if ($scope.personId) { // load single person
    Persons.getPerson($scope.personId).then(function(person) {
      if (!!cfg.fake) { console.log('person(', $scope.personId, ')', person); }
      angular.copy(person, $scope.person); // TODO: do we need angular.copy(), here?
      console.log('Persons.getPerson:', $scope.person);
      //$scope.person = person;
      $scope.person.nationality = 'it';
      //$scope.person.nat = $scope.person.nationality; // TODO... ???
      $scope.person.vote = 5;
      //$scope.person.streetAddress = 'Torino, Via Carlo Pisacane, 39';
      $scope.person.streetAddress = $scope.person.address;
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

  $scope.open = function(id) {
    $rootScope.detailsId = id;
    $location.path('/details/' + id);
  };

  $scope.scrollTo = function(id) {
    $timeout(function() {
      $location.hash(id);
      $anchorScroll(id);
      $location.hash(null);
    });
  };

  $scope.setSortCriteria = function(criterium) {
    $scope.sortCriteria[criterium] = true;
    $scope.personsList = sortObjectToList($scope.persons, $scope.sortCriteria);
  };

  $scope.isUniqPrimary = function(personId) {
    //console.log('@ isUniqPrimary('+personId+')');
    /* jshint camelcase: false */
    return (
      ($scope.persons[personId].uniq_prev === null) &&
      ($scope.persons[personId].uniq_next !== null)
    );
    // TODO: why other camel_case fields do not throw camel_case warning?
    /* jshint camelcase: true */
  };

  $scope.isUniqPrimaryOrSingle = function(personId) {
    //console.log('@ isUniqPrimary('+personId+')');
    /* jshint camelcase: false */
    return ($scope.persons[personId].uniq_prev === null);
    /* jshint camelcase: true */
  };

  $scope.isUniqLast = function(personId) {
    /* jshint camelcase: false */
    return (
      ($scope.persons[personId].uniq_prev !== null) &&
      ($scope.persons[personId].uniq_next === null)
    );
    /* jshint camelcase: true */
  };

  $scope.isUniqPrimaryShown = function(personId) {
    //console.log('@ isUniqPrimaryShown('+personId+')');
    /* jshint camelcase: false */
    return (
      $scope.isUniqPrimary(personId) &&
      $scope.persons[personId].uniq_opened
    );
    /* jshint camelcase: true */
  };

  $scope.uniqShow = function(personId) {
    //console.log('@ uniqPrimaryOpen('+personId+')');
    /* jshint camelcase: false */
    if ($scope.isUniqPrimary(personId)) {
      var opened = $scope.persons[personId].uniq_opened ? false : true;
      $scope.persons[personId].uniq_opened = opened;
      var id = personId;
      do {
        id = $scope.persons[id].uniq_next;
        if (id) {
          $scope.persons[id].uniq_opened = opened;
        }
      } while (id);
    } else {
      console.error('ASSERT FAILURE: Can\'t uniqShow('+personId+') on a secondary uniq!!!'); // TODO...
    }
    /* jshint camelcase: true */
  };

  $scope.isUniqShown = function(personId) {
    //console.log('@ isUniqShown('+personId+')');
    /* jshint camelcase: false */
    return (
      $scope.isUniqPrimaryOrSingle(personId) ||
      ($scope.persons[personId].uniq_opened === true)
    );
    /* jshint camelcase: true */
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
        //console.info('+++ photoGetOccurrences response:', response);
        //console.info('+++ response.length:', response.length);
        if (response.searchResults.length === 0) {
          console.log('No occurrences found...');
        } else {
          console.info('Occurrences found:', response);
        }
        $scope.photosOccurrencesLoading = false;
        $scope.photosOccurrences = response.searchResults;
        $scope.photosOccurrencesBestGuess = response.bestGuess;
        console.info('Persons.photoGetOccurrences - typeof response:', typeof response);
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

/*
  $scope.vote = function(vote) {
    if (vote > 0) {
      $scope.person.vote = Math.min(cfg.person.vote.max, $scope.person.vote + vote);
    } else {
      $scope.person.vote = Math.max(cfg.person.vote.min, $scope.person.vote + vote);
    }
  };
*/

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