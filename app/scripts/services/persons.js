'use strict';

app.factory('Persons', function ($resource) {
  var restUrl = 'http://192.168.1.2/escrape/api';
  return $resource(restUrl + '/persons/:id',
    { /*id: '@id'*/ },
    {
      query:  { method: 'GET', isArray: false },
      update: { method: 'PUT' },
    }
  );
});


/*
  var Person = $resource(
    restUrl + '/persons/:id',
    { id: '@id' },
    { set: { method: 'PUT' } }
  );

  //var personsResource = $resource(s
  //  restUrl + '/persons'
  //);
  var Persons = $resource(
    restUrl + '/persons',
    { },
    {
      query: { method: 'GET' },
      cache: true,
    }
  );
*/

/*
app.factory('Persons', function ($resource) {
  return $resource(
    'http://192.168.1.2/escrape/server/persons/list',
    { 'method': 'getTask', 'q': '???*' }, // query parameters
    { 'query': { 'method': 'GET' }},
    { 'setVote': { 'method': 'POST' }}
  );
});
*/

/*
app.factory('Persons', function ($resource) {
  return $resource(
  	'http://192.168.1.2/escrape/server/persons/:id',
  	{ id: '@id' },
  	{
      update: {
        method: 'PUT' // this method issues a PUT request
      }
    }
  );

$scope.get = function(id) {
  return = Persons.get({ id: id });
};

$scope.set = function(person) {
  if ($scope.persons[person.id]) {
    Person.update({ id: person.id }, person);
  }

  $scope.addTodo = function() {
  todoFactory.save($scope.newTodoModel, backToList);
}
$scope.add = function(person) {
  $scope.post.$save().then(function(response) {
              $scope.posts.push(response)
            });
          }
          $scope.editing = false;
          $scope.post = new Post();
        }

        $scope.delete = function(post) {
          Post.delete(post)
          _.remove($scope.posts, post)
        }

/*
  return $resource(
    //'http://192.168.1.2/escrape/server/persons/:action, ',
    'http://192.168.1.2/escrape/server/persons/:id',
  	{}, // { action: 'list', param1: 'p1' },
  	{
      getList: { method: 'GET', params: { action: 'getList' } },
      setVote: { method: 'GET', params: { action: 'setVote', key: '@key', vote: '@vote' } },
      putVote: { method: 'PUT', params: { action: 'setVote', key: '@key', vote: '@vote' } },
/ *
  	  query:      { method: 'GET', params: { id: 'id1' } },
      post:       { method: 'POST' },
      update:     { method: 'PUT', params: { key: '@key' } },
      remove:     { method: 'DELETE'}
* /
    }
  );
* /
});
*/