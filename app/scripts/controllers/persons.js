'use strict';

app.controller('PersonsController', function($scope, $rootScope, $routeParams, $modal, $timeout, $location, $anchorScroll, $filter, $window, cfg, notify, Authentication, Countries, Persons, Comments, Sieves) {
  $scope.persons = [];
  $scope.person = {};

  $scope.personId = $routeParams.personId;
  $scope.tabs = {
    'main': {
      'description': 'Main',
      'hidden': false,
    },
    'photos': {
      'description': 'Photos',
      'hidden': false,
    },
    'photosOccurrences': { // TODO: think of a better name, please...
      'description': 'Photos occurrences',
      'hidden': true,
      'loading': false,
    },
    'comments': {
      'description': 'Comments',
      'hidden': false,
    },
  };
  $scope.tabSelected = 'main';
  $scope.countries = Countries;
  $scope.person.streetLocation = '[0, 0]'; // to avoid geolocation prompts...
  $scope.cfg = cfg; // make cfg data available to scope
  //$scope.username = $rootScope.username;
  //$scope.username = $rootScope.globals.currentUser.username;
  $scope.userId = (typeof $rootScope.globals.currentUser !== 'undefined') ? $rootScope.globals.currentUser.id : null;
  $scope.sortCriteria = {};
  $scope.openedId = null;
  $scope.Sieves = Sieves;
  $scope.Sieves.load();
  $scope.sieves = Sieves.sieves;
  $scope.photosOccurrencesWhitelist = [];

  // watch for sieves changes
  $scope.$watch('Sieves.getDigest()', function() {
    console.log('Sieves.getDigest() CHANGED, RELOADING SIEVES...');
    loadPersons(); // load persons list
  }, false);

  // private methods
  function applyPersons(persons) {
    console.log('PERSONS: ', persons);
    $scope.persons = persons;
    //$scope.sortCriteria.name = true;
    $scope.personsList = sortObjectToList(persons, $scope.sieves.sort/*$scope.sortCriteria*/);
    if ($rootScope.openedId) { // scroll to remembered row id
      console.info('scope.openedId:', $rootScope.openedId);
      $scope.scrollTo($rootScope.openedId);
    }
  }

  function loadPersons() {
    //console.log('loadPersons() - Sieves.sieves:', Sieves.sieves);
    //Persons.getPersons($rootScope.sieves).then(function(persons) {
    Persons.getPersons(Sieves.sieves).then(function(persons) {
      applyPersons(persons);
    });
  }

  function sortObjectToList(object, criteria) { // obj is an object of objects
    // order objects by sort criteria
console.log('sortObjectToList():', object, criteria);
    var list = Object.keys(object).sort(function(a, b) { // sort object of objects according to criteria returning keys
      var len = criteria.length;
      for (var i = 0; i < len; i++) {
        var crit = criteria[i].name;
        var dir = criteria[i].direction;
        if (crit) {
          if (dir === 'ascending') {
            if (object[a][crit] > object[b][crit]) { /*console.log(criteria[i].name, 'asc', '>');*/ return 1; }
            if (object[a][crit] < object[b][crit]) { /*console.log(criteria[i].name, 'asc', '<');*/ return -1; }
          } else {
            if (object[a][crit] > object[b][crit]) { /*console.log(criteria[i].name, 'desc', '>');*/ return -1; }
            if (object[a][crit] < object[b][crit]) { /*console.log(criteria[i].name, 'desc', '<');*/ return 1; }
          }
        }
        // objects are equal, according to this criterium: proceed with next criterium
      }
      return 0; // no sort criteria can find an ordering
    }).map(function(key) { return object[key]; }); // map resulting array of keys to array of objects

    // aggregate uniq lists in sorted list
    var len = list.length;
    /* jshint camelcase: false */
    for (var i = 0; i < len; i++) {
      if ((list[i].uniq_prev === null) && (list[i].uniq_next !== null)) { // a uniq primary
        var next;
        for (var j = searchArrayByIdPerson(list, list[i].uniq_next); j !== null; j = next) {
          next = searchArrayByIdPerson(list, list[j].uniq_next);
          var src = j;
          var dst = ++i;
          list.move(src, dst);
        }
      }
    }
    /* jshint camelcase: true */
    return list;
  }

  function searchArrayByIdPerson(array, personId) {
    var len = array.length;
    for (var i = 0; i < len; i++) {
      /* jshint camelcase: false */
      if (array[i].id_person === personId) {
      /* jshint camelcase: true */
        return i;
      }
    }
    return null;
  }

  if ($scope.personId) { // load single person
    Persons.getPerson($scope.personId).then(function(person) {
      if (!!cfg.fake) { console.log('person(', $scope.personId, ')', person); }
      angular.copy(person, $scope.person); // TODO: do we need angular.copy(), here?
      console.log('Persons.getPerson:', $scope.person);
      //$scope.person = person;
      $scope.person.nationality = 'it';
      //$scope.person.nat = $scope.person.nationality; // TODO... ???
      $scope.person.vote = 5;
      //$scope.person.streetAddress = 'Torino, Via Carlo Pisacane, 39';
      $scope.person.streetAddress = $scope.person.address;
      $scope.person.streetRegion = 'it';
      $scope.$watch('person.streetAddress', function() {
         console.log('$watch: Hey, person.streetAddress has changed!');
         $scope.person.streetAddressImageUrl = $scope.streetAddressToImageUrl($scope.person.streetAddress);
         $scope.geocode($scope.person.streetAddress, $scope.person.streetRegion);
      });

      if (!$scope.person.phone) { // empty phone, do not load comments
        $scope.person.comments = [];
        $scope.personsPerComment = {};
      } else { // active phone, do load comments
/*
 *      $personsPerComment[] = [
 *        "id_person" => $person["id_person"],
 *        "name" => $person["name"]
 *      ];
 */
        //$scope.personsPerComment = {};
        $scope.personsPerComment = [];

        Comments.getCommentsByPhone($scope.person.phone).then(function(comments) {
          console.log('comments for ' + $scope.person.phone + ':', comments);
          $scope.person.comments = comments;
          /*
           * $scope.person.comments contains all comments linked to the person's phone;
           * if they lack "id_person" field, they could be relative to another person
           * with the same phone.
           */
          /*
          var len = $scope.person.comments.length;
          //console.error('$scope.person.comments', $scope.person.comments);
          for (var i = 0; i < len; i++) {
            $scope.getPersonsPerComment($scope.person.comments[i].id);
          }
          */
          console.log('------------------------------');
          var len = $scope.person.comments.length;
//console.log('phone of comment', i, $scope.person.phone);
          for (var personId in $scope.persons) {
//console.log(' personId:', personId);
            if ($scope.person.phone === $scope.persons[personId].phone) {
              //console.log(personId);
              var active = false;
              for (var i = 0; i < len; i++) { // list all comments (possibly) linked to this person (effectively, to her phone)
                /* jshint camelcase: false */
                if ($scope.person.comments[i].id_person) { // this comment has a specific id_person set: set that person as active
                  if (personId === $scope.person.comments[i].id_person) {
                    active = true;
                    break;
                  }
                }
                /* jshint camelcase: true */
              }
              // TODO: $scope.personsPerComment should be indexed by commentId, before personId !!! ...
              $scope.personsPerComment.push({ id: personId, name: $scope.persons[personId].name, active: active });
            }
          }
          console.log($scope.personsPerComment);
          console.log('------------------------------');
        });
      }
    });
  }


  // public methods
  $scope.setPersonInCommentActive = function(commentId, personId, active) {
    var len, i;

    // list all comments (possibly) linked to this person (effectively, to her phone)
    len = $scope.personsPerComment.length;
    for (i = 0; i < len; i++) {
      var person = $scope.personsPerComment[i];
      if (person.id === personId) {
        $scope.personsPerComment[i].active = active;
        break;
      }
    }

    // set given person id to id_person field of this comment
    len = $scope.person.comments.length;
    for (i = 0; i < len; i++) {
      if ($scope.person.comments[i].id === commentId) {
        /* jshint camelcase: false */
        $scope.person.comments[i].id_person = personId;
        /* jshint camelcase: true */
      }
    }
  };

  $scope.getPersonInCommentActive = function() {
    var len = $scope.personsPerComment.length;
    for (var i = 0; i < len; i++) { // list all comments (possibly) linked to this person (effectively, to her phone)
      var person = $scope.personsPerComment[i];
      if (person.active) {
        return person;
      }
    }
    return $scope.personsPerComment[0]; // no active person found, return the first one
  };

  $scope.isAnyPersonInCommentActive = function(commentId) {
    // search current person for given comment id and check if any active person in comment is present
    var len = $scope.person.comments.length;
    for (var i = 0; i < len; i++) {
      if ($scope.person.comments[i].id === commentId) {
        /* jshint camelcase: false */
        return $scope.person.comments[i].id_person !== null;
        /* jshint camelcase: true */
      }
    }
    return false;
  };

  $scope.open = function(id) {
    $rootScope.openedId = id;
    $location.path('/details/' + id);
  };

  $scope.openInNewTab = function(url) {
    $window.open(url, '_blank');
  };

  $scope.scrollTo = function(personId) {
    $timeout(function() {
      $location.hash(personId);
      $anchorScroll(personId);
      $location.hash(null);
    });
  };

  $scope.firstSeen = function(personId) {
    var MILLISECONDS_PER_DAY = (1000 * 60 * 60 * 24);
    /* jshint camelcase: false */
    var timestampCreation = $scope.persons[personId].timestamp_creation * 1000;
    /* jshint camelcase: true */
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
    }
  };

  $scope.delSortCriteria = function(criterium) {
    if ($scope.sieves.sort.removeByName(criterium)) {
      $scope.personsList = sortObjectToList($scope.persons, $scope.sieves.sort);
    }
  };

  $scope.hasSortCriteria = function(criterium) {
    return ($scope.sieves.sort.hasName(criterium) >= 0);
  };

  $scope.resetSortCriteria = function() {
    $scope.sieves.sort = Sieves.defaults.sort; // TODO: writing $scope.sieves, does update Sieves.sieves ???
    $scope.personsList = sortObjectToList($scope.persons, $scope.sieves.sort);
  };

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
    }
  };

  $scope.setSortCriteriaDirection = function(criterium, direction) {
    var index = $scope.sieves.sort.hasName(criterium);
    if (index >= 0) {
      if ((direction === 'ascending') || (direction === 'descending')) {
        $scope.sieves.sort[index].direction = direction;
        $scope.personsList = sortObjectToList($scope.persons, $scope.sieves.sort);
      }
    }
  };


  $scope.isUniqPrimary = function(personId) {
    //console.log('@ isUniqPrimary('+personId+')');
    /* jshint camelcase: false */
    return (
      ($scope.persons[personId].uniq_prev === null) &&
      ($scope.persons[personId].uniq_next !== null)
    );
    // TODO: why other camel_case fields do not throw camel_case warning?
    /* jshint camelcase: true */
  };

  $scope.isUniqPrimaryOrSingle = function(personId) {
    //console.log('@ isUniqPrimary('+personId+')');
    /* jshint camelcase: false */
    return ($scope.persons[personId].uniq_prev === null);
    /* jshint camelcase: true */
  };

  $scope.isUniqLast = function(personId) {
    /* jshint camelcase: false */
    return (
      ($scope.persons[personId].uniq_prev !== null) &&
      ($scope.persons[personId].uniq_next === null)
    );
    /* jshint camelcase: true */
  };

  $scope.isUniqPrimaryShown = function(personId) {
    //console.log('@ isUniqPrimaryShown('+personId+')');
    /* jshint camelcase: false */
    return (
      $scope.isUniqPrimary(personId) &&
      $scope.persons[personId].uniq_opened
    );
    /* jshint camelcase: true */
  };

  $scope.uniqShow = function(personId) {
    //console.log('@ uniqPrimaryOpen('+personId+')');
    /* jshint camelcase: false */
    if ($scope.isUniqPrimary(personId)) {
      var opened = $scope.persons[personId].uniq_opened ? false : true;
      $scope.persons[personId].uniq_opened = opened;
      var id = personId;
      do {
        id = $scope.persons[id].uniq_next;
        if (id) {
          $scope.persons[id].uniq_opened = opened;
        }
      } while (id);
    } else {
      console.error('ASSERT FAILURE: Can\'t uniqShow('+personId+') on a secondary uniq!!!'); // TODO...
    }
    /* jshint camelcase: true */
  };

  $scope.isUniqShown = function(personId) {
    //console.log('@ isUniqShown('+personId+')');
    /* jshint camelcase: false */
    return (
      $scope.isUniqPrimaryOrSingle(personId) ||
      ($scope.persons[personId].uniq_opened === true)
    );
    /* jshint camelcase: true */
  };

  $scope.savePerson = function(person) {
    /* jshint camelcase: false */
    var personId = person.id_person;
    /* jshint camelcase: true */
  console.log('$scope.savePerson(', personId, person, $scope.userId, ')');
    var detailFields = [
      'name',
      'sex',
      'zone',
      'address',
      'description',
      'notes',
      'phone',
      'nationality',
      'age',
      'vote',
      'rating',
      'showcase',
      'thruthful',
      'new',
      'uniq_prev',
      'uniq_next',
    ];
    var personDetail = {};
    angular.forEach(person, function(value, key) {
//console.log('forEach:', key, value);
      if (detailFields.indexOf(key) !== -1) {
        this[key] = value;
      }
    }, personDetail);
console.log('personDetail:', personDetail);

    Persons.setPerson(personId, personDetail, $scope.userId).then(
      function(/*successMessage*/) {
        console.info('Person saved correctly');
      },
      function(errorMessage) {
        notify.error(errorMessage);
      }
    );
    //// reset the form once values have been consumed
    //$scope.form.name = '';
  };

  $scope.addPerson = function() { // TODO: "$scope.form.name" ???
    Persons.addPerson($scope.form.name).then(
      loadPersons,
      function(errorMessage) {
        console.warn(errorMessage);
      }
    );
    //// reset the form once values have been consumed
    //$scope.form.name = '';
  };

  $scope.removePerson = function(person) {
    Persons.removePerson(person.id).then(
      loadPersons,
      function(errorMessage) {
        console.warn(errorMessage);
      }
    );
  };
      
  $scope.tabSelect = function (tabName, data) {
    //console.log('Selecting tab ' + tabName);
    $scope.tabSelected = tabName;
    if (tabName === 'photos') {
      var number = data;
      $scope.person.photos[0].active = false;
      $scope.person.photos[number].active = true;
    }
  };

  $scope.photoGetOccurrences = function(id, url) {
    $scope.tabSelected = 'photosOccurrences';
    $scope.tabs.photosOccurrences.hidden = false;
    $scope.photosOccurrencesLoading = true;
    $scope.photosOccurrences = [];
    //notify.info('photoGetOccurrences(' + url + ')');
    Persons.photoGetOccurrences(id, url).then(
      function(response) {
        //console.info('+++ photoGetOccurrences response:', response);
        //console.info('+++ response.length:', response.length);
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
        //console.info('Persons.photoGetOccurrences - typeof response:', typeof response);
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
    console.log('It is thruthful!');
    $scope.tabSelected = 'photos';
    $scope.photosOccurrences = [];
    $scope.tabs.photosOccurrences.hidden = true;
  };

  $scope.photosOccurrencesFake = function() {
    console.log('It is fake!');
    $scope.tabSelected = 'photos';
    $scope.photosOccurrences = [];
    $scope.tabs.photosOccurrences.hidden = true;
  };

  $scope.photosOccurrencesUndecided = function() {
    console.log('Don\'t know...');
    $scope.tabSelected = 'photos';
    $scope.photosOccurrences = [];
    $scope.tabs.photosOccurrences.hidden = true;
  };

  $scope.changeCountry = function(code) {
    console.log('changeCountry(): ', code);
    $scope.person.nationality = code;
  };

  $scope.streetAddressToImageUrl = function(streetAddress) {
    return(
      'https://maps.googleapis.com/maps/api/streetview' + '?' + 
      'location=' + encodeURIComponent(streetAddress) + '&' +
      'size=' + '800x600'
    );
  };

  $scope.vote = function(vote) {
    if (vote === 0) { // noop
      return;
    }
    if (vote > 0) {
      $scope.person.vote = Math.min(cfg.person.vote.max, parseInt($scope.person.vote) + parseInt(vote));
    } else {
      $scope.person.vote = Math.max(cfg.person.vote.min, parseInt($scope.person.vote) + parseInt(vote));
    }
    //$scope.persons[$scope.personId].vote = $scope.person.vote; // TODO: DO WE NEED THIS INSTRUCTION?
    $scope.savePerson($scope.person);
  };

  $scope.rating = function(commentId, ratingDelta) {
    var commentNum = null;
    var len = $scope.personsPerComment.length;
    for (var i = 0; i < len; i++) { // list all comments (possibly) linked to this person (effectively, to her phone)
      if ($scope.person.comments[i].id === commentId) {
        commentNum = i;
        break;
      }
    }
    if (commentNum === null) { // comment with given id not found on this person, shouldn't happen
      console.error('can\'t find a comment with id ' + commentId + ' for this person');
      return;
    }

    /* jshint camelcase: false */
    if (ratingDelta === 0) { // noop
      return;
    }
    if ($scope.person.comments[commentNum].content_rating === null) {
      $scope.person.comments[commentNum].content_rating = 0;
    }
console.log(' === rating:', $scope.person.comments[commentNum].content_rating);
console.log(' === ratingDelta:', ratingDelta);
    if (ratingDelta > 0) {
      $scope.person.comments[commentNum].content_rating = Math.min(cfg.person.rating.max, parseInt($scope.person.comments[commentNum].content_rating) + parseInt(ratingDelta));
    } else {
      $scope.person.comments[commentNum].content_rating = Math.max(cfg.person.rating.min, parseInt($scope.person.comments[commentNum].content_rating) + parseInt(ratingDelta));
    }
console.log(' === rating AFTER:', $scope.person.comments[commentNum].content_rating);
    /* jshint camelcase: true */

    // calculate person's rating from all her comments ratings
    $scope.person.rating = $scope.personRatingFromCommentsRatings();

    // TODO: call saveComment() !!!
    
    // call savePerson() now to save person's comments
    $scope.savePerson($scope.person);
  };

  $scope.personRatingFromCommentsRatings = function() {
    // TODO: refine method to calculate person's rating from all her comments ratings
    var ratings = 0;
    var count = 0;
    var len = $scope.person.comments.length;
    for (var i = 0; i < len; i++) {
      /* jshint camelcase: false */
      if (parseInt($scope.person.comments[i].content_rating) > 0) {
        ratings += parseInt($scope.person.comments[i].content_rating);
        count++;
      }
      /* jshint camelcase: true */
    }
    if (count > 0) {
      return parseInt(ratings / count);
    } else {
      return null; // no ratings set for any comment for this person
    }
  };

  $scope.geocode = function(address, region) {
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({ 'address': address, 'region': region }, function(results, status) {
      if (status === google.maps.GeocoderStatus.OK) {
        //console.info('Address [' + $scope.person.streetAddress + '] found:', results[0].geometry.location);
        $scope.person.streetGeometryLocation = results[0].geometry.location;
        $scope.person.streetLocation = [ $scope.person.streetGeometryLocation.k, $scope.person.streetGeometryLocation.D ];
        console.log('person.streetLocation is now', $scope.person.streetLocation);
      } else {
        if (status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
          // got OVER_QUERY_LIMIT status code: retry in a moment...
          setTimeout(function() {
            $scope.geocode(address);
          }, 200);
        } else {
          console.error('Unable to find address [' + $scope.person.streetAddress + '], status: ', status); // set center of region is set if no address found
        }
      }
    });
  };

  // google maps initialization
  $rootScope.$on('mapsInitialized', function(event, maps) {
    $scope.map = maps[0];
    /* global $:false */
    $('#streetAddressIndicationsModalPopup').on('show.bs.modal', function() {
      $timeout(function() {
        google.maps.event.trigger($scope.map, 'resize');
        $scope.map.setCenter($scope.person.streetGeometryLocation);
      }, 200); // this timeout is needed due to animation delay
    });
  });

});