'use strict';

app.service('Persons', function($http, $q, cfg) {
  var apiUri = cfg.apiUri + '/persons/';

  // private methods
  function handleSuccess(response) {
    //console.info('Person success: ', response.data);
    if (response.data.error) {
      console.error(response.data.error);
      return($q.reject(response.data.error));
    } else {
console.info('handleSuccess() - response:', response);
      return(response.data);
    }
  }

  function handleError(response) {
    //console.info('Person error: ', response);
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

    photoGetOccurrences: function (url) {
console.log('SERVICE photoGetOccurrences URL is', url);
      return $http({
        method: 'POST',
        url: apiUri + 'photo' + '/' + 'get' + '/' + 'occurrences',
        data: {
          url: url
        },
      }).then(handleSuccess, handleError);
    }

  });

});