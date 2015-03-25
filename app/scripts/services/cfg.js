'use strict';

var apiUri = 'http://0.0.0.0'; // server is local

// DEBUG ONLY ////////////////////////////////////////////////////
function atHome() {
  //if (1) {return false;} // TODO: mobile @ office...
  return (
    (typeof navigator !== 'undefined' && typeof navigator.appVersion !== 'undefined' &&
     navigator.appVersion.indexOf('Linux') !== -1) || // Chrome
    (typeof navigator !== 'undefined' && typeof navigator.oscpu !== 'undefined' &&
     navigator.oscpu.indexOf('Linux') !== -1) // Firefox
  );
}
var fake = !atHome();
apiUri = atHome() ? 'http://0.0.0.0' : 'http://192.168.10.30';
//////////////////////////////////////////////////////////////////

app.constant('cfg', {
  site: {
    name: 'eScrape',
    description: 'the ultimate web contacts manager',
    version: latestTag, // sync'ed with latest git tag with a post-commit hook
  },
  apiUri: apiUri + '/escrape/api',
  fake: fake, // DEBUG ONLY
  notify: {
    toastr: true,
    toastrShortTimeOut: 6000, // milliseconds
    toastrLongTimeOut: 10000, // milliseconds
    console: true,
  },
  person: {
    vote: {
      min: 0,
      max: 9,
    },
    age: {
      min: 18,
      max: 75,
    },
  },
});