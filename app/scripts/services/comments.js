'use strict';

app.service('Comments', function($http, $q, cfg, notify) {
  var apiUri = cfg.apiUri + '/comments/';

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

    getComments: function (userId) {
      return $http({
        method: 'GET',
        url: apiUri + 'get' + '/' + userId,
      }).then(handleSuccess, handleError);
    },

    getComment: function (id) {
      return $http({
        method: 'GET',
        url: apiUri + 'get' + '/' + id,
      }).then(handleSuccess, handleError);
    },

    setComment: function (id, commentMaster, commentDetail, userId) {
      return $http({
        method: 'POST',
        url: apiUri + 'set',
        data: {
          'id': id,
          'comment_master': commentMaster,
          'comment_detail': commentDetail,
          'user_id': userId,
        },
      }).then(handleSuccess, handleError);
    },

    getCommentsByPhone: function (phone) {
      return $http({
        method: 'GET',
        url: apiUri + 'getByPhone' + '/' + phone,
      }).then(handleSuccess, handleError);
    },

    addComment: function (commentMaster, commentDetail, userId) {
      return $http({
        method: 'POST',
        url: apiUri + 'add',
        data: {
          'comment_master': commentMaster,
          'comment_detail': commentDetail,
          'user_id': userId,
        },
      }).then(handleSuccess, handleError);
    },

    removeComment: function (id, userId) {
      return $http({
        method: 'DELETE',
        url: apiUri + 'delete',
        data: {
          'id': id,
          'user_id': userId,
        },
      }).then(handleSuccess, handleError);
    }
  });

});