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
    'ngTouch'
  ]);

app.config(function ($routeProvider) {
  $routeProvider
    .when('/', {
      templateUrl: 'views/persons.html',
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