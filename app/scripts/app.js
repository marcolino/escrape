'use strict';

/**
 * @ngdoc overview
 * @name escrapeApp
 * @description
 * # escrapeApp
 *
 * Main module of the application.
 */
var app = angular.module('escrapeApp', [
    'ngAnimate',
  //'ngAria',
    'ngCookies',
    'ngMessages',
    'ngResource',
    'ngRoute',
    'ngSanitize',
    'ngTouch',
    'ngCookies',
    'ui.bootstrap',
    'revolunet.stepper',
  ]);

app.config(function ($routeProvider) {
  $routeProvider
    .when('/login', {
      templateUrl: 'views/login.html',
      controller: 'LoginCtrl',
      //hideMenus: true
    })
    .when('/', {
      templateUrl: 'views/persons.html',
      controller: 'PersonsCtrl'
    })
    .when('/details/:personId', {
      templateUrl: 'views/person.html',
      controller: 'PersonsCtrl'
    })
    .when('/about', {
      templateUrl: 'views/about.html',
      controller: 'AboutCtrl'
    })
    .otherwise({
      redirectTo: '/'
    });
});

app.run(function ($rootScope, $location, $cookieStore, $http) {
  // keep user logged in after page refresh
  $rootScope.globals = $cookieStore.get('globals') || {};
  if ($rootScope.globals.currentUser) {
    $http.defaults.headers.common['Authorization'] = 'Basic ' + $rootScope.globals.currentUser.authdata; // jshint ignore:line
  }
 
  $rootScope.$on('$locationChangeStart', function (event, next, current) {
    console.info('rootScope.on(locationChangeStart):', 'event:',event, 'next:',next, 'current:',current);
    // redirect to login page if not logged in
    if ($location.path() !== '/login' && !$rootScope.globals.currentUser) {
      $location.path('/login');
    }
  });
});