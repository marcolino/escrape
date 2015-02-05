'use strict';

app.controller('PersonsCtrl', function($scope, $rootScope, $routeParams, $modal, $timeout, cfg, Sites, Countries, Persons) {
  $scope.persons = [];
  $scope.person = [];
  $scope.personId = $routeParams.personId;
  $scope.selectedTab = 'main';
  $scope.countries = Countries;
  $scope.person.streetLocation = '[0, 0]'; // to avoid geolocation prompts...

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
    });
  } else { // load single person
    Persons.getPerson($scope.personId).then(function(person) {
      console.log('person:', person);
      $scope.person = person;
      $scope.person.nationality = {};
      $scope.person.nationality.code = 'it';
      $scope.person.nationality.country = $scope.countries[$scope.person.nationality.code];
      $scope.person.vote = 5;
      $scope.person.streetAddress = 'Torino, Via Carlo Pisacane, 39';
      $scope.person.streetAddressRegion = 'it';
      $scope.person.streetAddressImageUrl = $scope.streetAddressToImageUrl($scope.person.streetAddress);
      $scope.geocode($scope.person.streetAddress, $scope.person.streetAddressRegion);
      
      //<!--<img class="slide" ng-src="{{sites[person.site]['url']}}/{{photo}}" />-->

      var values = {name: 'misko', gender: 'male'};
      var log = [];
      angular.forEach(values, function(value, key) {
        this.push(key + ': ' + value);
      }, log);
      //expect(log).toEqual(['name: misko', 'gender: male']);

/*
      $scope.person.slides = [];
      var first = true;
      var log = [];
      foreach($scope.person.photos as photo) {
        $scope.person.slides[] = {
          image: Sites[$scope.person.site]['url'] + '/' + photo,
          active: first,
        };
        first = false;
      }
*/
      if (cfg.fake || !$scope.person.slides) { // DEBUG ONLY
        $scope.person.slides = [
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
      } else {
        console.error('Unable to find address [' + $scope.person.streetAddress + '], status: ', status); // set center of region is set if no address found
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