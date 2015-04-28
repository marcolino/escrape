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
    Persons.getPersons(Sieves.sieves, $scope.userId).then(function(persons) {
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
    return list;
  }

  function searchArrayByIdPerson(array, personId) {
    var len = array.length;
    for (var i = 0; i < len; i++) {
      if (array[i].id_person === personId) {
        return i;
      }
    }
    return null;
  }

  if ($scope.personId) { // load single person
    Persons.getPerson($scope.personId, $scope.userId).then(function(person) {
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

      $scope.personsPerComment = {};
      //$scope.personsPerCommentAnyActive = false; // TODO: ...
      if (!$scope.person.phone) { // empty phone, do not load comments
        $scope.person.comments = [];
      } else { // active phone, do load comments
        Comments.getCommentsByPhone($scope.person.phone, $scope.userId).then(function(comments) {
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
console.log(' persons:', $scope.persons);

          var len = $scope.person.comments.length;
console.log('length of comments for this person:', len);
          var commentId;
          for (var i = 0; i < len; i++) { // loop through all comments (possibly) linked to this person (effectively, to her phone)
            commentId = $scope.person.comments[i].id_comment;
            $scope.personsPerComment[commentId] = {};
console.log('id of current ('+i+') comment:', commentId);
            for (var personId in $scope.persons) { // loop through all persons
//console.log('id of current person:', personId);
//console.log('$scope.persons[personId].phone:', $scope.persons[personId].phone);
              if ($scope.person.phone === $scope.persons[personId].phone) { // phone matches
console.log(' +++ id of person with a matching phone:', personId);
console.log('     name of person with a matching phone:', $scope.persons[personId].name);
                var active = false;
                if ($scope.person.comments[i].id_person === personId) { // this comment has a specific id_person set: set that person as active
                  //if ($scope.person.comments[i].id_person) { // this comment has a specific id_person set: set that person as active
                  active = true;
                  //}
                }
              //$scope.personsPerComment.push({ id: personId, name: $scope.persons[personId].name, active: active });
                $scope.personsPerComment[commentId][personId] = { name: $scope.persons[personId].name, active: active };
              }
            }
          }
          console.log($scope.personsPerComment);
          console.log(typeof $scope.personsPerComment);
          console.log(Object.keys($scope.personsPerComment).length);
          console.log('------------------------------');
          console.log($scope.personsPerComment[commentId]);
          console.log(typeof $scope.personsPerComment[commentId]);
          console.log(Object.keys($scope.personsPerComment[commentId]).length);
          console.log('------------------------------');
        });
      }
    });
  }


  // public methods
  $scope.flipPersonInCommentActive = function(commentId, personId) {
    var len, i; //, active, person;
console.log('flipPersonInCommentActive() - commentId:', commentId);
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
console.log('flipPersonInCommentActive() - comment before save:', comment);
    $scope.savePersonComment(comment);
  };

  $scope.getPersonInCommentActive = function(commentId) {
    var personId;
    for (personId in $scope.personsPerComment[commentId]) {
      if ($scope.personsPerComment[commentId][personId].active) { // one person is active, return her
        return $scope.personsPerComment[commentId][personId];
      }
    }
    // no person is active, return the first (...) id in object
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
        //console.info('isAnyPersonInCommentActive - $scope.person.comments[i].id_person :', i, $scope.person.comments[i].id_person);
        return !isNaN(parseInt($scope.person.comments[i].id_person));
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
    var timestampCreation = $scope.persons[personId].timestamp_creation * 1000;
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
      do {
        id = $scope.persons[id].uniq_next;
        if (id) {
          $scope.persons[id].uniq_opened = opened;
        }
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
    var personId = person.id_person;
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
      if (detailFields.indexOf(key) !== -1) {
        this[key] = value;
      }
    }, personDetail);
//console.log('personDetail:', personDetail);

    Persons.setPerson(personId, [], personDetail, $scope.userId).then(
      function(/*successMessage*/) {
        console.info('Person saved correctly');
      },
      function(errorMessage) {
        notify.error(errorMessage);
      }
    );
  };

  $scope.savePersonComment = function(comment) {
    var detailFields = [
      'id_comment',
      'id_user',
      'id_person',
      'content_rating',
    ];
    var commentDetail = {};
    angular.forEach(comment, function(value, key) {
//console.log('forEach:', key, value);
      if (detailFields.indexOf(key) !== -1) {
        this[key] = value;
      }
    }, commentDetail);
console.log('commentDetail:', commentDetail);

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
    $scope.tabSelected = tabName;
    if (tabName === 'photos') {
      var number = data;
      $scope.person.photos[0].active = false;
      $scope.person.photos[number].active = true;
    }
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

console.log('-------------- person.rating => ', $scope.person.rating);
    $scope.savePerson($scope.person); // for new rating only
    $scope.savePersonComment(comment); // for new comment
  };

  $scope.personRatingFromCommentsRatings = function() {
    // TODO: refine method to calculate person's rating from all her comments ratings
    var ratings = 0;
    var count = 0;
    var len = $scope.person.comments.length;
console.log(' +++++++++++++ person.id_person => ', $scope.person.id_person);
    for (var i = 0; i < len; i++) {
console.log(i + ' +++++++++++++ comment => ', $scope.person.comments[i]);
      if (!isNaN(parseInt($scope.person.comments[i].content_rating))) {
        if ($scope.person.comments[i].id_person === $scope.person.id_person) {
console.log(i + ' +++++++++++++! content_rating => ', parseInt($scope.person.comments[i].content_rating));
          ratings += parseInt($scope.person.comments[i].content_rating);
          count++;
console.log(i + ' +++++++++++++! count => ', count);
        }
      }
    }
    if (count > 0) {
console.log(parseInt(count) + ' +++++++++++++ person.rating => ', parseInt(ratings / count));
      return parseInt(ratings / count);
    } else {
console.log(parseInt(count) + ' +++++++++++++ person.rating => ', null);
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