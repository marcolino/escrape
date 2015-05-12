'use strict';

/**
 * Prototypes
 */
String.prototype.shuffle = function () {
  var a = this.split(''),
      n = a.length
  ;
  for (var i = n - 1; i > 0; i--) {
    var j = Math.floor(Math.random() * (i + 1)),
        tmp = a[i]
    ;
    a[i] = a[j];
    a[j] = tmp;
  }
  return a.join('');
};

String.prototype.parseUrl = function () {
  var a = document.createElement('a');
  a.href = this;
  return a;
};

/*
// not used but nice...
String.prototype.htmlToPlaintext = function() {
  return this.replace(/<[^>]+>/gm, '');
};
*/

Array.prototype.move = function (fromIndex, toIndex) {
  //var array = this,
  //    howMany = 1;
  var howMany = 1;
  fromIndex = parseInt(fromIndex) || 0;
  fromIndex = fromIndex < 0 ? this.length + fromIndex : fromIndex;
  toIndex = parseInt(toIndex) || 0;
  toIndex = toIndex < 0 ? this.length + toIndex : toIndex;
  toIndex = toIndex <= fromIndex ? toIndex : toIndex <= fromIndex + howMany ? fromIndex : toIndex - howMany;
  this.splice.apply(this, [toIndex, 0].concat(this.splice(fromIndex, howMany)));
  /*
  //array.splice.apply(array, [toIndex, 0].concat(array.splice(fromIndex, howMany)));
  var moved;
  array.splice.apply(array, [toIndex, 0].concat(moved = array.splice(index, howMany)));
  return moved;
  */
};

Array.prototype.hasName = function(value) {
  for (var i = 0; i < this.length; i++) {
    if (this[i].name === value) {
      return i;
    }
  }
  return -1;
};

Array.prototype.removeByName = function(value) {
  for (var i = 0; i < this.length; i++) {
    if (this[i].name === value) {
      this.splice(i, 1);
      return true;
    }
  }
  return false;
};

Array.prototype.remove = function() {
  var what, a = arguments, len = a.length, ax;
  while (len && this.length) {
    what = a[--len];
    while ((ax = this.indexOf(what)) !== -1) {
      this.splice(ax, 1);
    }
  }
  return this;
};