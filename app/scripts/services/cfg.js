'use strict';

var fake = false;

app.constant('cfg', {
  apiUri: (fake ? 'http://192.168.10.30' : 'http://192.168.1.3') + '/escrape/api',
  fake: fake,
  person: {
  	vote: {
  	  min: 0,
  	  max: 9,
  	},
  },
});