'use strict';

app.controller('PersonsController', function($scope, $rootScope, $routeParams, $modal, $timeout, cfg, Sites, Countries, Persons, Comments) {
  $scope.persons = [];
  $scope.person = [];
  $scope.personId = $routeParams.personId;
  $scope.selectedTab = 'main';
  $scope.countries = Countries;
  $scope.person.streetLocation = '[0, 0]'; // to avoid geolocation prompts...
  $scope.sites = Sites;
console.log('sites:', $scope.sites.sgi);
  $scope.cfg = cfg; // make cfg data available to scope

  // private methods
  function applyPersons(newPersons) {
    $scope.persons = newPersons;
  }

  function loadPersons() {
    Persons.getPersons().then(function(persons) {
      applyPersons(persons);
    });
  }

  if (!$scope.personId) { // load persons list
    Persons.getPersons().then(function(persons) {
      $scope.persons = persons;
      if (cfg.fake) { // DEBUG ONLY
        angular.forEach($scope.persons, function(person) {
          person.name = person.name.shuffle();
        });
      } // /DEBUG ONLY
    });
  } else { // load single person
    Persons.getPerson($scope.personId).then(function(person) {
      if (!cfg.fake) { console.log('person:', person); }
      $scope.person = person;
      $scope.person.nationality = {};
      $scope.person.nationality.code = 'it';
      $scope.person.nationality.country = $scope.countries[$scope.person.nationality.code];
      $scope.person.vote = 5;
      $scope.person.streetAddress = 'Torino, Via Carlo Pisacane, 39';
      $scope.person.streetRegion = 'it';
      //$scope.geocode($scope.person.streetAddress, $scope.person.streetRegion);
      $scope.$watch('person.streetAddress', function() {
         console.log('$watch: Hey, person.streetAddress has changed!');
         $scope.person.streetAddressImageUrl = $scope.streetAddressToImageUrl($scope.person.streetAddress);
         $scope.geocode($scope.person.streetAddress, $scope.person.streetRegion);
      });

      Comments.getCommentsByPhone($scope.person.phone).then(function(comments) {
        if (!cfg.fake) { console.log('comments for ' + $scope.person.phone + ':', comments); }
        $scope.person.comments = comments;
        /*
        if (cfg.fake || !$scope.person.comments) { // DEBUG ONLY
          $scope.person.comments = [
            {
              'author': 'au1',
              'date': '2014-27-05 10:33:11',
              'content': 'Molto efficiente e rapido',
              'url': 'http://a...',
            },
            {
              'author': 'au2',
              'date': '2014-27-05 11:50:00',
              'content': 'Poco efficiente e lento',
              'url': 'http://b...',
            },
            {
              'author': 'au3',
              'date': '2014-27-06 12:44:32',
              'content': 'Abbastanza efficiente ma veloce',
              'url': 'http://c...',
            },
            {
              'author': 'au4',
              'date': '2014-27-05 11:50:00',
              'content': 'Poco efficiente e lento',
              'url': 'http://d...',
            },
            {
              'author': 'au5',
              'date': '2014-12-12 22:15:30',
              'content': 'Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... ',
              'url': 'http://e...',
            },
          ];
        }
        */
      });

      if (cfg.fake || !$scope.person.photos) { // DEBUG ONLY
        $scope.person.photos = [
          {
            image: 'http://uploads7.wikiart.org/images/vincent-van-gogh/self-portrait-1887-9.jpg',
            active: true,
          },
          {
            image: 'http://upload.wikimedia.org/wikipedia/commons/7/78/Brorfelde_landscape_2.jpg',
            active: false,
          },
          {
            image: 'http://lorempixel.com/400/200/sports',
            active: false,
          },
        ];
      }
      if (cfg.fake) { // DEBUG ONLY
        $scope.person.name = $scope.person.name.substr(0, 2);
        $scope.person.description = $scope.person.description.substr(0, 2);
      }
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
    Persons.removePerson(person.id).then(loadPersons);
  };


  $scope.formChangeCountry = function(code) {
    console.log('formChangeCountry(): ', code);
    $scope.person.nationality.code = code;
    $scope.person.nationality.country = $scope.countries[code];
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

  $scope.getCountryClass = function(countryCode) {
    return 'flag flag-32 flag-' + countryCode;
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