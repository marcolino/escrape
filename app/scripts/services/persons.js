'use strict';

app.service('Persons', function($http, $q, cfg) {
  var apiUri = cfg.apiUri + '/persons/';

  // private methods
  function handleSuccess(response) {
console.info('response.data:', response.data);
//console.info('response.data.error:', response.data.error);
    if (response.data.error) {
console.info('response.data ERROR!');
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

    getPersons: function (data) {
      return $http({
        method: 'POST',
        url: apiUri + 'get',
        data: data,
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
