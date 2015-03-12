'use strict';

app.service('notify', function(cfg, toastr) {

 function stringify(args) { // stringify array in args
    var sep = ' | '; // number/string separator

    // see if all arguments are number or string (to avoid JSON.stringify...)
    var retval = '';
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
        toastr.success(stringify(args), 'Success');
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
        toastr.info(stringify(args), 'info');
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
        toastr.warning(stringify(args), 'warning');
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
        toastr.error(stringify(args), 'error');
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