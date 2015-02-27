// TODO: REMOVE-ME, use cookieStore service!
'use strict';

app.service('Filter', function() {
  var filter = {
    voteMin: 0,
    commentsCountMin: 0,
    nationality: {
      countryCode: 'it',
      countryName: 'Italy',
    },
  };

  return(filter);
});