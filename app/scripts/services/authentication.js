'use strict';
 
app.factory('Authentication', function (Base64, $http, $cookies, $rootScope, $q, cfg, notify) {
  var service = {};
  var apiUri = cfg.apiUri + '/users/';
  //var sievesDigest = '';

  service.register = function (username, password, callback) {
    $http.post(apiUri + 'register', { username: username, password: password })
    .success(function (response) {
      callback(response);
      if (!response.success && response.message) {
        notify.error(response.message);
        return($q.reject(response.message));
      }
    })
    .error(function (response) {
      if (
        ! angular.isObject(response.data) ||
        ! response.data.message
      ) {
        var message = 'An unknown error occurred in registration service';
        notify.error(message);
        response.message = message; // TODO: test this...
        callback(response); // TODO: test this...
        return($q.reject(message));
      }
      notify.error(response.data.message);
      callback(response); // TODO: test this...
      return($q.reject(response.data.message));
    });
  };

  service.login = function (username, password, callback) {
    $http.post(apiUri + 'login', { username: username, password: password })
    .success(function (response) {
      callback(response);
      if (!response.success && response.message) {
        notify.error(response.message);
        return($q.reject(response.message));
      }
    })
    .error(function (response) {
      if (
        ! angular.isObject(response.data) ||
        ! response.data.message
      ) {
        var message = 'An unknown error occurred in login service';
        notify.error(message);
        response.message = message; // TODO: test this...
        callback(response); // TODO: test this...
        return($q.reject(message));
      }
      notify.error(response.data.message);
      callback(response); // TODO: test this...
      return($q.reject(response.data.message));
    });
  };

  service.logout = function (callback) {
    $rootScope.globals = {};
    callback({ success: true });
  };

  service.setCredentials = function (id, username, password, role) {
    var authdata = Base64.encode(username + ':' + password),
        key = 'globals',
        now = new Date(),
        expirationDate = new Date(now.getFullYear() + 10, now.getMonth(), now.getDate()); // this will set the expiration to 10 years

    $rootScope.expires = { expires: expirationDate };

    $rootScope.globals = {
      currentUser: {
        id: id,
        username: username,
        role: role,
        authdata: authdata
      }
    };
//console.warn('service.setCredentials() - globals:', $rootScope.globals);

    $http.defaults.headers.common['Authorization'] = 'Basic' + ' ' + authdata; // jshint ignore:line
console.warn('service.setCredentials() - $cookies.putObject', key, $rootScope.globals, $rootScope.expires);
    $cookies.putObject(key, $rootScope.globals, $rootScope.expires);
  };

  service.clearCredentials = function () {
    $rootScope.globals = {};
    $cookies.remove('globals');
    $http.defaults.headers.common.Authorization = 'Basic'; // + ' '; # ???
  };

/*
  service.setSievesDigest = function (digest) {
    // a null value sets a random digest (which will force a reload)
    sievesDigest = digest ? digest : Math.random();
console.log('service.setSievesDigest :', sievesDigest);
  };

  service.getSievesDigest = function () {
    return sievesDigest;
  };
*/

  service.signedIn = function () {
    return (
      (typeof $rootScope.globals !== 'undefined') &&
      (typeof $rootScope.globals.currentUser !== 'undefined') &&
      (typeof $rootScope.globals.currentUser.username !== 'undefined')
    );
  };

  service.apiUriExternal = function(callback) {
    var apiUriUty = cfg.apiUri + '/uty/';
    $http.get(apiUriUty + 'external-ip')
      .success(function (response) {
        //console.log('response:', response);
        callback(response);
        if (!response.success && response.message) {
          notify.error(response.message);
          return($q.reject(response.message));
        }
      })
      .error(function (response) {
        if (
          ! angular.isObject(response.data) ||
          ! response.data.message
        ) {
          var message = 'An unknown error occurred in external-ip service';
          notify.error(message);
          response.message = message; // TODO: test this...
          callback(response); // TODO: test this...
          return($q.reject(message));
        }
        notify.error(response.data.message);
        callback(response); // TODO: test this...
        return($q.reject(response.data.message));
      })
    ;
    //return 'http://' + '79.50.120.133';
  };

  return service;
})
 
.factory('Base64', function () {
  /* jshint bitwise: false */
  var keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
 
  return {
    encode: function (input) {
      var output = '';
      var chr1, chr2, chr3 = '';
      var enc1, enc2, enc3, enc4 = '';
      var i = 0;
 
      do {
        chr1 = input.charCodeAt(i++);
        chr2 = input.charCodeAt(i++);
        chr3 = input.charCodeAt(i++);
 
        enc1 = chr1 >> 2;
        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
        enc4 = chr3 & 63;
 
        if (isNaN(chr2)) {
          enc3 = enc4 = 64;
        } else if (isNaN(chr3)) {
          enc4 = 64;
        }
 
        output = output +
          keyStr.charAt(enc1) +
          keyStr.charAt(enc2) +
          keyStr.charAt(enc3) +
          keyStr.charAt(enc4);
        chr1 = chr2 = chr3 = '';
        enc1 = enc2 = enc3 = enc4 = '';
      } while (i < input.length);
 
      return output;
    },
 
    decode: function (input) {
      var output = '';
      var chr1, chr2, chr3 = '';
      var enc1, enc2, enc3, enc4 = '';
      var i = 0;
 
      // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
      var base64test = /[^A-Za-z0-9\+\/\=]/g;
      if (base64test.exec(input)) {
        window.alert(
          'There were invalid base64 characters in the input text.\n' +
          'Valid base64 characters are A-Z, a-z, 0-9, \'+\', \'/\',and \'=\'\n' +
          'Expect errors in decoding.'
        );
      }
      input = input.replace(/[^A-Za-z0-9\+\/\=]/g, '');
 
      do {
        enc1 = keyStr.indexOf(input.charAt(i++));
        enc2 = keyStr.indexOf(input.charAt(i++));
        enc3 = keyStr.indexOf(input.charAt(i++));
        enc4 = keyStr.indexOf(input.charAt(i++));
 
        chr1 = (enc1 << 2) | (enc2 >> 4);
        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
        chr3 = ((enc3 & 3) << 6) | enc4;
 
        output = output + String.fromCharCode(chr1);
 
        if (enc3 !== 64) {
          output = output + String.fromCharCode(chr2);
        }
        if (enc4 !== 64) {
          output = output + String.fromCharCode(chr3);
        }
 
        chr1 = chr2 = chr3 = '';
        enc1 = enc2 = enc3 = enc4 = '';
 
      } while (i < input.length);
 
      return output;
    }
  };
 
  /* jshint bitwise: true */
});