'use strict';

var fake = true;

app.constant('cfg', {
  site: {
  	name: 'escrape',
    description: 'escrape, the web contacts manager',
  },
  apiUri: (fake ? 'http://192.168.10.30' : 'http://192.168.1.3') + '/escrape/api',
  fake: fake,
  person: {
  	vote: {
  	  min: 0,
  	  max: 9,
  	},
  },
});