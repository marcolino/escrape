'use strict';

var apiUri = 'http://0.0.0.0'; // server is local
var apiPath = '/escrape/api';

// DEBUG ONLY ////////////////////////////////////////////////////
function atHome() {
  //if (1) {return false;} // TODO: mobile @ office...
/*
    (typeof navigator !== 'undefined' && typeof navigator.platform !== 'undefined' &&
     navigator.platform.indexOf('armv7l') !== -1) || // Chrome on Android
*/
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
  MILLISECONDS_PER_DAY: (1000 * 60 * 60 * 24),
  site: {
    name: 'eScrape',
    description: 'the ultimate web contacts manager',
    version: latestTag, // sync'ed with latest git tag with a post-commit hook
    copy: '© 2015, by the eScrape team',
  },
/*
  client: {
    OS: navigator.oscpu,
    platform: navigator.platform,
    browser: navigator.userAgent,
  },
*/
  apiUri: apiUri + apiPath,
  apiPath: apiPath,
  fake: fake, // DEBUG ONLY
  notify: {
    toastr: true,
    toastrShortTimeOut: 6000, // milliseconds
    toastrLongTimeOut: 10000, // milliseconds
    console: true,
  },
  person: {
    NEW_DURATION_DAYS: 7, // a person is new for one week
    vote: {
      min: 0,
      max: 9,
    },
    rating: {
      min: 0,
      max: 9,
    },
    age: {
      min: 18,
      max: 75,
    },
  },
  sources: {
    countryCode: 'it',
    cityCode: 'to',
    category: 'f',
  },
});