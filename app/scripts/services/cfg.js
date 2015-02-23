'use strict';

var fake = true;

app.constant('cfg', {
  apiUri: (fake ? 'http://192.168.10.30' : 'http://192.168.1.2') + '/escrape/api',
  fake: fake,
  person: {
  	vote: {
  	  min: 0,
  	  max: 9,
  	},
  },
});