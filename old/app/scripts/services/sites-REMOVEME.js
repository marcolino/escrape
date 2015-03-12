'use strict';

app.service('Sites', function() {
  var sites = {
  	'sgi': {
      'name': 'sexyguidaitalia',
      'url': 'http://www.sexyguidaitalia.com',
      'charset': 'utf-8',
      'path': 'escort/torino',
    },
    'toe': {
      'name': 'torinoerotica',
      'url': 'http://www.torinoerotica.com',
      'charset': 'CP1252',
      'path': 'annunci_Escort_singole_Piemonte_Torino.html',
    },
  };
  return sites;
});