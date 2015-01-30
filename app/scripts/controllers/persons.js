'use strict';

app.controller('PersonsCtrl', function($scope, Persons) {
  $scope.persons = [];

  // ngModel values for form interaction
  // TODO: form => person, name => id ...
  $scope.form = {
    name: ''
  };

  // PRIVATE METHODS
  function applyRemoteData(newPersons) {
    $scope.persons = newPersons;
  }

  function loadRemoteData() {
    Persons.getPersons().then(function(persons) {
      applyRemoteData(persons);
    });
  }

  loadRemoteData();

  // PUBLIC METHODS
  $scope.setProperty = function(name, value) {
    Persons.setProperty(name, value).then(
      loadRemoteData,
      function(errorMessage) {
        console.warn(errorMessage);
      }
    );

    // reset the form once values have been consumed
    $scope.form.name = '';
  };

  $scope.addPerson = function() {
    Persons.addPerson($scope.form.name).then(
      loadRemoteData,
      function(errorMessage) {
        console.warn(errorMessage);
      }
    );

    // reset the form once values have been consumed
    $scope.form.name = '';
  };

  $scope.removePerson = function(person) {
    Persons.removePerson(person.id).then(loadRemoteData);
  };

});