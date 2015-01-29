'use strict';

app.factory('TransformRequestAsFormPost', function() {
  // PRVIATE METHODS
  function serializeData(data) {

    // if this is not an object, defer to native stringification
    if (! angular.isObject(data)) {
      return((data === null) ? '' : undefined); //data.toString());
    }

    var buffer = [];

    // serialize each key in the object
    for (var name in data) {
        if (! data.hasOwnProperty(name)) {
          continue;
        }

        var value = data[name];

        buffer.push(
          encodeURIComponent(name) +
          '=' +
          encodeURIComponent((value === null) ? '' : value)
        );
    }

    // serialize the buffer and clean it up for transportation
    var source = buffer
      .join('&')
      .replace(/%20/g, '+')
    ;
    
    return(source);
  }

  function transformRequest(data, getHeaders) {
    var headers = getHeaders();
    headers[ 'Content-type' ] = 'application/x-www-form-urlencoded; charset=utf-8';
    return(serializeData(data));
  }

  return(transformRequest);


});

// override the 'expected' $sanitize service to simply allow the HTML to be
// output for the current demo
// NOTE: do not use this version in production! This is for development only
app.value(
  '$sanitize',
  function(html) {
    return(html);
  }
);
