'use strict';

app.service('Photos', function($http, $q, cfg, notify) {
  var apiUri = cfg.apiUri + '/photos/';

  // private methods
  function handleSuccess(response) {
    if (response.data.error) {
      notify.error(response.data.error);
      return $q.reject(response.data.error);
    } else {
      return response.data;
    }
  }

  function handleError(response) {
    if (
      ! angular.isObject(response.data) ||
      ! response.data.message
      ) {
      var message = 'An unknown error occurred in service [' + apiUri + ']';
      notify.error(message);
      return $q.reject(message);
    }
    notify.error(response.data.error);
    return $q.reject(response.data.message);
  }

  // public methods
  return({

    getPhotos: function (userId) {
      return $http({
        method: 'GET',
        url: apiUri + 'get' + '/' + userId,
      }).then(handleSuccess, handleError);
    },

    getPhoto: function (id, userId) {
      return $http({
        method: 'GET',
        url: apiUri + 'get' + '/' + id + '/' + userId,
      }).then(handleSuccess, handleError);
    },

    setPhoto: function (id, photoMaster, photoDetail, userId) {
      return $http({
        method: 'POST',
        url: apiUri + 'set',
        data: {
          'id': id,
          'photo_master': photoMaster,
          'photo_detail': photoDetail,
          'id_user': userId,
        },
      }).then(handleSuccess, handleError);
    },

  });

});