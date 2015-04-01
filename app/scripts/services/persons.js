'use strict';

app.service('Persons', function($http, $q, cfg, notify) {
  var apiUri = cfg.apiUri + '/persons/';

  // private methods
  function handleSuccess(response) {
    //console.info('SUCCESS - response.data:', response.data);
    if (response.data.error) {
      notify.error(response.data.error);
      return $q.reject(response.data.error); // TODO: what does $q.reject does, exactly???
    } else {
      //console.log('RESPONSE.DATA: ', response.data);
      return response.data;
    }
  }

  function handleError(response) {
    if (
      ! angular.isObject(response.data) ||
      ! response.data.message
    ) {
      var message = 'An unknown error occurred in service [Person]';
      notify.error(message);
      return $q.reject(message);
    }
    console.info('ERROR - response.data ???????????? WHEN/IF DOES THIS HAPPEN ????????????', response.data);
    return $q.reject(response.data.message); // TODO: what does $q.reject does, exactly???
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

/*
    setProperty: function (id, property) {
      return $http({
        method: 'PUT',
        url: apiUri + 'setproperty' + '/' + id,
        data: property,
      }).then(handleSuccess, handleError);
    },
*/
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
    },

    getSourcesCountries: function () {
      return $http({
        method: 'GET',
        url: apiUri + 'getSourcesCountries',
      }).then(handleSuccess, handleError);
    },

    getSourcesCities: function (countryCode) {
      return $http({
        method: 'GET',
        url: apiUri + 'getSourcesCities' + '/' + countryCode,
      }).then(handleSuccess, handleError);
    },

/*
    getUniqIds: function (id) {
      return $http({
        method: 'GET',
        url: apiUri + 'getUniqIds' + '/' + id,
      }).then(handleSuccess, handleError);
    },
*/
  });

});