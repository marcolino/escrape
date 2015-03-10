'use strict';

/**
 * @ngdoc function
 * @name escrapeApp.controller:AboutController
 * @description
 * # AboutController
 * Controller of the escrapeApp
 */
app.controller('AboutController', function ($scope, cfg) {
  $scope.name = cfg.site.name;
  $scope.description = cfg.site.description;
  $scope.version = cfg.site.version;
});