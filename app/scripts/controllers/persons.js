'use strict';

app.controller('PersonsController', function($scope, $rootScope, $routeParams, $modal, $timeout, $location, $anchorScroll, $filter, $window, cfg, notify, Authentication, Countries, Persons, Comments, Sieves) {
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
    'photosOccurrences': { // TODO: think of a better name...
      'description': 'Photos occurrences',
      'hidden': true,
      'loading': false,
    },
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
  $scope.photosOccurrencesWhitelist = [];
  $scope.personsEmpty = false; // not yet loaded
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
  $rootScope.$on('sievesChangedHard', function(/*event, args*/) {
    console.log('SIEVES - sievesChanged HARD');
    loadPersons(); // re-load persons list
  });
  $rootScope.$on('sievesChangedSoft', function(/*event, args*/) {
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
    $scope.personsList = sortObjectToList(persons, $scope.sieves.sort/*$scope.sortCriteria*/);
    $scope.personsCount = $scope.countPersons(); // for footer controller
    $scope.personsEmpty = $scope.personsCount === 0;
    $rootScope.$emit('personsLoaded', { personsCount: $scope.personsCount });
    $scope.scrollToOpenedId(); // scroll to remembered row id
  }
  
  function loadPersons() {
    console.log('LOAD PERSONS');
    $scope.personsLoading = true;
    Persons.getPersons(Sieves.sieves, $scope.userId).then(function(persons) {
      applyPersons(persons);
    });
  }

  function loadPerson() { // load single person
    $rootScope.openedId = $scope.personId;
//console.info(' $rootScope.openedId after PERSON OPENED: ', $rootScope.openedId);
    Persons.getPerson($scope.personId, $scope.userId).then(function(person) {
//      angular.copy(person, $scope.person); // TODO: do we need angular.copy(), here?
$scope.person = person;

      console.log('PERSON:', $scope.person);

      //$scope.person.street_region = 'it'; // TODO: get street_region from setup/cfg ?
      // watch for person street address changes
      $scope.$watch('person.street_address', function() {
        $scope.mapError = $scope.panoramaError = null;
        //console.log('$watch: person.street_address changed to', person.street_address);
        $scope.loadMapAndPano($scope.person.street_address);
      });

      //$scope.personCountryFilter = $scope.countries.getCountryName(person.nationality);
      $scope.personsPerComment = {};
      if (!$scope.person.phone) { // empty phone, do not load comments
        $scope.person.comments = [];
      } else { // phone is present, do load comments
        Comments.getCommentsByPhone($scope.person.phone, $scope.userId).then(function(comments) {
          //console.log('comments for ' + $scope.person.phone + ':', comments);
          $scope.person.comments = comments;
          /*
           * $scope.person.comments contains all comments linked to the person's phone;
           * if they lack "id_person" field, they could be relative to another person
           * with the same phone.
           */
          //console.log('------------------------------ persons:', $scope.persons);
          var len = $scope.person.comments.length;
          //console.log('length of comments for this person:', len);
          var commentId;
          for (var i = 0; i < len; i++) { // loop through all comments (possibly) linked to this person (effectively, to her phone)
            commentId = $scope.person.comments[i].id_comment;
            $scope.personsPerComment[commentId] = {};
            //console.log('id of current ('+i+') comment:', commentId);
            for (var personId in $scope.persons) { // loop through all persons
              if ($scope.person.phone === $scope.persons[personId].phone) { // phone matches
                //console.log(' +++ id of person with a matching phone:', personId);
                //console.log('     name of person with a matching phone:', $scope.persons[personId].name);
                var active = false;
                if ($scope.person.comments[i].id_person === personId) { // this comment has a specific id_person set: set that person as active
                  active = true;
                }
                $scope.personsPerComment[commentId][personId] = { name: $scope.persons[personId].name, active: active };
              }
            }
          }
          //console.log('------------------------------');
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
        var dir = criteria[i].direction;
        if (crit) {
          if (dir === 'ascending') {
            if (object[a][crit] > object[b][crit]) { return 1; }
            if (object[a][crit] < object[b][crit]) { return -1; }
          } else {
            if (object[a][crit] > object[b][crit]) { return -1; }
            if (object[a][crit] < object[b][crit]) { return 1; }
          }
        }
        // objects are equal, according to this criterium: proceed with next criterium
      }
      return 0; // no sort criteria can find an ordering
    }).map(function(key) { return object[key]; }); // map resulting array of keys to array of objects

    function id2indexBuild() {
      for (var i = 0; i < len; i++) { // rebuild id2index array
        var id = list[i].id_person;
        id2index[id] = i;
      }
    }

    var len = list.length;
    var id2index = {}; // id's to list indexes mapping
    var i;
    id2indexBuild();
    for (i = 0; i < len; i++) {
      if ((list[i].uniq_prev === null) && (list[i].uniq_next !== null)) { // a uniq primary
        for (var next, j = id2index[list[i].uniq_next]; j !== undefined; j = next) {
          next = id2index[list[j].uniq_next];
          var src = j;
          var dst = ++i;
          list.move(src, dst);
          id2indexBuild();
        }
      }
    }
    return list;
  }



// TODO: check which currently "public" methods could be set as "private"...
  // public methods

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

  $scope.getPersonInCommentActive = function(commentId) {
    var personId;
    for (personId in $scope.personsPerComment[commentId]) {
      if ($scope.personsPerComment[commentId][personId].active) { // one person is active, return her
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

  $scope.back = function(/*idPerson*/) {
//console.info('Going back with search hash of ', idPerson);
    $location.path('/'); //.search({st: idPerson});
  };

  $scope.open = function(id, tab) {
    //$rootScope.openedId = id;
    $rootScope.tabSelected = tab;
    $location.path('/details/' + id);
  };

  $scope.openInNewTab = function(url) {
    $window.open(url, '_blank');
  };

  $scope.scrollToOpenedId = function() {
    $timeout(function() {
      if ($rootScope.openedId) {
//console.info(' £££ setting anchor scroll to ', $rootScope.openedId);
        var hash = $rootScope.openedId;
//console.info(' £££ setting anchor scroll to hash ', hash);
        $location.hash(hash);
        $anchorScroll();
        $location.hash(null);
        /* $anchorScroll($rootScope.openedId); // only valid since angular-1.4-rc-0... */
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

  $scope.isNew = function(person) {
    var timestampLastSync = person.timestamp_last_sync * 1000;
    var now = new Date();
    return (now - timestampLastSync) < (cfg.person.NEW_DURATION_DAYS * cfg.MILLISECONDS_PER_DAY);
  };

  $scope.firstSeen = function(person) {
    var MILLISECONDS_PER_DAY = (1000 * 60 * 60 * 24);
    var timestampCreation = person.timestamp_creation * 1000;
    var firstSeenAsString;
    var now = new Date();
    var timestampStartOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var timestampStartOfYesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate()) - (1 * MILLISECONDS_PER_DAY);
    var timestampOneWeekAgo = new Date(now - (7 * MILLISECONDS_PER_DAY));
    var timestampOneMonthAgo = new Date(now - (30 * MILLISECONDS_PER_DAY));
    var timestampOneYearAgo = new Date(now - (365 * MILLISECONDS_PER_DAY));
    if (timestampCreation >= timestampStartOfToday) { // creation date is today
      firstSeenAsString = 'today, at ' + $filter('date')(timestampCreation, 'HH:mm');
    } else {
      if (timestampCreation >= timestampStartOfYesterday) { // creation date is yesterday
        firstSeenAsString = 'yesterday, at ' + $filter('date')(timestampCreation, 'HH:mm');
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
              firstSeenAsString = 'more than one year ago, on ' + $filter('date')(timestampCreation, 'dd MMMM yyyy');
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
      $scope.sieves.sort[len] = { 'name': criterium, 'direction': 'ascending' };
      //console.info('sievs sort: ', $scope.sieves.sort);
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

  $scope.shorten = function(name) {
    var maxlen = 20;
    if (name.length > maxlen) {
      name = name.substr(0, maxlen - 1) + '…';
    }
    return name;
  };

  $scope.isUniqPrimary = function(personId) {
    return (
      ($scope.persons[personId].uniq_prev === null) &&
      ($scope.persons[personId].uniq_next !== null)
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

  $scope.isUniqShown = function(personId) {
    return (
      $scope.isUniqPrimaryOrSingle(personId) ||
      ($scope.persons[personId].uniq_opened === true)
    );
  };

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
      'thruthful',
      'uniq_prev',
      'uniq_next',
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

  /**
   * whitelist filter
   */
  $scope.whitelist = function(element) {
    // TODO: load whitelist from somewhere... (add a photos-occurrences-domains-whitelist service...)
    $scope.resizeForce = true;
    return ($scope.photosOccurrencesWhitelist.indexOf(element.href.parseUrl().hostname) === -1);
  };

  $scope.photosOccurrencesThruthful = function() {
    //console.log('It is thruthful!');
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

  $scope.voteChange = function(vote) {
    if (vote === 0) { // noop
      return;
    }
//if (!$scope.person.vote) { console.info('$scope.person.vote:', $scope.person.vote); }
    if (!$scope.person.vote) {
      $scope.person.vote = 0;
    }
    if (vote > 0) {
      $scope.person.vote = Math.min(cfg.person.vote.max, parseInt($scope.person.vote) + parseInt(vote));
    } else {
      $scope.person.vote = Math.max(cfg.person.vote.min, parseInt($scope.person.vote) + parseInt(vote));
    }
    //$scope.persons[$scope.personId].vote = $scope.person.vote; // TODO: DO WE NEED THIS INSTRUCTION?
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
      console.warn('please specify an address...');
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
    if ($scope.persons.length === 0) { // TODO: DO WE NEED THIS TEST??? NOOO
    loadPersons();
    }
    else { console.info('§§§§§§§§§§§§§ PERSONS NOT LOADED §§§§§§§§§§§§ $scope.persons.length:', $scope.persons.length);}
  } else { // load single person
    loadPerson();
  }

});