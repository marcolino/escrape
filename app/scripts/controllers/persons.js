'use strict';

app.controller('PersonsCtrl', function($scope, Persons) {
  $scope.persons = [];

  // ngModel values for form interaction
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