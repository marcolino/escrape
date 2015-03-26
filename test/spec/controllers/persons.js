'use strict';

describe('Controller: PersonsController', function () {

  // load the controller's module
  beforeEach(module('escrapeApp'));

  var PersonsCtrl,
    scope;

  // Initialize the controller and a mock scope
  beforeEach(inject(function ($controller, $rootScope) {
    scope = $rootScope.$new();
    PersonsCtrl = $controller('PersonsController', {
      $scope: scope
    });
  }));

  it('3 should be 3', function () {
    expect(3).toBe(3);
  });
});
