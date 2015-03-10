    $scope.openAside = function(position) {
      $aside.open({
        templateUrl: 'views/sidemenu.html',
        placement: position,
        size: 'sm', // 'sm': small / 'lg': large
        backdrop: true, // close if user clicks outside this panel
        controller: function($scope, $modalInstance) {
          $scope.close = function(e) {
            $modalInstance.close();
            if (e) {
              e.stopPropagation();
            }
          };
          $scope.cancel = function(e) {
            $modalInstance.dismiss();
            if (e) {
              e.stopPropagation();
            }
            $scope.storeFilters(); // to save isopen status
          };
        }
      });
    };
