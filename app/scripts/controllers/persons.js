'use strict';

app.controller('PersonsCtrl', function($scope, $rootScope, $routeParams, $modal, $timeout, Persons, Countries) {
  $scope.persons = [];
  $scope.person = [];
console.log('routeParams:', $routeParams);
  $scope.personId = $routeParams.personId;
console.log('$scope.person:', $scope.person);
console.log('$scope.personId:', $scope.personId);

  $scope.selectedTab = 'main';
//$scope.sites = Sites;
  $scope.sites = { // TODO: put this in a new 'Sites' service...
    'sgi': {
      'url': 'http://www.sexyguidaitalia.com',
      'path': 'escort/torino',
      'charset': 'utf-8',
    },
    'toe': {
      'url': 'http://www.torinoerotica.com',
      'charset': 'CP1252',
      'path': 'annunci_Escort_singole_Piemonte_Torino.html',
    },
  };
  $scope.myInterval = 13000;
  $scope.slides = [
    {
      image: 'http://uploads7.wikiart.org/images/vincent-van-gogh/self-portrait-1887-9.jpg'
    },
    {
      image: 'http://upload.wikimedia.org/wikipedia/commons/7/78/Brorfelde_landscape_2.jpg'
    },
    {
      image: 'http://lorempixel.com/400/200/food'
    },
    {
      image: 'http://lorempixel.com/400/200/sports'
    },
    {
      image: 'http://lorempixel.com/400/200/people'
    }
  ];  
  $scope.countries = Countries;

  // ngModel values for form interaction
/*
  // TODO: form => person, name => id ...
  $scope.form = {
    name: ''
  };
*/

/*
  // PRIVATE METHODS
  function applyRemoteData(newPersons) {
    $scope.persons = newPersons;
  }

  function loadRemoteData() {
    Persons.getPersons().then(function(persons) {
      applyRemoteData(persons);
    });
  }
*/
  function applyPersons(newPersons) {
    $scope.persons = newPersons;
  }

  function loadPersons() {
    Persons.getPersons().then(function(persons) {
      applyPersons(persons);
    });
  }

  if (!$scope.personId) {
    Persons.getPersons().then(function(persons) {
      $scope.persons = persons;
    });
    //loadRemoteData();
  } else {
    Persons.getPerson($scope.personId).then(function(person) {
      $scope.person = person;
      $scope.person.nationality = {};
      $scope.person.nationality.code = 'it';
      $scope.person.nationality.country = $scope.countries[$scope.person.nationality.code];
      $scope.formStreetAddressImageUrl();

      $scope.person.streetAddress = 'Torino, Via Carlo Pisacane, 41';
      $scope.person.streetAddressRegion = 'it';

      $scope.person.comments = [
        {
          'author': 'au1',
          'date': '2014-27-05 10:33:11',
          'content': 'Molto efficiente e rapido',
          'url': 'http://...',
        },
        {
          'author': 'au2',
          'date': '2014-27-05 11:50:00',
          'content': 'Poco efficiente e lento',
          'url': 'http://...',
        },
        {
          'author': 'au2',
          'date': '2014-27-05 11:50:00',
          'content': 'Poco efficiente e lento',
          'url': 'http://...',
        },
        {
          'author': 'au2',
          'date': '2014-27-05 11:50:00',
          'content': 'Poco efficiente e lento',
          'url': 'http://...',
        },
        {
          'author': 'au2',
          'date': '2014-27-05 11:50:00',
          'content': 'Poco efficiente e lento',
          'url': 'http://...',
        },
        {
          'author': 'au2',
          'date': '2014-27-05 11:50:00',
          'content': 'Poco efficiente e lento',
          'url': 'http://...',
        },
        {
          'author': 'au2',
          'date': '2014-27-05 11:50:00',
          'content': 'Poco efficiente e lento',
          'url': 'http://...',
        },
        {
          'author': 'au3',
          'date': '2014-12-12 22:15:30',
          'content': 'Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... Non saprei proprio cosa dire... ',
          'url': 'http://...',
        },
        {
          'author': 'au2',
          'date': '2014-27-05 11:50:00',
          'content': 'Poco efficiente e lento',
          'url': 'http://...',
        },
        {
          'author': 'au2',
          'date': '2014-27-05 11:50:00',
          'content': 'Poco efficiente e lento',
          'url': 'http://...',
        },
        {
          'author': 'au2',
          'date': '2014-27-05 11:50:00',
          'content': 'Poco efficiente e lento',
          'url': 'http://...',
        },
      ];
      $scope.person.name = $scope.person.name.substr(0, 2); // TODO: REMOVE-ME
      $scope.person.description = $scope.person.description.substr(0, 2); // TODO: REMOVE-ME
    });
  }

  // PUBLIC METHODS
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

    // reset the form once values have been consumed
    $scope.form.name = '';
  };

  $scope.addPerson = function() {
    Persons.addPerson($scope.form.name).then(
      loadPersons,
      function(errorMessage) {
        console.warn(errorMessage);
      }
    );

    // reset the form once values have been consumed
    $scope.form.name = '';
  };

  $scope.removePerson = function(person) {
    Persons.removePerson(person.id).then(loadPersons);
  };


  $scope.formChangeCountry = function(code) {
    console.log('formChangeCountry(): ', code);
    $scope.person.nationality.code = code;
    $scope.person.nationality.country = $scope.countries[code];
  };

  $scope.formStreetAddressImageUrl = function() {
    var url =
      'https://maps.googleapis.com/maps/api/streetview' + '?' + 
      'location=' + encodeURIComponent($scope.person.streetAddress) + '&' +
      'size=' + '800x600'
    ;
    $scope.person.streetAddressImageUrl = url;
  };

  // slide show
  $scope.images = [{src:'img1.png',title:'Pic 1'},{src:'img2.jpg',title:'Pic 2'},{src:'img3.jpg',title:'Pic 3'},{src:'img4.png',title:'Pic 4'},{src:'img5.png',title:'Pic 5'}]; 

  // Google map
  $rootScope.$on('mapsInitialized', function(event, maps) {
    $scope.map = maps[0];

    /* global $:false */
    $('#streetAddressIndicationsModalPopup').on('show.bs.modal', function(/*e*/) {
      $timeout(function() {
        google.maps.event.trigger($scope.map, 'resize');
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({ 'address': $scope.person.streetAddress, 'region': $scope.person.streetAddressRegion }, function(results, status) {
          if (status === google.maps.GeocoderStatus.OK) {
            var location = results[0].geometry.location;
            $scope.person.streetLocation = [ location.k, location.D ];
            $scope.map.setCenter(location);
            $scope.$digest(); // to force marker show
          } else {
            console.error('Unable to find address ' + $scope.address); // TODO: ? (set center of region?)
          }
        });
      }, 200); // this timeout is needed due to animation delay
    });
  });

});