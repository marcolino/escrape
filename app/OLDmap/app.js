var mapApp = angular.module('googleMapApp', ['google-maps'.ns(),'ui.bootstrap']);
mapApp.config(['GoogleMapApiProvider'.ns(), function (GoogleMapApi) {
  GoogleMapApi.configure({
    key: 'your Google Map api key',
    v: '3.17',
    libraries: ''
  });
}]);

mapApp.controller("MyCtrl",['$scope', 'GoogleMapApi'.ns(), function ($scope,GoogleMapApi) {

  var panorama;
  angular.extend($scope, {
    map: {
      center: {
        latitude: 35.681382,
        longitude: 139.766084
      },
      options: {
        maxZoom: 20,
        minZoom: 3
      },
      zoom: 15,
      control: {},
      events: {
        click: function (map, eventName, originalEventArgs) {
          var sv = new google.maps.StreetViewService();
          var event = originalEventArgs[0];
          sv.getPanoramaByLocation(event.latLng, 50, processSVData);
          addLatLng(map, eventName, originalEventArgs);
        }
      },
      marker: {
        id: 1,
        options: {}
      }
    }
  });

  function processSVData(data, status) {
    if (status == google.maps.StreetViewStatus.OK) {
      $scope.map.marker.latitude = data.location.latLng.lat();
      $scope.map.marker.longitude = data.location.latLng.lng();
      $scope.map.marker.id = 1;
      $scope.map.marker.options.labelContent = data.location.description;
      $scope.map.marker.options.labelAnchor = '0 30';
      panorama = new google.maps.StreetViewPanorama(document.getElementById('pano'));
      panorama.setPano(data.location.pano);
      panorama.setPov({
        heading: 270,
        pitch: 0
      });
      panorama.setVisible(true);
      $scope.$apply();
    } else {
      alert('Street View data not found for this location.');
    }
  }
}]);