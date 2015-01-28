'use strict';

app.controller('PersonsCtrl', function($scope, $rootScope, Persons) {

  $scope.username = $rootScope.globals.currentUser.username;
  
	$scope.persons = Persons.query(function(data) {
    console.info(data);
    //return data;
	});
	$scope.personNew = {};
  $scope.votes = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];

	//$scope.save = function(person) {
	//  Person.update({id: person.id}, person);
	//};

/*
	$scope.add = function() {
  	var personNew = new Person($scope.personNew);
	  personNew.$save(function() {
			$scope.persons.push(personNew);
		});
	};
*/
	$scope.setVote = function(idx, vote) {
/*
		$scope.persons[idx].vote = vote;
console.log('setVote()', $scope.persons[idx].vote);
//    Persons.update({id: id}, $scope.persons[id]);
    Persons.update({ id: idx }, { Content: $scope.persons[idx] });
*/
    $scope.persons[idx].vote = vote;
//console.log('setVote()', $scope.persons[idx].vote);
    var person = Persons.get({ id: idx });
    //person.$update({ id: idx }, function() { return {vote: vote}; });
    person.$update({ 'vote': 3 });
	};

});

/*
app.controller('PersonsCtrl', function($scope, Persons) {
  // Instantiate an object to store your scope data in (Best Practices)
  $scope.persons = {};
  $scope.votes = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ];
 
  Persons.getList(function(response) {
    // assign the response *inside* the callback
    $scope.personsDefinition = response.personsDefinition;
    $scope.personsList = response.personsList;

    // for each person set full url "photoUrl" field
    for (var person in $scope.personsList) {
	    var url = $scope.personsDefinition[$scope.personsList[person].site].url;
	    var photo = $scope.personsList[person].photo;
    	$scope.personsList[person].urlPhoto = url + '/' + photo;
        $scope.personsList[person].vote *= 10;
     	//console.debug('current vote is', $scope.personsList[person].vote);
    }
  });

  $scope.setVote = function() {
  	console.debug('person key is', this.person.key);
  	console.debug('selected vote is', this.person.vote);

    //console.debug('setting vote', this.person.vote / 10);
    Persons.setVote({ key: this.person.key, vote: this.person.vote / 10},
    function() {},
    function(error) {
      console.error('error:', error); // TODO: ...
    }).$promise.then(function(data) {
      if (data.result) {
    	console.log('result is true');
      } else {
    	console.log('result is error:', data.error);
      }
   });
  };

  $scope.putVote = function() {
  	console.debug('person key is', this.person.key);
  	console.debug('selected vote is', this.person.vote);

    //console.debug('setting vote', this.person.vote / 10);
    Persons.putVote({ key: this.person.key, vote: this.person.vote / 10},
    function() {},
    function(error) {
      console.error('error!!!:', error); // TODO: ...
    }).$promise.then(function(data) {
      if (data.result) {
    	console.log('result is true');
      } else {
    	console.log('result is error:', data.error);
      }
   });
  };
});
*/