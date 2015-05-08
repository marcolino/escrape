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
        '<b>System exception:</b>' + '<br>' +
        args[0].message + '<br>' + '<br>' +
        '<small>' +
        'in&nbsp;file:&nbsp;<i>' + args[0].file + '</i>,&nbsp;' +
        'at&nbsp;line:&nbsp;<i>' + args[0].line + '</i>,&nbsp;' +
        'with&nbsp;code:&nbsp;<i>' + args[0].code + '</i>.' +
        '</small>'
      ;
      return retval;
    }
    // TODO: we are now handling only first exception (args[0]): handle multiple exceptions, too?

    // see if all arguments are number or string (to avoid JSON.stringify...)
    var sep = ' | '; // number/string separator
    var type = '';
    var maxlen = 500;
    for (var i = 0; i < args.length; i++) { // multiple elements array
      if (retval) {
        retval += sep;
      }
      type = typeof args[i];
      if (type === 'number' || type === 'string') { // not numeric/string type
        retval += args[i];
      } else {
        retval += JSON.stringify(args[i]);
      }
    }

    // truncate too long messages (keep initial and final part)
    if (retval.length > maxlen) {
      retval = retval.substr(0, maxlen / 2) + ' &hellip; ' + retval.substr((maxlen / 2) + 1, (maxlen / 2));
    }

    return retval;
  }

  return({ // all the following methods use 'arguments' pseudo-array

    // success methods /////////////////////////////////
    success: function () {
      var args = [].slice.call(arguments);
      if (cfg.notify.toastr) {
        toastr.success(stringify(args), null, { timeOut: cfg.notify.toastrShortTimeOut });
      }
      if (cfg.notify.console) {
        console.log(args.length > 1 ? args : args[0]);
      }
    },
    successWithTitle: function () {
      var args = [].slice.call(arguments);
      var title = args.shift();
      if (cfg.notify.toastr) {
        toastr.success(stringify(args), title, { timeOut: cfg.notify.toastrShortTimeOut });
      }
      if (cfg.notify.console) {
        console.log('[' + title + ']', args.length > 1 ? args : args[0]);
      }
    },

    // info methods /////////////////////////////////
    info: function () {
      var args = [].slice.call(arguments);
      if (cfg.notify.toastr) {
        toastr.info(stringify(args), null, { timeOut: cfg.notify.toastrShortTimeOut });
      }
      if (cfg.notify.console) {
        console.info(args.length > 1 ? args : args[0]);
      }
    },
    infoWithTitle: function () {
      var args = [].slice.call(arguments);
      var title = args.shift();
      if (cfg.notify.toastr) {
        toastr.info(stringify(args), title, { timeOut: cfg.notify.toastrShortTimeOut });
      }
      if (cfg.notify.console) {
        console.info('[' + title + ']', args.length > 1 ? args : args[0]);
      }
    },
  
    // warning methods /////////////////////////////////
    warning: function () {
      var args = [].slice.call(arguments);
      if (cfg.notify.toastr) {
        toastr.warning(stringify(args), null, { timeOut: cfg.notify.toastrShortTimeOut });
      }
      if (cfg.notify.console) {
        console.warn(args.length > 1 ? args : args[0]);
      }
    },
    warningWithTitle: function () {
      var args = [].slice.call(arguments);
      var title = args.shift();
      if (cfg.notify.toastr) {
        toastr.warning(stringify(args), title, { timeOut: cfg.notify.toastrShortTimeOut });
      }
      if (cfg.notify.console) {
        console.warn('[' + title + ']', args.length > 1 ? args : args[0]);
      }
    },

    // error methods /////////////////////////////////
    error: function () {
      var args = [].slice.call(arguments);
      if (cfg.notify.toastr) {
        toastr.error(stringify(args), null, { timeOut: cfg.notify.toastrLongTimeOut });
      }
      if (cfg.notify.console) {
        console.error(args.length > 1 ? args : args[0]);
      }
    },
    errorWithTitle: function () {
      var args = [].slice.call(arguments);
      var title = args.shift();
      if (cfg.notify.toastr) {
        toastr.error(stringify(args), title, { timeOut: cfg.notify.toastrLongTimeOut });
      }
      if (cfg.notify.console) {
        console.error('[' + title + ']', args.length > 1 ? args : args[0]);
      }
    },

  });

});