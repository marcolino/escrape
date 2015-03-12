'use strict';

var apiUri = 'http://0.0.0.0'; // server is local

// DEBUG ONLY ////////////////////////////////////////////////////
function atHome() {
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
    version: 'v0.0.1', // TODO: sync with latest git tag...
  },
  apiUri: apiUri + '/escrape/api',
  fake: fake, // DEBUG ONLY
  notify: {
    toastr: true,
    console: true,
  },
  person: {
    vote: {
      min: 0,
      max: 9,
    },
  },
});