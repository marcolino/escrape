'use strict';

app.service('Persons', function($http, $q) {
  /*
  return({
    addPerson: addPerson,
    getPersons: getPersons,
    removePerson: removePerson
  });
  */
  var apiUrl = 'http://192.168.1.2/escrape/api' + '/persons/';

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
        method: 'GET',
        url: apiUrl + 'get',
        data: {
          flag: 'yes'
        },
        //transformRequest: TransformRequestAsFormPost,
      }).then(handleSuccess, handleError);
    },

    getPerson: function (id) {
console.info('service getPerson('+id+')');
      return $http({
        method: 'GET',
        url: apiUrl + 'get' + '/' + id,
        data: {
          flag: 'yes'
        },
        //transformRequest: TransformRequestAsFormPost,
      }).then(handleSuccess, handleError);
    },

    setProperty: function (id, prop) {
      console.info('setProperty() - prop:', prop);
      return $http({
        method: 'PUT',
        url: apiUrl + 'setproperty' + '/' + id,
        data: prop,
/*
        data: {
          'id': id,
          'prop': prop,
        },
*/
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