'use strict';

app.controller('PersonsCtrl', function($scope, $routeParams, Persons) {
  $scope.persons = [];
  $scope.person = [];
console.log('routeParams:', $routeParams);
  $scope.personId = $routeParams.personId;
console.log('$scope.person:', $scope.person);
console.log('$scope.personId:', $scope.personId);

  $scope.selectedTab = 'main';

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



  // slide show
  $scope.images = [{src:'img1.png',title:'Pic 1'},{src:'img2.jpg',title:'Pic 2'},{src:'img3.jpg',title:'Pic 3'},{src:'img4.png',title:'Pic 4'},{src:'img5.png',title:'Pic 5'}]; 

});