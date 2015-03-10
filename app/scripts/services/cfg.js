'use strict';

// TODO: DEBUG ONLY //////////////////////////////////////////////
function atHome() {
  return (
    (navigator.appVersion.indexOf('Linux') !== -1) || // Chrome
    (navigator.oscpu.indexOf('Linux') !== -1) // Firefox
  );
}
var fake = !atHome();
var apiUri = atHome() ? 'http://0.0.0.0' : 'http://192.168.10.30';
//////////////////////////////////////////////////////////////////

app.constant('cfg', {
  site: {
    name: 'escrape',
    description: 'escrape, the web contacts manager',
    version: '0.0.1', // TODO: sync with latest git tag...
  },
  apiUri: apiUri + '/escrape/api',
  fake: fake,
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
