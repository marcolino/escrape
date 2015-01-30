'use strict';

app.service('Persons', function($http, $q) {
  /*
  return({
    addPerson: addPerson,
    getPersons: getPersons,
    removePerson: removePerson
  });
  */
  var apiUrl = 'http://192.168.10.30/escrape/api' + '/persons/';

  // PRIVATE METHODS
  function handleSuccess(response) {
    console.info('Person success: ', response.data);
    if (response.data.error) {
      console.error(response.data.error);
    } else {
      return(response.data);
    }
  }

  function handleError(response) {
    console.info('Person error: ', response);
    if (
      ! angular.isObject(response.data) ||
      ! response.data.message
      ) {
      return($q.reject('An unknown error occurred in service [Person]'));
    }

    return($q.reject(response.data.message));
  }

  return({
    // PUBLIC METHODS

    getPersons: function () {
      return $http({
        method: 'get',
        url: apiUrl + 'get',
        data: {
          flag: 'yes'
        },
        //transformRequest: TransformRequestAsFormPost,
      }).then(handleSuccess, handleError);
    },

    setProperty: function (name, value) {
      return $http({
        method: 'post',
        url: apiUrl + 'setProperty',
        data: {
          name: name,
          value: value,
        },
        //transformRequest: TransformRequestAsFormPost,
      }).then(handleSuccess, handleError);
    },

    addPerson: function (name) {
      var request = $http({
        method: 'post',
        url: apiUrl + 'add',
        params: {
          flag: 'yes'
        },
        data: {
          name: name
        },
        //transformRequest: TransformRequestAsFormPost,
      });
      return(request.then(handleSuccess, handleError));
    },

    removePerson: function (id) {
      return $http({
        method: 'delete',
        url: apiUrl + 'delete',
        params: {
          flag: 'no'
        },
        data: {
          id: id
        },
        //transformRequest: TransformRequestAsFormPost,
      }).then(handleSuccess, handleError);
    }
  });

});