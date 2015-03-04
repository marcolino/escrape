'use strict';

// TODO: DEBUG ONLY...
function atHome() {
  return (navigator.appVersion.indexOf('Linux') !== -1);
}
var fake = !atHome();
var apiUri = atHome() ? 'http://0.0.0.0' : 'http://192.168.10.30';

app.constant('cfg', {
  site: {
    name: 'escrape',
    description: 'escrape, the web contacts manager',
  },
  apiUri: apiUri + '/escrape/api',
  fake: fake,
  person: {
    vote: {
      min: 0,
      max: 9,
    },
  },
});