'use strict';

app.controller('PersonsController', function($scope, $rootScope, $routeParams, $modal, $timeout, $location, $anchorScroll, $filter, $window, cfg, notify, Authentication, Countries, Persons, Comments, Photos, Sieves) {
  $scope.persons = [];
  $scope.person = {};
  $scope.personId = $routeParams.personId;
//console.info('$routeParams:', $routeParams);
  $scope.tabs = {
    'main': {
      'description': 'Main',
      'hidden': false,
    },
    'photos': {
      'description': 'Photos',
      'hidden': false,
    },
/*
    'photosOccurrences': { // TODO: think of a better name...
      'description': 'Photos occurrences',
      'hidden': true,
      'loading': false,
    },
*/
    'comments': {
      'description': 'Comments',
      'hidden': false,
    },
    'list': {
      'description': 'List',
      'hidden': false,
    },
  };
  $scope.tabSelected = $rootScope.tabSelected || 'main';
  $scope.countries = Countries;
  $scope.person.streetLocation = '[0, 0]'; // to avoid geolocation prompts...
  $scope.cfg = cfg; // make cfg data available to scope
  $scope.userId = (typeof $rootScope.globals.currentUser !== 'undefined') ? $rootScope.globals.currentUser.id : null;
  $scope.sortCriteria = {};
  $scope.Sieves = Sieves;
  $scope.Sieves.load();
  $scope.sieves = Sieves.sieves; // TODO: 'Sieves.sieves' => 'Sieves.all' in sieves.js service... And use 'Sieves.all' instead of 'sieves' in controllers and views, deleting this row...
  /*
  $scope.photosOccurrencesWhitelist = [];
  */
  $scope.personsEmpty = false; // not yet loaded
  $scope.tooltipLegenda = '\n\ • click on this label to enable/disable the sort on this column\n\ • click on the arrow to flip the sort direction';

  // watch for sieves changes
  //$scope.$watch('Sieves.getDigest()', function() {
  //  console.log('Sieves.getDigest() CHANGED, RELOADING SIEVES...');
/*
  $scope.$watch('Sieves.digest', function() {
    console.log('Sieves.digest CHANGED, RELOADING SIEVES...');
    loadPersons(); // load persons list
  }, false);
*/
  // AT BOTTOM: loadPersons(); // load persons list

  // watch for sieves changes
  $rootScope.$on('sievesChangedHard', function(/*event, args*/) { // explicitly apply sieves changes
    console.log('SIEVES - sievesChanged HARD');
    loadPersons(); // re-load persons list
  });
  $rootScope.$on('sievesChangedSoft', function(/*event, args*/) { // implicitly apply sieves changes
    console.log('SIEVES - sievesChanged SOFT');
    applyPersons($scope.persons); // re-apply persons list
  });

  /*
  if ($scope.personId) {
    // bind user notification before location change start event if any unsaved change present
    $scope.$on('$locationChangeStart', function(event) {
      if ($scope.changes) {
        var answer = confirm('There are unsaved changes to this person. Do you really want to discard them?')
        if (!answer) {
          event.preventDefault();
        }
      }
    });
  }
  */

  // private methods
  function applyPersons(persons) {
    console.log('APPLY PERSONS');
    $scope.personsLoading = false;
    $scope.persons = persons;
console.info('PERSONS:', $scope.persons);
    $scope.personsList = sortObjectToList(persons, $scope.sieves.sort);
console.info('SIEVES.SORT:', $scope.sieves.sort);
console.info('PERSONS LIST:', $scope.personsList);

/*
var list = $scope.personsList;
var len = list.length;
for (var i = 0; i < len; i++) {
 console.log('id: ' + list[i].id_person + ', active: ' + list[i].active + ', name: ' + list[i].name);
}
*/

    $scope.personsCount = $scope.countPersons(); // for footer controller
    $scope.personsEmpty = $scope.personsCount === 0;
    $rootScope.$emit('personsLoaded', { personsCount: $scope.personsCount });
    //$scope.scrollToOpenedId(); // scroll to remembered row id
  }
  
  function loadPersons() {
    console.log('LOAD PERSONS');
    $scope.personsLoading = true;
    Persons.getPersons(Sieves.sieves, $scope.userId).then(function(persons) {
      applyPersons(persons);
    });
  }

  function loadPerson() { // load single person
    //$rootScope.openedId = $scope.personId;
    //console.info(' $rootScope.openedId after PERSON OPENED: ', $rootScope.openedId);
    Persons.getPerson($scope.personId, $scope.userId).then(function(person) {
      //angular.copy(person, $scope.person); // TODO: do we need angular.copy(), here?
      $scope.person = person;

/*
      // assert photos availability
      for (var i = 0; i < $scope.person.photos.length; i++) {
        /* jshint loopfunc: true * /
        (function(i) {
          Persons.assertPhotoAvailability($scope.person.photos[i].url).then(function(available) {
            $scope.person.photos[i].available = (available === 'true');
          });
        })(i);
        /* jshint loopfunc: false * /
      }
*/
      //console.log('PERSON:', $scope.person);

      // watch for person street address changes
      $scope.$watch('person.street_address', function() {
        $scope.mapError = $scope.panoramaError = null;
        //console.log('$watch: person.street_address changed to', person.street_address);
        $scope.loadMapAndPano($scope.person.street_address);
      });

      //$scope.personCountryFilter = $scope.countries.getCountryName(person.nationality);
      $scope.personsPerComment = {};
      $scope.person.topics = {};
      if (!$scope.person.phone) { // empty phone, do not load comments
        $scope.person.comments = [];
      } else { // phone is present, do load comments
        Comments.getCommentsByPhone($scope.person.phone, $scope.userId).then(function(comments) {
          $scope.person.comments = comments;
          //console.log('comments for ' + $scope.person.phone + ':', comments);
//return;
          /*
           * $scope.person.comments contains all comments linked to the person's phone;
           * if they lack "id_person" field, they could be relative to another person
           * with the same phone.
           */
          Persons.getPersonsByPhone($scope.person.phone, $scope.userId).then(function(persons) {
            //console.log('------------------------------ persons:', persons);
            var commentsCount = $scope.person.comments.length;
            console.log('number of comments for this person:', commentsCount);
            console.log('comments for this person:', $scope.person.comments);
            console.log('number of persons with this same phone:', persons.length);
            console.log('persons:', persons);
            for (var i = 0; i < commentsCount; i++) { // loop through all comments (possibly) linked to this person (effectively, to her phone)

              // build personsPerComment
              var comment = $scope.person.comments[i];
              // TODO: REMOVE $scope.personsPerComment, use comment.feasiblePersons...
              $scope.personsPerComment[comment.id_comment] = {};
              comment.feasiblePersons = {}; // TODO: use instead of personsPerComment[comment.id_comment] ...
              for (var person in persons) { // loop through all persons with the same phone as current person
                // TODO: REMOVE $scope.personsPerComment, use comment.feasiblePersons...
                $scope.personsPerComment[comment.id_comment][person.id_person] = {
                  name: person.name,
                  active: (comment.id_person === person.id_person), // this comment has id_person field equal to one of the persons with the same phone: set that personPerComment as active
                };
                comment.feasiblePersons[person.id_person] = {
                  name: person.name,
                  active: (comment.id_person === person.id_person), // this comment has id_person field equal to one of the persons with the same phone: set that personPerComment as active
                };
              }
              // TODO: REMOVE $scope.personsPerComment, use comment.feasiblePersons...
              if ($scope.personsPerComment[comment.id_comment].length < 1) { // assert at least one person per comment found
                console.error('$scope.personsPerComment['+comment.id_comment+'].length < 1'); // TODO: DEBUG ONLY, REMOVE ME!!!
              }
              if ($scope.personsPerComment[comment.id_comment].length === 1) { // exactly one person for this commen't phone: set it as active
                console.info('$scope.personsPerComment['+comment.id_comment+'].length === 1'); // TODO: DEBUG ONLY, REMOVE ME!!!
                $scope.personsPerComment[comment.id_comment][person.id_person].active = true;
              }
              if ($scope.personsPerComment[comment.id_comment].length > 1) { // more than one person for this commen't phone: do nothing
                console.info('$scope.personsPerComment['+comment.id_comment+'].length > 1'); // TODO: DEBUG ONLY, REMOVE ME!!!
              }

              if (comment.feasiblePersons.length < 1) { // assert at least one person per comment found
                console.error('comment.feasiblePersons.length < 1'); // TODO: DEBUG ONLY, REMOVE ME!!!
              }
              if (comment.feasiblePersons.length === 1) { // exactly one person for this commen't phone: set it as active
                console.info('comment.feasiblePersons.length === 1'); // TODO: DEBUG ONLY, REMOVE ME!!!
                $scope.personsPerComment[comment.id_comment][person.id_person].active = true;
              }

              // build topics
              var topicKey =
                comment.topic + '-' +
                comment.timestamp_creation + '-' +
                comment.author_nick
              ; // build a univoke key for this comment
              if (!(topicKey in $scope.person.topics)) { // new topic, create it
                $scope.person.topics[topicKey] = {};
                $scope.person.topics[topicKey].title = comment.topic;
                $scope.person.topics[topicKey].count = 0;
                $scope.person.topics[topicKey].timestamp_creation = comment.timestamp; // TODO: do we need this???
                $scope.person.topics[topicKey].phone = $scope.person.phone; // TODO: do we need this???
                $scope.person.topics[topicKey].personsPerTopic = $scope.personsPerComment[comment.id_comment];
                $scope.person.topics[topicKey].rating = 0;
                $scope.person.topics[topicKey].items = [];
              } else { // old topic, noop
              }
              $scope.person.topics[topicKey].items.push(comment);
              $scope.person.topics[topicKey].count++;
              $scope.person.topics[topicKey].rating += comment.rating;
            }

            // scan all topics for this person phone to set topic rating median value
            for (var tKey in $scope.person.topics) {
              if (!$scope.person.topics.hasOwnProperty(tKey)) { // TODO: DEBUG-ONLY, REMOVE-ME
                console.error('§§§ (EXPECTED) §§§ - topic object with inherited property:', tKey);
              }
              if ($scope.person.topics.hasOwnProperty(tKey)) {
                var topicRating = $scope.person.topics[tKey].count ?
                  $scope.person.topics[tKey].rating / $scope.person.topics[tKey].count :
                  null
                ;
                $scope.person.topics[tKey].rating = topicRating; // set new median rating value
              }
            }

            console.log('°°° loadPerson() - personsPerComment:', $scope.personsPerComment);
/*
            $scope.person.topics = {};
            var commentsCount = $scope.person.comments.length;
            for (var i = 0, n = 0; i < commentsCount; i++) {
              var comment = $scope.person.comments[i];
              var key = comment.topic + '-' + comment.timestamp_creation + '-' + comment.author_nick;
              if (!key in $scope.person.topics) { // create topic and add comment to new topic
                $scope.person.topics[key] = {};
                $scope.person.topics[key].topic = comment.topic;
                $scope.person.topics[key].n = ++n;
                $scope.person.topics[key].timestamp_creation = comment.timestamp; // TODO: do we need this???
                $scope.person.topics[key].phone = $scope.person.phone; // TODO: do we need this???
                $scope.person.topics[key].personsPerTopic = $scope.personsPerComment[comment.id_comment];
                $scope.person.topics[key].rating = null;
                $scope.person.topics[key].items = [];
              } else { // topic already present
              }
              $scope.person.topics[key].items.push(comment);
            }
*/
          });
        });
      }
    });
  }

  function sortObjectToList(object, criteria) { // obj is an object of objects
    // order objects by sort criteria
    var list = Object.keys(object).sort(function(a, b) { // sort object of objects according to criteria returning keys
      var len = criteria.length;
      for (var i = 0; i < len; i++) {
        var crit = criteria[i].name;
        //console.log('crit:' + crit);
        var dir = criteria[i].direction;
        //console.log('dir:' + dir);
        if (crit) {
          var aval, bval;
          // consider null vote a median value
          if (crit === 'new') {
            crit = 'timestamp_creation';
            aval = object[a][crit];
            bval = object[b][crit];
          } else
          if (crit === 'source') {
            crit = 'url';
            aval = object[a][crit];
            bval = object[b][crit];
          } else
          if (crit === 'comments') {
            crit = 'comments_count';
            aval = parseInt(object[a][crit]);
            bval = parseInt(object[b][crit]);
          } else {
            if (crit === 'vote' && (object[a][crit] === null)) {
              aval = (cfg.person.vote.max - cfg.person.vote.min) / 2;
            } else {
              aval = object[a][crit] || '~'; // replace null values with '~', which comes after all ASCII characters
              //console.log('aval:', aval);
            }
            if (crit === 'vote' && (object[b][crit] === null)) {
              bval = (cfg.person.vote.max - cfg.person.vote.min) / 2;
            } else {
              bval = object[b][crit] || '~'; // replace null values with '~', which comes after all ASCII characters
              //console.log('bval:', bval);
            }
          }
          if (dir === 'ascending') {
            if (aval > bval) { return 1; }
            if (aval < bval) { return -1; }
          } else {
            if (aval > bval) { return -1; }
            if (aval < bval) { return 1; }
          }
        }
        // objects are equal, according to this criterium: proceed with next criterium
      }
      return 0; // no sort criteria can find an ordering
    }).map(function(key) { return object[key]; }); // map resulting array of keys to array of objects

// TODO: IGNORE persons with id_user !== 1 !!!

    function id2indexBuild(iStart, len) {
      for (var i = iStart; i < len; i++) { // rebuild id2index array
        id2index[list[i].id_person] = i;
      }
    }

//console.info('XXX LIST:', list);

    // aggregate uniq persons
    var len = list.length;
    var id2index = {}; // id's to list indexes mapping
    id2indexBuild(0, len);
//console.info('XXX ID2INDEX:', id2index);

    var i, j, src, dst, next;
    for (i = 0; i < len; i++) {
      if ((list[i].uniq_prev === null) && (list[i].uniq_next !== null)) { // a uniq primary
        for (j = id2index[list[i].uniq_next]; j !== undefined; j = next) {
          next = id2index[list[j].uniq_next];
          src = j;
          //dst = ++i; // NOOOOOOOOOOO
          dst = i + 1;
          list.move(src, dst);

          id2indexBuild(Math.min(src, dst), len);
          //id2indexBuild(0, len);
        }
      }
    }

    /*
     * pop up active persons in uniq chains, otherwise, if a not-active person is on
     * top, all chain should be hidden when 'show only active persons' filter is set
     */
//console.log('id2index:', id2index);
    for (i = 0; i < len; i++) {
      if ((list[i].uniq_prev === null) && (list[i].uniq_next !== null)) { // a uniq primary
//console.log('i:', i, ' - a uniq primary found');
//console.log('pop up active persons in uniq chains - starting j:', i);
        for (j = i; j !== undefined && list[j].active === '0'; j = id2index[list[j].uniq_next]) {
          // nop
//console.log('pop up active persons in uniq chains - found j:', j);
        }
//console.log('pop up active persons in uniq chains FINISHED - j:', j);
        if (j !== undefined && j !== i) { // the first active person found is not the first on this chain
          src = j;
          dst = i;
//console.log('moving index ' + src + ' to index ' + dst + '.');
          list.move(src, dst); // swap first person (not active) with first active person
          // swap uniq flags, too
          var src_next = list[src].uniq_next;
          var dst_next = list[dst].uniq_next;
          var src_prev = list[src].uniq_prev;
          var dst_prev = list[dst].uniq_prev;
          list[src].uniq_next = dst_next;
          list[dst].uniq_next = dst_prev;
          list[src].uniq_prev = src_next;
          list[dst].uniq_prev = src_prev;

          id2indexBuild(Math.min(src, dst), len);
//console.log('id2index:', id2index);
        }
      }
    }

/*
console.log('-------------');
for (i = 0; i < len; i++) {
  //console.log(list[i].name + ' active flag is ' + list[i].active);
  //if (list[i].uniq_next || list[i].uniq_prev) {
    console.log('i: ' + i + ', id: ' + list[i].id_person + ', active: ' + list[i].active + ', next: ' + (list[i].uniq_next ? list[i].uniq_next : 'X') + ', prev: ' + (list[i].uniq_prev ? list[i].uniq_prev : 'X') + ' => ' + list[i].name);
  //}
}
console.log('-------------');
*/
//console.log('scope.persons: ', $scope.persons);
//console.log('-------------');
/////////////////////////////////////////////////////////////////////////////////////////

    return list;
  }



  // public methods

  /* infinite scroll handling */
  $scope.displayedPerPage = 32;
  $scope.totalDisplayed = $scope.displayedPerPage;
  $scope.loadMore = function () {
    $scope.totalDisplayed += $scope.displayedPerPage;
    console.log('loadMore() - totalDisplayed:', $scope.totalDisplayed);
  };

  $scope.flipPersonInCommentActive = function(commentId, personId) {
    var len, i; //, active, person;
    var active = false;
    for (var pId in $scope.personsPerComment[commentId]) {
      if (pId === personId) {
        active = !$scope.personsPerComment[commentId][pId].active; // flip active flag
        // set active flag in personsPerComment
        $scope.personsPerComment[commentId][pId].active = active;
      } else {
        $scope.personsPerComment[commentId][pId].active = false;
      }
    }
    // if a person is active, set id_person in person comments, otherwise reset it
    len = $scope.person.comments.length;
    var comment;
    for (i = 0; i < len; i++) {
      comment = $scope.person.comments[i];
      if (comment.id_comment === commentId) {
        if (active) {
          comment.id_person = personId;
        } else {
          comment.id_person = null;
        }
      }
    }
    $scope.savePersonComment(comment);
  };

  $scope.flipPersonInCommentActive2 = function(commentId, personId) {
    var len, i; //, active, person;
    var active = false;
    for (var pId in $scope.personsPerComment[commentId]) {
      if (pId === personId) {
        active = !$scope.personsPerComment[commentId][pId].active; // flip active flag
        // set active flag in personsPerComment
        $scope.personsPerComment[commentId][pId].active = active;
      } else {
        $scope.personsPerComment[commentId][pId].active = false;
      }
    }
    // if a person is active, set id_person in person comments, otherwise reset it
    len = $scope.person.comments.length;
    var comment;
    for (i = 0; i < len; i++) {
      comment = $scope.person.comments[i];
      if (comment.id_comment === commentId) {
        if (active) {
          comment.id_person = personId;
        } else {
          comment.id_person = null;
        }
      }
    }
    $scope.savePersonComment(comment);
  };

  $scope.getPersonInCommentActive = function(commentId) {
    var personId;
    for (personId in $scope.personsPerComment[commentId]) {
      if ($scope.personsPerComment[commentId][personId].active) { // one person is active, return that
        return $scope.personsPerComment[commentId][personId];
      }
    }
    // no person is active, return a dummy object with a blank name
    for (personId in $scope.personsPerComment[commentId]) {
      return { name: '' };
      //return $scope.personsPerComment[commentId][personId];
    }
    // no person, return empty object (should not happen)
    return {};
  };
  $scope.getPersonInCommentActive2 = function(commentId) {
    var personId;
    for (personId in $scope.personsPerComment[commentId]) {
      if ($scope.personsPerComment[commentId][personId].active) { // one person is active, return that
        return $scope.personsPerComment[commentId][personId];
      }
    }
    // no person is active, return a dummy object with a blank name
    for (personId in $scope.personsPerComment[commentId]) {
      return { name: '' };
      //return $scope.personsPerComment[commentId][personId];
    }
    // no person, return empty object (should not happen)
    return {};
  };


  $scope.isAnyPersonInCommentActive = function(commentId) {
    // search current person for given comment id and check if any active person in comment is present
    var len = $scope.person.comments.length;
    for (var i = 0; i < len; i++) {
      if ($scope.person.comments[i].id_comment === commentId) {
        return !isNaN(parseInt($scope.person.comments[i].id_person));
      }
    }
    return false;
  };

  $scope.getPersonsInPersonComments = function() {
    // get the list of all persons related to this person's comments
    var persons = [];
    if (typeof $scope.personsPerComment === 'object') {
      Object.keys($scope.personsPerComment).forEach(function (commentId) {
        Object.keys($scope.personsPerComment[commentId]).forEach(function (personId) {
          var name = $scope.personsPerComment[commentId][personId].name;
          if (persons.indexOf(name) === -1) {
            persons.push(name);
          }
        });
      });
    }
    return persons;
  };

  $scope.countPersons = function() {
    var count = 0;
    Object.keys($scope.persons).forEach(function (id) { // scan all persons in persons
      if ($scope.isUniqPrimaryOrSingle(id)) {
        count++;
      }
    });
    return count;
  };

  /*
  $scope.back = function() {
    $location.path('/'); //.search({st: idPerson});
  };
  */

  $scope.openDetails = function(id, tab) {
    //$rootScope.openedId = id;
    if (tab) {
      $rootScope.tabSelected = tab;
    }
    $location.path('/details/' + id);
  };

  $scope.openDetailsInNewWindow = function(id, tab) {
    if (tab) { // set requested tab (if any) as the selected one
      $rootScope.tabSelected = tab;
    }
    $window.open('/#' + '/details/' + id, '_blank');
  };

  $scope.openInNewWindow = function(url) {
    url = url.replace(/\.\.\//g, ''); // TODO: do this when saving photo urls...
    $window.open(url, '_blank');
  };

  $scope.openInImageSearchWindow = function(photo) {
    var imageSearchUrl = 'https://www.google.com/searchbyimage?image_url';
    var windowName = '_image_search_window_';

    Authentication.apiUriExternal(
      function(response) {
        if (response.ip) {
          var imageUrl = 'http://' + response.ip + cfg.apiPath + '/' + photo.path_full;
          var url = imageSearchUrl + '=' + imageUrl;
          $window.open(url, windowName);
        }
      }
    );
  };

/*
  $scope.scrollToOpenedId = function() {
    $timeout(function() {
      if ($rootScope.openedId) {
//console.info(' £££ setting anchor scroll to ', $rootScope.openedId);
        var hash = $rootScope.openedId;
//console.info(' £££ setting anchor scroll to hash ', hash);
        $location.hash(hash);
        $anchorScroll();
        $location.hash(null);
        / * $anchorScroll($rootScope.openedId); // only valid since angular-1.4-rc-0... * /
        $rootScope.openedId = null;
//console.info(' $rootScope.openedId after null: ', $rootScope.openedId);
//console.info(' hash after null: ', hash);
      //} else { // reset anchor scroll
//console.info(' £££ resetting anchor scroll...');
        //$location.hash(null);
        //$anchorScroll();
      }
    });
  };
*/

  // TODO: REMOVE THIS METHOD, USE ONLY newness() ...
  $scope.isNew = function(person) {
    var timestampCreation = person.timestamp_creation * 1000;
    var now = new Date().getTime();
    return (now - timestampCreation) < (cfg.person.NEW_DURATION_DAYS * cfg.MILLISECONDS_PER_DAY);
  };

  $scope.newnessGetClass = function(person) {
    var className = 'person-newness-ages-ago';
    var timestampCreation = person.timestamp_creation * 1000;
    var now = new Date();

    var timestampOneDayAgo = new Date(now - (1 * cfg.MILLISECONDS_PER_DAY)).getTime();
    if (timestampCreation >= timestampOneDayAgo) { // creation date is not older than 24 hours
      className = 'person-newness-one-day-ago';
    } else {
      var timestampTwoDaysAgo = new Date(now - (2 * cfg.MILLISECONDS_PER_DAY)).getTime();
      if (timestampCreation >= timestampTwoDaysAgo) { // creation date is not older than 48 hours
        className = 'person-newness-two-days-ago';
      } else {
        var timestampOneWeekAgo = new Date(now - (7 * cfg.MILLISECONDS_PER_DAY)).getTime();
        if (timestampCreation >= timestampOneWeekAgo) { // creation date is not older than 48 hours
          className = 'person-newness-one-week-ago';
        } else {
          var timestampOneMonthAgo = new Date(now - (30 * cfg.MILLISECONDS_PER_DAY)).getTime();
          if (timestampCreation >= timestampOneMonthAgo) { // creation date is not older than 48 hours
            className = 'person-newness-one-week-ago';
          } else {
            var timestampOneYearAgo = new Date(now - (365 * cfg.MILLISECONDS_PER_DAY)).getTime();
            if (timestampCreation >= timestampOneYearAgo) { // creation date is not older than 48 hours
              className = 'person-newness-one-year-ago';
            }
          }
        }
      }
    }
    return className;
  };

  $scope.firstSeen = function(person) {
    var timestampCreation = person.timestamp_creation * 1000;
    var firstSeenAsString;
    var now = new Date();
    var timestampStartOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
    var timestampStartOfYesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() - (1 * cfg.MILLISECONDS_PER_DAY);
    var timestampStartOfBeforeYesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() - (2 * cfg.MILLISECONDS_PER_DAY);
    var timestampOneWeekAgo = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() - (7 * cfg.MILLISECONDS_PER_DAY);
    var timestampOneMonthAgo = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() - (30 * cfg.MILLISECONDS_PER_DAY);
    var timestampOneYearAgo = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime() - (365 * cfg.MILLISECONDS_PER_DAY);
    if (timestampCreation >= timestampStartOfToday) { // creation date is today
      firstSeenAsString = 'today, at ' + $filter('date')(timestampCreation, 'HH:mm');
    } else {
      if (timestampCreation >= timestampStartOfYesterday) { // creation date is yesterday
        firstSeenAsString = 'yesterday, at ' + $filter('date')(timestampCreation, 'HH:mm');
      } else {
        if (timestampCreation >= timestampStartOfBeforeYesterday) { // creation date is the day before yesterday
          firstSeenAsString = 'two days ago, at ' + $filter('date')(timestampCreation, 'HH:mm');
        } else {
          if (timestampCreation >= timestampOneWeekAgo) { // creation date is one week ago or less
            firstSeenAsString = 'this last week, on ' + $filter('date')(timestampCreation, 'dd MMMM yyyy');
          } else {
            if (timestampCreation >= timestampOneMonthAgo) { // creation date is one month ago or less
              firstSeenAsString = 'this last month, on ' + $filter('date')(timestampCreation, 'dd MMMM yyyy');
            } else {
              if (timestampCreation >= timestampOneYearAgo) { // creation date is one year ago or less
                firstSeenAsString = 'this last year, on ' + $filter('date')(timestampCreation, 'dd MMMM yyyy');
              } else { // default, creation date is older than one year since now
                firstSeenAsString = 'one year or more ago, on ' + $filter('date')(timestampCreation, 'dd MMMM yyyy');
              }
            }
          }
        }
      }
    }
    return firstSeenAsString;
  };

  $scope.addSortCriteria = function(criterium) {
    //console.info('addSortCriteria()', criterium);
    var index = $scope.sieves.sort.hasName(criterium);
    if (index < 0) {
      var len = $scope.sieves.sort.length;
      $scope.sieves.sort[len] = {
        'name': criterium, 'direction': (
          criterium === 'new' ||
          criterium === 'vote'
        ) ? 'descending' : 'ascending'
      };
      $scope.personsList = sortObjectToList($scope.persons, $scope.sieves.sort);
      Sieves.finalize(false);
    }
  };

  $scope.delSortCriteria = function(criterium) {
    var index = $scope.sieves.sort.hasName(criterium);
    if (index >= 0) {
      if ($scope.sieves.sort.removeByName(criterium)) {
        $scope.personsList = sortObjectToList($scope.persons, $scope.sieves.sort);
      }
      Sieves.finalize(false);
    }
  };

  $scope.hasSortCriteria = function(criterium) {
    return ($scope.sieves.sort.hasName(criterium) >= 0);
  };

  /*
  $scope.resetSortCriteria = function() {
    $scope.sieves.sort = Sieves.defaults.sort; // TODO: writing $scope.sieves, does update Sieves.sieves ???
    $scope.personsList = sortObjectToList($scope.persons, $scope.sieves.sort);
  };
  */
  $scope.getSortCriteriaDirection = function(criterium) {
    var index = $scope.sieves.sort.hasName(criterium);
    if (index >= 0) {
      return $scope.sieves.sort[index].direction;
    } else {
      return null;
    }
  };

  $scope.flipSortCriteriaDirection = function(criterium) {
    var index = $scope.sieves.sort.hasName(criterium);
    if (index >= 0) {
      if ($scope.sieves.sort[index].direction === 'ascending') {
        $scope.sieves.sort[index].direction = 'descending';
      } else {
        $scope.sieves.sort[index].direction = 'ascending';
      }
      $scope.personsList = sortObjectToList($scope.persons, $scope.sieves.sort);
      Sieves.finalize(false);
    }
  };

  $scope.setSortCriteriaDirection = function(criterium, direction) {
    var index = $scope.sieves.sort.hasName(criterium);
    if (index >= 0) {
      if ((direction === 'ascending') || (direction === 'descending')) {
        $scope.sieves.sort[index].direction = direction;
        $scope.personsList = sortObjectToList($scope.persons, $scope.sieves.sort);
      }
      Sieves.finalize(false);
    }
  };

  $scope.isUniq = function(personId) {
    return (
      ($scope.persons[personId].uniq_prev !== null) ||
      ($scope.persons[personId].uniq_next !== null)
    );
  };

  $scope.isUniqPrimary = function(personId) {
    var isUniqPrimary = false;
    if ($scope.persons[personId].uniq_prev === null) {
      //var id = personId;
      var idNext = personId;
      //var n = 0;
      do {
        idNext = $scope.persons[idNext].uniq_next;
        if (idNext && $scope.persons[idNext] && $scope.persons[idNext].active) {
          isUniqPrimary = true;
          break;
        }
        //if (++n > 10) {
        //  console.error('isUniqPrimary('+personId+'): INFINITE LOOP DETECTED!');
        //  break;
        //}
      } while (idNext && $scope.persons[idNext]);
    }
    return isUniqPrimary;
  };

  $scope.isUniqSecondary = function(personId) {
    return (
      ($scope.persons[personId].uniq_prev !== null)
    );
  };

  $scope.isUniqPrimaryOrSingle = function(personId) {
    return ($scope.persons[personId].uniq_prev === null);
  };

  $scope.isUniqLast = function(personId) {
    return (
      ($scope.persons[personId].uniq_prev !== null) &&
      ($scope.persons[personId].uniq_next === null)
    );
  };

  $scope.isUniqPrimaryShown = function(personId) {
    return (
      $scope.isUniqPrimary(personId) &&
      $scope.persons[personId].uniq_opened
    );
  };

  $scope.isUniqShown = function(personId) {
    return (
      $scope.isUniqPrimaryOrSingle(personId) ||
      ($scope.persons[personId].uniq_opened === true)
    );
  };

  $scope.uniqShow = function(personId) {
    if ($scope.isUniqPrimary(personId)) {
      var opened = $scope.persons[personId].uniq_opened ? false : true;
      $scope.persons[personId].uniq_opened = opened;
      var id = personId;
var n = 0;
      // TODO: infinite loop!!!!!!!!!!!!!!!!!!!!!!!
      do {
        id = $scope.persons[id].uniq_next;
        if (id) {
          $scope.persons[id].uniq_opened = opened;
        }
if (++n >= 100) { console.error('uniqShow(): INFINITE LOOP!!!'); break; } // TODO...
      } while (id);
    } else {
      console.error('ASSERT FAILURE: Can\'t uniqShow('+personId+') on a secondary uniq!!!'); // TODO...
    }
  };

/*
  $scope.uniqShowAll = function() {
    for (var personId in $scope.persons) {
      if ($scope.persons.hasOwnProperty(personId)) {
        var person = $scope.persons[personId];
        person.uniq_opened = true;
      }
    }
  };

  $scope.uniqHideAll = function() {
    for (var personId in $scope.persons) {
      if ($scope.persons.hasOwnProperty(personId)) {
        $scope.persons[personId].uniq_opened = false;
      }
    }
  };
*/

  $scope.savePerson = function(person) {
    if (!$scope.userId) {
      // reload person to restore previous data (TODO: use a better strategy: save person after loading, and restore it now...)
      Persons.getPerson($scope.personId, $scope.userId).then(function(person) {
        angular.copy(person, $scope.person); // TODO: do we need angular.copy(), here?
      });
      return;
    }
    var personId = person.id_person;
    var detailFields = [
      'name',
      'sex',
      'zone',
      'street_address',
      'description',
      'notes',
      'phone',
      'nationality',
      'age',
      'vote',
      'rating',
      'showcase',
      'truthful',
    ];
    var personDetail = {};
    angular.forEach(person, function(value, key) {
      if (detailFields.indexOf(key) !== -1) {
        this[key] = value;
      }
    }, personDetail);

    Persons.setPerson(personId, [], personDetail, $scope.userId).then(
      function(/*successMessage*/) {
        console.info('PERSON SAVED');
      },
      function(errorMessage) {
        notify.error(errorMessage);
      }
    );
  };

  $scope.savePersonComment = function(comment) {
    var detailFields = [
      'id_comment',
      'id_person',
      'content_rating',
    ];
    var commentDetail = {};
    angular.forEach(comment, function(value, key) {
      if (detailFields.indexOf(key) !== -1) {
        this[key] = value;
      }
    }, commentDetail);
    //console.log('commentDetail:', commentDetail);

    Comments.setComment(comment.id, [], commentDetail, $scope.userId).then(
      function(/*successMessage*/) {
        console.info('Comment saved correctly');
      },
      function(errorMessage) {
        notify.error(errorMessage);
      }
    );
  };

  $scope.savePersonPhoto = function(photo) {
    var detailFields = [
      'id_photo',
      'truthful',
    ];
    var photoDetail = {};
    angular.forEach(photo, function(value, key) {
console.log('key:', key, ', value:', value);
      if (detailFields.indexOf(key) !== -1) {
        this[key] = value;
      }
    }, photoDetail);
console.log('photoDetail:', photoDetail);
console.log('photo:', photo);

    Photos.setPhoto(photo.id, [], photoDetail, $scope.userId).then(
      function() {
        console.info('Photo saved correctly');
      },
      function(errorMessage) {
        notify.error(errorMessage);
      }
    );
  };

  $scope.addPerson = function() {
    Persons.addPerson($scope.personMaster, $scope.personDetail, $scope.userId).then(
      loadPersons,
      function(errorMessage) {
        console.warn(errorMessage);
      }
    );
  };

  $scope.removePerson = function(personId) {
    Persons.removePerson(personId, $scope.userId).then(
      loadPersons,
      function(errorMessage) {
        console.warn(errorMessage);
      }
    );
  };

  $scope.tabSelect = function (tabName, data) {
    //console.log('Selecting tab ' + tabName);
    if (tabName === 'photos') {
      var number = data;
      $scope.person.photos[0].active = false;
      $scope.person.photos[number].active = true;
    }
    // TODO: try to avoid carousel sliding on carousel loading...
    $scope.tabSelected = tabName;
  };

  // TODO: photoOccurrences is obsoleted, remove anything related...

/* TODO: we check photo availability server-side, DELETE THIS CODE...
  $scope.checkImageUrl = function(url) {
    var callback = function (url, result) {
      console.log("result for image at " + url + " is " + result);
    };
    var timeout = 5 * 1000; // 5 seconds
    var timedOut = false, timer;
    var img = new Image();
    img.onerror = img.onabort = function() {
      if (!timedOut) {
        clearTimeout(timer);
        callback(url, "error");
      }
    };
    img.onload = function() {
      if (!timedOut) {
        clearTimeout(timer);
        callback(url, "success");
      }
    };
    img.src = url;
    timer = setTimeout(function() {
      timedOut = true;
      callback(url, "timeout");
    }, timeout); 
  };
*/

/*
  $scope.getPhotoOccurrences = function(id, url) {
    $scope.tabSelected = 'photosOccurrences';
    $scope.tabs.photosOccurrences.hidden = false;
    $scope.photosOccurrencesLoading = true;
    $scope.photosOccurrences = [];
    //notify.info('getPhotoOccurrences(' + url + ')');
    Persons.getPhotoOccurrences(id, url).then(
      function(response) {
        //console.info('+++ getPhotoOccurrences response:', response);
        $scope.photosOccurrencesLoading = false;
        $scope.photosOccurrencesBestGuess = response.bestGuess;
        //$scope.photosOccurrences = response.searchResults;
        if (response.searchResults.length === 0) {
          console.log('No occurrences found...');
        } else {
          console.info('Occurrences found:', response);
        }
        // filter searchResults with withelist to photosOccurrences
        angular.forEach(response.searchResults, function(element) {
          if ($scope.whitelist(element)) {
            this.push(element);
          }
        }, $scope.photosOccurrences);
        //console.info('Persons.getPhotoOccurrences - typeof response:', typeof response);
      },
      function(errorMessage) {
        console.warn(errorMessage);
        $scope.photosOccurrencesLoading = false;
      }
    );
  };

  $scope.photosOccurrencesAddToWhitelist = function(url) {
    var domain = url.parseUrl().hostname;
    console.info('adding to whitelist domain (' + domain + ') ...');
    if ($scope.photosOccurrencesWhitelist.indexOf(domain) === -1) {
      $scope.photosOccurrencesWhitelist.push(domain);
      console.info('added domain [' + domain + '] to whitelist');
      // TODO: save whitelist to somewhere... (add a photos-occurrences-domains-whitelist service...)
    }
  };

  / **
   * whitelist filter
   * /
  $scope.whitelist = function(element) {
    // TODO: load whitelist from somewhere... (add a photos-occurrences-domains-whitelist service...)
    $scope.resizeForce = true;
    return ($scope.photosOccurrencesWhitelist.indexOf(element.href.parseUrl().hostname) === -1);
  };

  $scope.photosOccurrencesTruthful = function() {
    //console.log('It is truthful!');
    $scope.tabSelected = 'photos';
    $scope.photosOccurrences = [];
    $scope.tabs.photosOccurrences.hidden = true;
  };

  $scope.photosOccurrencesFake = function() {
    //console.log('It is fake!');
    $scope.tabSelected = 'photos';
    $scope.photosOccurrences = [];
    $scope.tabs.photosOccurrences.hidden = true;
  };

  $scope.photosOccurrencesUndecided = function() {
    //console.log('Don\'t know...');
    $scope.tabSelected = 'photos';
    $scope.photosOccurrences = [];
    $scope.tabs.photosOccurrences.hidden = true;
  };
*/

  $scope.photoIsTruthful = function(n) {
    //console.log('Checking if photo '+n+' is truthful');
    return $scope.person.photos[n].truthful === 'true';
  };

  $scope.photoIsUnknown = function(n) {
    //console.log('Checking if photo '+n+' is unknown');
    return (!$scope.person.photos[n].truthful) || ($scope.person.photos[n].truthful === 'unknown');
  };

  $scope.photoIsUntruthful = function(n) {
    //console.log('Checking if photo '+n+' is fake');
    return $scope.person.photos[n].truthful === 'false';
  };

  $scope.photoSetTruthful = function(n) {
    console.log('Setting photo '+n+' as truthful');
    $scope.person.photos[n].truthful = 'true';
    $scope.savePersonPhoto($scope.person.photos[n]); // save new truthfulness flag
  };

  $scope.photoSetUnknown = function(n) {
    console.log('Setting photo '+n+' as unknown');
    $scope.person.photos[n].truthful = 'unknown';
    $scope.savePersonPhoto($scope.person.photos[n]); // save new truthfulness flag
  };

  $scope.photoSetUntruthful = function(n) {
    console.log('Setting photo '+n+' as fake');
    $scope.person.photos[n].truthful = 'false';
    $scope.savePersonPhoto($scope.person.photos[n]); // save new truthfulness flag
  };

  $scope.photoSetTruthfulAllAs = function(n) {
    console.log('Setting all truthfulness unset potos as photo '+n+'');
    var thisTruthfulness = $scope.person.photos[n].truthful;
    var count = 0;
    for (var i = 0; i < $scope.person.photos.length; i++) {
      if ($scope.photoIsUnknown(i)) {
        $scope.person.photos[i].truthful = thisTruthfulness;
        $scope.savePersonPhoto($scope.person.photos[i]); // save new truthfulness flag
        count++;
      }
    }
    if (count > 0) {
      notify.info(count + ' photo' + (count === 1 ? '' : 's') + ' marked as ' + (thisTruthfulness === 'true' ? 'truthful' : thisTruthfulness === 'false' ? 'fake' : 'unknown'));
    } else {
      notify.info('All photos already had their truthfulness flag set');
    }
  };

  $scope.nameChange = function() {
    //console.log('name change: ', $scope.person.name);
    $scope.savePerson($scope.person);
  };

  $scope.descriptionChange = function() {
    //console.log('description change: ', $scope.person.description);
    $scope.savePerson($scope.person);
  };

  $scope.notesChange = function() {
    //console.log('notes change: ', $scope.person.notes);
    $scope.savePerson($scope.person);
  };

  $scope.countryChange = function(code) {
    //console.log('countryChange(): ', code);
    $scope.person.nationality = code;
    //$scope.personCountryFilter = $scope.countries.getCountryName(code);
    $scope.savePerson($scope.person);
  };

  $scope.phoneChange = function() {
    // TODO: reload comments and reassert persons uniqueness (???), based on this new phone, eventually after a timeout...! (or put a new watch in controller...)
    //console.log('phone change: ', $scope.person.phone);
    $scope.savePerson($scope.person);
  };

  $scope.voteChange = function(delta) {
    if (delta === 0) { // noop
      return;
    }
    if ($scope.person.vote === null) {
      if (delta > 0) {
        $scope.person.vote = 0;
      } else {
        // do nothing
      }
    } else {
      if ($scope.person.vote === 0 && delta < 0) {
        $scope.person.vote = null;
      } else {
        if (delta > 0) {
          $scope.person.vote = Math.min(cfg.person.vote.max, parseInt($scope.person.vote) + parseInt(delta));
        } else {
          $scope.person.vote = Math.max(cfg.person.vote.min, parseInt($scope.person.vote) + parseInt(delta));
        }
      }
    }
    $scope.savePerson($scope.person);
  };

  $scope.ratingChange = function(commentId, ratingDelta) {
    if (!(commentId in $scope.personsPerComment)) { // comment with given id not found on this person, shouldn't happen
      console.error('can\'t find a comment with id ' + commentId + ' for this person');
      return;
    }
    if (ratingDelta === 0) { // noop (should not happen)
      return;
    }

    // check a person is active, otherwise notify a request to select one
    if (!$scope.isAnyPersonInCommentActive(commentId)) {
      notify.warning('Select the person of this comment, above, before rating the comment, please');
      return;
    }

    var commentNum = null;
    var len = $scope.person.comments.length;
    for (var i = 0; i < len; i++) { // list all comments (possibly) linked to this person (effectively, to her phone)
      if ($scope.person.comments[i].id_comment === commentId) {
        commentNum = i;
        break;
      }
    }
    var comment = $scope.person.comments[commentNum];
    if (isNaN(parseInt(comment.content_rating))) {
      comment.content_rating = 0;
    }
    if (ratingDelta > 0) {
      comment.content_rating = Math.min(cfg.person.rating.max, parseInt(comment.content_rating) + parseInt(ratingDelta));
    } else {
      comment.content_rating = Math.max(cfg.person.rating.min, parseInt(comment.content_rating) + parseInt(ratingDelta));
    }

    // calculate person's rating from all her comments ratings
    $scope.person.rating = $scope.personRatingFromCommentsRatings();

    //console.log('--- person.rating => ', $scope.person.rating);
    $scope.savePerson($scope.person); // for new rating only
    $scope.savePersonComment(comment); // for new comment
  };

  $scope.streetAddressChange = function(person) {
    //console.log('+++ saving person for street address changed:', person);
    $scope.savePerson(person);
  };

  $scope.personRatingFromCommentsRatings = function() {
    // TODO: refine method to calculate person's rating from all her comments ratings
    var ratings = 0;
    var count = 0;
    var len = $scope.person.comments.length;
    for (var i = 0; i < len; i++) {
      if (!isNaN(parseInt($scope.person.comments[i].content_rating))) {
        if ($scope.person.comments[i].id_person === $scope.person.id_person) {
          ratings += parseInt($scope.person.comments[i].content_rating);
          count++;
        }
      }
    }
    if (count > 0) {
      return parseInt(ratings / count);
    } else {
      return null; // no ratings set for any comment for this person
    }
  };

  $scope.loadMapAndPano = function(address) {
    // check if GPS has been locally cached
    var geocoder = new google.maps.Geocoder();
    if (!address) {
      $scope.mapError = $scope.panoramaError = 'No address given';
      //console.warn('please specify an address...');
      return;
    }
    geocoder.geocode({ 'address': address }, function(results, status) {
      if (status === google.maps.GeocoderStatus.OK) {
        var gps = results[0].geometry.location;
        $scope.createMapAndPano(gps.lat(), gps.lng(), 'map-canvas', 'panorama-canvas');

       $('#streetAddressIndicationsModalPopup').on('shown.bs.modal', function() {
         //console.log('on #streetAddressIndicationsModalPopup shown.bs.modal');
         google.maps.event.trigger($scope.map, 'resize');
         $scope.map.setCenter($scope.addLatLng);
       });
     
       $('#streetAddressPanoramaModalPopup').on('shown.bs.modal', function() {
         //console.log('on #streetAddressPanoramaModalPopup shown.bs.modal');
         google.maps.event.trigger($scope.panorama, 'resize');
       });

      } else {
        if (status === google.maps.GeocoderStatus.ZERO_RESULTS) {
          $scope.mapError = $scope.panoramaError = 'Address "' + address + '" not found';
          console.warn('can\'t find address ' + address);
        } else {
          if (status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT) { // over query limit,VER_QUERY_LIMIT status code: retry in a moment...          
            $scope.mapError = $scope.panoramaError = 'Please wait for address geo-codification...';
            setTimeout(function() {
              console.warn('retrying loadMapAndPano() ...');
              $scope.mapError = $scope.panoramaError = null;
              $scope.loadMapAndPano(address);
            }, 1000);
          } else { // other error
            $scope.mapError = $scope.panoramaError = 'Address "' + address + '" not found (status is: ' + status + ')';
            console.error('can\'t find address ' + address + ' (geocoder status is ' + status + ')');
          }
        }
      }
    });
  };

  $scope.createMapAndPano = function(lat, lng, mapId, panoramaId) {
    $scope.panorama = new google.maps.StreetViewPanorama(document.getElementById(panoramaId));
    $scope.addLatLng = new google.maps.LatLng(lat, lng);
    var service = new google.maps.StreetViewService();
    var radiusMeters = 50;
    var zoom = 14;
    service.getPanoramaByLocation($scope.addLatLng, radiusMeters, $scope.showPanoData);

    var options = {
      zoom: zoom,
      center: $scope.addLatLng,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      backgroundColor: 'transparent',
      streetViewControl: false,
      keyboardShortcuts: false
    };
    $scope.map = new google.maps.Map(document.getElementById(mapId), options);
    new google.maps.Marker({
      map: $scope.map,
      animation: google.maps.Animation.DROP,
      position: $scope.addLatLng
    });
  };

  $scope.showPanoData = function(panoData, status) {
    if (status !== google.maps.StreetViewStatus.OK) {
      $scope.panoramaError = 'No StreetView available';
      return;
    }
    var angle = $scope.computeAngle(panoData.location.latLng, $scope.addLatLng);
    var panoOptions = {
      position: $scope.addLatLng,
      addressControl: false,
      linksControl: false,
      panControl: true,
      zoomControlOptions: {
        style: google.maps.ZoomControlStyle.SMALL
      },
      pov: {
        heading: angle,
        pitch: 10,
        zoom: 1
      },
      enableCloseButton: false,
      visible: true,
    };
    $scope.panorama.setOptions(panoOptions);
  };

  $scope.computeAngle = function(startLatLng, endLatLng) {
    var DEGREE_PER_RADIAN = 57.2957795;
    var RADIAN_PER_DEGREE = 0.017453;

    var dlat = endLatLng.lat() - startLatLng.lat();
    var dlng = endLatLng.lng() - startLatLng.lng();
    /*
     * we multiply dlng with cos(endLat), since the two points are very closeby,
     * so we assume their cos values are approximately equal
     */
    var yaw = Math.atan2(dlng * Math.cos(endLatLng.lat() * RADIAN_PER_DEGREE), dlat) * DEGREE_PER_RADIAN;
    return $scope.wrapAngle(yaw);
  };

  $scope.wrapAngle = function(angle) {
    if (angle >= 360) {
      angle -= 360;
    } else {
      if (angle < 0) {
        angle += 360;
      }
    }
    return angle;
  };

  if (!$scope.personId) { // load persons list
    loadPersons();
  } else { // load single person
    loadPerson();
  }

});