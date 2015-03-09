'use strict';

app.service('Persons', function($http, $q, cfg) {
  var apiUri = cfg.apiUri + '/persons/';

  // private methods
  function handleSuccess(response) {
console.info('response.data:', response.data);
    if (response.data.error) {
      console.error(response.data.error);
      return($q.reject(response.data.error));
    } else {
      return(response.data);
    }
  }

  function handleError(response) {
    if (
      ! angular.isObject(response.data) ||
      ! response.data.message
      ) {
      return($q.reject('An unknown error occurred in service [Person]'));
    }
    return($q.reject(response.data.message));
  }

  // public methods
  return({

    getPersons: function () {
      return $http({
        method: 'GET',
        url: apiUri + 'get',
      }).then(handleSuccess, handleError);
    },

    getPerson: function (id) {
      return $http({
        method: 'GET',
        url: apiUri + 'get' + '/' + id,
      }).then(handleSuccess, handleError);
    },

    setProperty: function (id, property) {
      return $http({
        method: 'PUT',
        url: apiUri + 'setproperty' + '/' + id,
        data: property,
      }).then(handleSuccess, handleError);
    },

    addPerson: function (name) {
      return $http({
        method: 'POST',
        url: apiUri + 'add',
        data: {
          name: name
        },
      }).then(handleSuccess, handleError);
    },

    removePerson: function (id) {
      return $http({
        method: 'DELETE',
        url: apiUri + 'delete',
        data: {
          id: id
        },
      }).then(handleSuccess, handleError);
    },

    photoGetOccurrences: function (id, url) {
      //console.log('SERVICE photoGetOccurrences - id:', id, ', url:', url);
      return $http({
        method: 'POST',
        url: apiUri + 'photo' + '/' + 'get' + '/' + 'occurrences',
        data: {
          id: id,
          url: url,
        },
      }).then(handleSuccess, handleError);
    }

  });

});
