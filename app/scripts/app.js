'use strict';

/**
 * @ngdoc overview
 * @name escrapeApp
 * @description
 * # escrapeApp
 *
 * Main module of the application.
 */

/*
function NavBarController($scope) {
  $scope.isCollapsed = true;
}
*/
var app = angular.module('escrapeApp', [
    'ngAnimate',
    'ngAria',
    'ngCookies',
    'ngMessages',
    'ngResource',
    'ngRoute',
    'ngSanitize',
    'ngTouch',
    'ngCookies',
    'ngMap',
    'ngAside',
    'ui.bootstrap',
    'ui-rangeSliderInline',
    'toastr',
    'infinite-scroll',
  ]);

// configure routing system
app.config(function ($routeProvider) {
  $routeProvider
    .when('/register', {
      templateUrl: 'views/register.html',
      controller: 'AuthenticationController',
    })
    .when('/login', {
      templateUrl: 'views/login.html',
      controller: 'AuthenticationController',
    })
    .when('/', {
      templateUrl: 'views/persons.html',
      controller: 'PersonsController'
    })
    .when('/details/:personId', {
      templateUrl: 'views/person.html',
      controller: 'PersonsController'
    })
    .when('/about', {
      templateUrl: 'views/about.html',
      controller: 'AboutController'
    })
    .otherwise({
      redirectTo: '/'
    });
});

// configure messaging system
app.config(function (toastrConfig) {
  angular.extend(toastrConfig, {
    allowHtml: true,
    closeButton: true,
    closeHtml: '<button>&times;</button>',
    containerId: 'toast-container',
    extendedTimeOut: 0, // ms. - 0 means not timeout
    iconClasses: {
      error: 'toast-error',
      info: 'toast-info',
      success: 'toast-success',
      warning: 'toast-warning'
    },
    maxOpened: 3,
    messageClass: 'toast-message',
    newestOnTop: false, // newest on bottom
    onHidden: null,
    onShown: null,
    positionClass: 'toast-bottom-right',
    tapToDismiss: true,
    target: 'body',
    timeOut: 5000, // ms. - 0 means not timeout
    titleClass: 'toast-title',
    toastClass: 'toast'
  });
});

app.run(function ($rootScope, $location, $cookieStore, $http) {
  // keep user logged in after page refresh
  $rootScope.globals = $cookieStore.get('globals') || {};
  if ($rootScope.globals.currentUser) {
    $http.defaults.headers.common['Authorization'] = 'Basic ' + $rootScope.globals.currentUser.authdata; // jshint ignore:line
  }
});