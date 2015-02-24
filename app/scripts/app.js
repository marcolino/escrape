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
function NavBarCtrl($scope) {
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
    'ngOrderObjectBy',
    'ui.bootstrap',
  ]);

app.config(function ($routeProvider) {
  $routeProvider
    .when('/register', {
      templateUrl: 'views/register.html',
      controller: 'AuthenticationCtrl',
      //hideMenus: true
    })
    .when('/login', {
      templateUrl: 'views/login.html',
      controller: 'AuthenticationCtrl',
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
 
/*
  $rootScope.$on('$locationChangeStart', function (event, next, current) {
    console.info('rootScope.on(locationChangeStart):', 'event:',event, 'next:',next, 'current:',current);
    // redirect to login page if not logged in
    if (
      (!$rootScope.globals.currentUser) &&
      ($location.path() !== '/login' && $location.path() !== '/register')
    ) {
      $location.path('/login');
    }
  });
*/
});

/**
 * Prototypes (TODO: where to put these, in a more "Angular" way?)
 */
String.prototype.shuffle = function () {
  var a = this.split(''),
      n = a.length;

  for (var i = n - 1; i > 0; i--) {
    var j = Math.floor(Math.random() * (i + 1));
    var tmp = a[i];
    a[i] = a[j];
    a[j] = tmp;
  }
  return a.join('');
};