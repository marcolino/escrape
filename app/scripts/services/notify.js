'use strict';

app.service('notify', function(cfg, toastr) {

 function stringify(args) { // stringify array in args
    var retval = '';
    // show exceptions in a custom way
    if (
      typeof args[0].message !== 'undefined' &&
      typeof args[0].file !== 'undefined' &&
      typeof args[0].line !== 'undefined' && 
      typeof args[0].code !== 'undefined'
    ) {
      retval = 
        '<u><b>Exception:</u></b><br>' +
        args[0].message + '<br>' +
        '<small>in file: <i>' + args[0].file + '</i></small><br>' +
        '<small>at line: <i>' + args[0].line + '</i></small><br>' +
        '<small>with code: <i>' + args[0].code + '</i></small><br>'
      ;
      return retval;
    }
    // TODO: we are now handling only first exception (args[0]): handle multiple exceptions, too?

    var sep = ' | '; // number/string separator
    // see if all arguments are number or string (to avoid JSON.stringify...)
    var type = '';
    for (var i = 0; i < args.length; i++) {
      type = typeof args[i];
      if (type !== 'number' && type !== 'string') { // not numeric/string type
        return JSON.stringify(args);
      }
      if (retval) {
        retval += sep;
      }
      retval += args[i];
    }
    if (args.length === 0) { // empty array
      return null;
    }
    if (args.length === 1) { // one element array
      return args[0];
    }
    // multiple elements array
    return retval;
  }

  return({ // all the following methods use 'arguments' pseudo-array

    // success methods /////////////////////////////////
    success: function () {
      var args = [].slice.call(arguments);
      if (cfg.notify.toastr) {
        toastr.success(stringify(args)); //, 'Success');
      }
      if (cfg.notify.console) {
        console.log(args);
      }
    },
    successWithTitle: function () {
      var args = [].slice.call(arguments);
      var title = args.shift();
      if (cfg.notify.toastr) {
        toastr.success(stringify(args), title);
      }
      if (cfg.notify.console) {
        console.log('[' + title + ']', args);
      }
    },

    // info methods /////////////////////////////////
    info: function () {
      var args = [].slice.call(arguments);
      if (cfg.notify.toastr) {
        toastr.info(stringify(args)); //, 'Info');
      }
      if (cfg.notify.console) {
        console.info(args);
      }
    },
    infoWithTitle: function () {
      var args = [].slice.call(arguments);
      var title = args.shift();
      if (cfg.notify.toastr) {
        toastr.info(stringify(args), title);
      }
      if (cfg.notify.console) {
        console.info('[' + title + ']', args);
      }
    },
  
    // warning methods /////////////////////////////////
    warning: function () {
      var args = [].slice.call(arguments);
      if (cfg.notify.toastr) {
        toastr.warning(stringify(args)); //, 'Warning');
      }
      if (cfg.notify.console) {
        console.warn(args);
      }
    },
    warningWithTitle: function () {
      var args = [].slice.call(arguments);
      var title = args.shift();
      if (cfg.notify.toastr) {
        toastr.warning(stringify(args), title);
      }
      if (cfg.notify.console) {
        console.warn('[' + title + ']', args);
      }
    },

    // error methods /////////////////////////////////
    error: function () {
      var args = [].slice.call(arguments);
      if (cfg.notify.toastr) {
        toastr.error(stringify(args)); //, 'Error');
      }
      if (cfg.notify.console) {
        console.error(args);
      }
    },
    errorWithTitle: function () {
      var args = [].slice.call(arguments);
      var title = args.shift();
      if (cfg.notify.toastr) {
        toastr.error(stringify(args), title);
      }
      if (cfg.notify.console) {
        console.error('[' + title + ']', args);
      }
    },

  });

});