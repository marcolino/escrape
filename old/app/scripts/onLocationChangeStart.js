// in "app.js", to add callback on "location change" event:

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