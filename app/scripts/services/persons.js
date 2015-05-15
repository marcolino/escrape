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
  return {
    getPersons: function (sieves, userId) {
/*
  TODO: REMOVE THIS CODE: WRONG APPROACH: WHEN FORCING A RELOAD
        FOR A FILTER CHANGED, THE PERSONS LIST DOES NOT CHANGE...
        COULD AVOID RELOADING FROM SERVER IF FILTERS DID NOT CHANGE:
        RE-SETUP A DIGEST SYSTEM...

      if (this.persons) {
        //console.log('PERSONS IS CACHED!');
        return this.persons;
      } else {
        //console.log('PERSONS IS UNDEFINED, LOAD FROM SERVER...');
      }
*/
      this.persons = $http({
        method: 'POST',
        url: apiUri + 'get',
        data: {
          'sieves': sieves,
          'id_user': userId,
        },
      }).then(handleSuccess, handleError);
      return this.persons;
    },

    getPerson: function (id, userId) {
      return $http({
        method: 'GET',
        url: apiUri + 'get' + '/' + id + '/' + userId,
      }).then(handleSuccess, handleError);
    },

    setPerson: function (id, personMaster, personDetail, userId) {
      // by the moment, we just invalidate persons memory cached in this service (it will be reloaded from server next time...)
      // TODO: we should set also persons[id] with new person' data...
      this.persons = null;
      return $http({
        method: 'POST',
        url: apiUri + 'set',
        data: {
          'id': id,
          'person_master': personMaster,
          'person_detail': personDetail,
          'id_user': userId,
        },
      }).then(handleSuccess, handleError);
    },

    addPerson: function (personMaster, personDetail, userId) {
      return $http({
        method: 'POST',
        url: apiUri + 'addPerson',
        data: {
          person_master: personMaster,
          person_detail: personDetail,
          userId: userId,
        },
      }).then(handleSuccess, handleError);
    },

    removePerson: function (id, userId) {
      return $http({
        method: 'DELETE',
        url: apiUri + 'delete',
        data: {
          id: id,
          userId: userId,
        },
      }).then(handleSuccess, handleError);
    },

    getPhotoOccurrences: function (id, url) {
      return $http({
        method: 'POST',
        url: apiUri + 'getPhotoOccurrences',
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

    getActiveCountries: function (userId) {
      return $http({
        method: 'GET',
        url: apiUri + 'getActiveCountries' + '/' + userId,
      }).then(handleSuccess, handleError);
    },

    getPersonsPerComment: function(commentId) {
      return $http({
        method: 'GET',
        url: apiUri + 'getPersonsPerComment' + '/' + commentId,
      }).then(handleSuccess, handleError);
    },

  };
});