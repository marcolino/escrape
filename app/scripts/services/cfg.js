'use strict';

var fake = false;

app.constant('cfg', {
<<<<<<< HEAD
  site: {
  	name: 'escrape',
    description: 'escrape, the web contacts manager',
  },
=======
>>>>>>> c0b26e634efcc5fec5c040d3b5bb62ba7f0a68f4
  apiUri: (fake ? 'http://192.168.10.30' : 'http://192.168.1.3') + '/escrape/api',
  fake: fake,
  person: {
  	vote: {
  	  min: 0,
  	  max: 9,
  	},
  },
});