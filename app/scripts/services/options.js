// TODO: WE DON'T NEED THIS FILE???
'use strict';

app.service('Options', function(Persons) {
  return {

    getSitesCountries: function () {
      Persons.getSitesCountries().then(
        function(response) {
          console.info('+++ getSitesCountries response:', response);
          //console.info('+++ response.length:', response.length);
          if (response.length === 0) {
            console.log('No countries found...');
          } else {
            console.info('Countries found:', response);
          }
        },
        function(errorMessage) {
          console.warn(errorMessage);
        }
      );
    },
  
    getSitesCities: function (countryCode) {
      Persons.getSitesCities(countryCode).then(
        function(response) {
          console.info('+++ getSitesCities response:', response);
          //console.info('+++ response.length:', response.length);
          if (response.length === 0) {
            console.log('No cities found (for country ' + countryCode + ')...');
          } else {
            console.info('Cities found (for country ' + countryCode + '):', response);
          }
        },
        function(errorMessage) {
          console.warn(errorMessage);
        }
      );
    },

  };

});