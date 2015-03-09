'use strict';

// TODO: do not print title in console.xxx if it's undefined...
// TODO: handle multiple parameters, just as console.xxx...:
/*
for (var i = 0; i < arguments.length; i++) {
  alert(arguments[i]);
}
*/
app.service('notify', function(cfg, toastr) {
  return({
  
    success: function (message, title) {
      title = typeof title !== 'undefined' ? title : 'Success';
      if (cfg.notify.toastr) {
        toastr.success(message, title);
      }
      if (cfg.notify.console) {
        console.log(message, title);
      }
    },
  
    info: function (message, title) {
      title = typeof title !== 'undefined' ? title : 'Info';
      if (cfg.notify.toastr) {
        toastr.info(message, title);
      }
      if (cfg.notify.console) {
        console.info(message, title);
      }

    },
  
    warning: function (message, title) {
      title = typeof title !== 'undefined' ? title : 'Warning';
      if (cfg.notify.toastr) {
        toastr.warning(message, title);
      }
      if (cfg.notify.console) {
        console.warn(message, title);
      }
    },
    warn: function (message, title) { this.warning(message, title); },
  
    error: function (message, title) {
      title = typeof title !== 'undefined' ? title : 'Error';
      if (cfg.notify.toastr) {
        toastr.error(message, title);
      }
      if (cfg.notify.console) {
        console.error(message, title);
      }
    },
  
  });
});