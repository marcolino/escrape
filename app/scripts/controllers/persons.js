'use strict';

app.controller('PersonsCtrl', function($scope, $routeParams, Persons, Countries) {
  $scope.persons = [];
  $scope.person = [];
console.log('routeParams:', $routeParams);
  $scope.personId = $routeParams.personId;
console.log('$scope.person:', $scope.person);
console.log('$scope.personId:', $scope.personId);

  $scope.selectedTab = 'main';
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
      $scope.person.streetAddress = "Torino, Via Carlo Pisacane, 41";
      $scope.formStreetAddressImageUrl();

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
  }

  $scope.formStreetAddressImageUrl = function() {
    var url =
      "https://maps.googleapis.com/maps/api/streetview" + "?" + 
      "location=" + encodeURIComponent($scope.person.streetAddress) + "&" +
      "size=" + "800x600"
    ;
    $scope.person.streetAddressImageUrl = url;
    console.info('url:', url);
  }

  // slide show
  $scope.images = [{src:'img1.png',title:'Pic 1'},{src:'img2.jpg',title:'Pic 2'},{src:'img3.jpg',title:'Pic 3'},{src:'img4.png',title:'Pic 4'},{src:'img5.png',title:'Pic 5'}]; 

});