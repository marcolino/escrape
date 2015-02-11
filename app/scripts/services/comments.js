'use strict';

app.service('Comments', function($http, $q, cfg) {
  var apiUri = cfg.apiUri + '/comments/';

  // private methods
  function handleSuccess(response) {
    //console.info('Comment success: ', response.data);
    if (response.data.error) {
      console.error(response.data.error);
      return($q.reject(response.data.error));
    } else {
      return(response.data);
    }
  }

  function handleError(response) {
    //console.info('Comment error: ', response);
    if (
      ! angular.isObject(response.data) ||
      ! response.data.message
      ) {
      return($q.reject('An unknown error occurred in service [Comment]'));
    }
    return($q.reject(response.data.message));
  }

  // public methods
  return({

    getComments: function () {
      return $http({
        method: 'GET',
        url: apiUri + 'get',
      }).then(handleSuccess, handleError);
    },

    getCommentsByPhone: function (phone) {
      return $http({
        method: 'GET',
        url: apiUri + 'getByPhone' + '/' + phone,
      }).then(handleSuccess, handleError);
    },

    getComment: function (id) {
      return $http({
        method: 'GET',
        url: apiUri + 'get' + '/' + id,
      }).then(handleSuccess, handleError);
    },

    addComment: function (name) {
      return $http({
        method: 'POST',
        url: apiUri + 'add',
        data: {
          name: name
        },
      }).then(handleSuccess, handleError);
    },

    removeComment: function (id) {
      return $http({
        method: 'DELETE',
        url: apiUri + 'delete',
        data: {
          id: id
        },
      }).then(handleSuccess, handleError);
    }
  });

});