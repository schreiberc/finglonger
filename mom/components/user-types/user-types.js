
app.controller('ListUserTypesCtrl', ['$scope', '$state', '$stateParams', 'contentServices', function($scope, $state, $stateParams, contentServices) {  
  
  $scope.error = '';

  $scope.formSubmitted = null;
  $scope.postToDelete = null;
  $scope.loading = true;
  
  contentServices.getResource('fl-user-types').then(function(usertypes){
    $scope.error = '';

    if(usertypes.status == 'success'){
      $scope.usertypes = usertypes.data;
      $scope.loading = false;

    }else{
      $scope.error = usertypes.message;
    }
        
    if($stateParams.deletedUserType != null){
    	$scope.deletedUserType = $stateParams.deletedUserType;
    }
    
  });

  $scope.requestDeleteUserType = function(userTypeToDelete){
    	  
	$scope.almostDeletedUserType = null;
	$scope.userTypeToDelete = userTypeToDelete;
	$scope.userTypeToDelete.display = userTypeToDelete.user_type;
		  	    
    $('#deleteModal').modal('show');
    $scope.userTypeToDelete = userTypeToDelete;
  }
  
  $scope.deleteUserType = function(){
	 
    $.ajax({
      url: '../service/fl-user-types/'+$scope.userTypeToDelete.user_type_id, type: 'DELETE',
      
      success: function(data){
    	  
    	  var dataParse = jQuery.parseJSON(data);
    	  
    	  if(dataParse .status != null && dataParse .status == 'success'){
    	  	$state.go($state.current, {'deletedUserType':$scope.userTypeToDelete.display}, {reload: true});
      	  }else if(dataParse .status != null && dataParse .status == 'error'){
      		$scope.error = dataParse.message;
      	  }else{
      		$scope.error = 'Sorry, an unknown error has occured.';
      	  }
    	  
    	  $scope.$apply();
      }
    });
  }  

}]);

app.controller('UserTypeCtrl', ['$scope', '$state', '$stateParams', 'contentServices', '$timeout', function($scope, $state, $stateParams, contentServices, $timeout) {
  
  $scope.error = '';
  
  $scope.view = 'edit';
  
  contentServices.getResource('fl-user-types/'+$stateParams.id).then(function(usertype){
    
    $scope.error = '';
    $scope.formSuccess = false;

    if(usertype.status == 'success'){
      $('#content-form').removeClass('loading');
      $scope.usertype = usertype.data;  

    }else{
      $scope.error = usertype.message;
    }

  });

  $scope.save = function(){

      var postData = angular.toJson($scope.usertype);

      $('#content-form').addClass('loading');

      $.ajax({
        url: '../service/fl-user-types', type: 'POST', data: {data: postData}, dataType: 'html',
          success: function(data){
        	  
            var dataParse = jQuery.parseJSON(data);
            if(dataParse.status == 'success'){
              if(dataParse.data.user_type_id){
                $scope.formSuccess = true;
                $('#content-form').removeClass('loading');                
              }
            }else{
              $scope.errors = dataParse.message;
            }
            $scope.$apply();
          }
      }); 
  }
}]);

app.controller('CreateUserTypeCtrl', ['$scope', '$state', '$stateParams', 'contentServices', '$timeout', function($scope, $state, $stateParams, contentServices, $timeout) {

  $scope.error = '';
  $scope.pageContent = null;

  $scope.view = 'create';
  
  $scope.usertype = {
    user_type: ''    
  }

  $('#content-form').removeClass('loading');

  $scope.save = function(){

      var postData = angular.toJson($scope.usertype);
      
      $('#content-form').addClass('loading');

      $.ajax({
        url: '../service/fl-user-types', type: 'POST', data: {data: postData}, dataType: 'html',
          success: function(data){
        	          	  
            var dataParse = jQuery.parseJSON(data);
            if(dataParse.status == 'success'){
              if(dataParse.data.user_type_id){
                $scope.formSuccess = true;
                $('#content-form').removeClass('loading');
                $state.go('user-typeid', {id: dataParse.data.user_type_id});
              }
            }else{
            	$('#content-form').removeClass('loading');
            	$scope.errors = dataParse.message;
            }
            $scope.$apply();
            
          }
      }); 
  }

}]);
