// TODO: WE DON'T NEED THIS FILE???
'use strict';

app.service('Options', function(Persons) {
  return {

    getSourcesCountries: function () {
      Persons.getSourcesCountries().then(
        function(response) {
          console.info('+++ getSourcesCountries response:', response);
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
  
    getSourcesCities: function (countryCode) {
      Persons.getSourcesCities(countryCode).then(
        function(response) {
          console.info('+++ getSourcesCities response:', response);
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