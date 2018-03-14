
app.controller('ListUsersCtrl', ['$scope', '$state', '$stateParams', 'contentServices', 'validationServices', function($scope, $state, $stateParams, contentServices) {  
  
	$scope.error = '';
	$scope.pageContent = null;
	$scope.formSubmitted = null;
	$scope.resources = [];
	$scope.filterCat = 0;
	$scope.postToDelete = null;
	$scope.loading = true;
	contentServices.getResource('fl-users').then(function(users){
	$scope.error = '';
	
    if(users.status == 'success'){
      $scope.users = users.data;
      $scope.loading = false;
    }else{
      $scope.error = users.message;
    }
      
    if($stateParams.deletedUser != null){
    	$scope.deletedUser = $stateParams.deletedUser;
    }
    
});
  
$scope.requestDeleteUser = function(userToDelete){
	
	//Popluate the modal with data about the user
	$scope.almostDeletedUser = null;
	$scope.userToDelete = userToDelete;
	$scope.userToDelete.display = userToDelete.first_name + ' ' + userToDelete.last_name + ' a.k.a. ' + userToDelete.user_name;
	  	
	$('#deleteModal').modal('show');	 
}

$scope.deleteUser = function(){
	  
	
	$.ajax({
		url: '../service/fl-users/'+$scope.userToDelete.user_id, 
		type: 'DELETE',
	   	success: function(data){
	    	  	    	  
	    	//Check for success of the delete
	    	$state.go($state.current, {'deletedUser':$scope.userToDelete.display}, {reload: true});
	   	}
  	});
}  

$scope.almostDeleteUser = function(){
	
	$scope.almostDeletedUser = $scope.userToDelete;	
}

}]);

app.controller('UserCtrl', ['$scope', '$state', '$stateParams','contentServices', 'validationServices', '$timeout', function($scope, $state, $stateParams, contentServices, validationServices, $timeout) {
	  
	  $scope.error = '';
	  $scope.updatePassword = false;
	  
	  $scope.tabOrder = new Array('user.user_name', 'user.first_name', 'user.last_name', 'user.email', 'user.user_type', 'user.password', 'save');
	 
	  //Edit User and Create user use the same view manage
	  //Flag to manage some elements
	  $scope.view = 'edit';
	  
	  contentServices.getResource('fl-users/'+$stateParams.id).then(function(user){
	    
		$scope.error = '';
	    $scope.formSuccess = false;

	    if(user.status == 'success'){
	      
	      $scope.user = user.data;  	      
	      contentServices.getResource('fl-user-types?children=false').then(function(usertypes){
			  
	    	  if(usertypes.status = 'success'){
				  $scope.usertypes = usertypes.data;
				   
				  for (var i=0; i < $scope.usertypes.length; i++) {
					  if ($scope.usertypes[i].user_type_id === $scope.user.user_type_id) {
						  $scope.user_type_name = $scope.usertypes[i].user_type;
						  i = $scope.usertypes.length;
					  }
				  }
				  
				 //Remove the loader
				$('#content-form').removeClass('loading');
				
				var lengthOfUserTypes = $scope.usertypes.length;
				var userTypeName = 'open';
				
				for(var i = 0; i < lengthOfUserTypes; i++){
					if($scope.usertypes[i].user_type_id == $scope.user.user_type_id){
						userTypeName = $scope.usertypes[i].user_type;
					}
				}
				
				setTimeout(function(){
				  	$('.ui.dropdown').dropdown('set selected', userTypeName);
				 }, 0);
				  	 
			  }else{
				  $scope.error = usertypes.message; 
			  }  
		  });
	      
	    }else{
	      $scope.error = user.message;
	    }
	  });
	  
	  $scope.save = function(){
		  
		  //Validate the form
		  var error = false;
		  var errorElements = new Array();
		
		  if(validationServices.validateString($scope.user.user_name) == false){
			
			  var domElement = $('[ng-model="user.user_name"]');
			  domElement.addClass('error');
		
			  //Add on blur event to remove error state when corrected
			  domElement.keyup(function(event){
				
				if(validationServices.validateString($scope.user.user_name) == false){
					$(this).addClass('error');
				}else{					
					$(this).removeClass('error');
				}
		
			});
		
			errorElements.push('user.user_name');
		  }
		  
		  if(validationServices.validateEmail($scope.user.email) == false){
				
			  var domElement = $('[ng-model="user.email"]');
			  domElement.addClass('error');
		
			  //Add on blur event to remove error state when corrected
			  domElement.keyup(function(event){
				
				if(validationServices.validateEmail($scope.user.email) == false){
					$(this).addClass('error');
				}else{
					$(this).removeClass('error');
				}
		
			});
		
			errorElements.push('user.email');
		  }
		  
		  //If we have an error then we are done.
		  var errorElementsLength = errorElements.length;
		  		  
		  if(errorElementsLength > 0){

				var topErrorElementTabIndex = 1000;
				var curErrorElementIndex = 0;

				//Set the focus to the top most element to error as set by the tab order
				for(var i = 0; i < errorElementsLength; i++){
					
					curErrorElementIndex = $scope.tabOrder.indexOf(errorElements[i]);
					
					if(curErrorElementIndex < topErrorElementTabIndex){	
						topErrorElementTabIndex = curErrorElementIndex;
					}
					
				}
				
				$('[ng-model="' + $scope.tabOrder[topErrorElementTabIndex] + '"]').focus();
				return;
		  }
		  
		  $scope.user.user_type_id = parseInt($('#user_type_id').val());
	  
		  //Check to see if we are goign to update the password
		  //Probably overkill but we'll use scope variable and the state of the input to determine if we are goign to update the password
		  if($scope.changePassword = true && !$('input#password').attr('disabled')){  
			  //Append the entered password to the user model
			  $scope.user.password = $('input#password').val();  
		  }
		  
		  var postData = angular.toJson($scope.user);
		  		  
		  $.ajax({
		    url: '../service/fl-users', type: 'POST', data: {data: postData}, dataType: 'html',
		      success: function(data){
		    	  
		    	var dataParse = jQuery.parseJSON(data);
		    	
		        if(dataParse.status == 'success'){	            	
		        	
		        	$scope.formSuccess = true;
		        	$('#content-form').removeClass('loading');                
		        	
		        }else{
		          $scope.errors = dataParse.message;
		        }
		        $scope.$apply();
		      }
		  });
	        
	  }
	  
}]);

app.controller('CreateUserCtrl', ['$scope', '$state', '$stateParams', 'contentServices', 'validationServices', '$timeout', function($scope, $state, $stateParams, contentServices, validationServices, $timeout) {

	  $scope.error = '';
	  $scope.pageContent = null;
	  
	  $scope.tabOrder = new Array('user.user_name', 'user.first_name', 'user.last_name', 'user.email', 'user.user_type', 'user.password', 'save');
		 
	  //Edit User and Create user use the same view manage
	  //Flag to manage some elements
	  $scope.view = 'create';
	  	  
	  $scope.user = {};	  
	  
	  contentServices.getResource('fl-user-types?children=false').then(function(usertypes){
		  if(usertypes.status = 'success'){
			  $scope.usertypes = usertypes.data;
			  
			  for (var i=0; i < $scope.usertypes.length; i++) {
				  if ($scope.usertypes[i].user_type_id === $scope.user.user_type_id) {
					  $scope.user_type_name = $scope.usertypes[i].user_type;
					  i = $scope.usertypes.length;
				  }
			  }
			  
			 //Remove the loader
			$('#content-form').removeClass('loading');
			  
			setTimeout(function(){
			  	$('.ui.dropdown').dropdown('set selected', 'open')
			 }, 0);
			  	 
		  }else{
			  $scope.error = usertypes.message; 
		  }  
	  });
	  
	  $scope.save = function(){
		  //Validate the form
		  var error = false;
		  var errorElements = new Array();
		
		  if(validationServices.validateString($scope.user.user_name) == false){
			
			  var domElement = $('[ng-model="user.user_name"]');
			  domElement.addClass('error');
		
			  //Add on blur event to remove error state when corrected
			  domElement.keyup(function(event){
				
				if(validationServices.validateString($scope.user.user_name) == false){
					$(this).addClass('error');
				}else{					
					$(this).removeClass('error');
				}
		
			});
		
			errorElements.push('user.user_name');
		  }
		  
		  if(validationServices.validateEmail($scope.user.email) == false){
				
			  var domElement = $('[ng-model="user.email"]');
			  domElement.addClass('error');
		
			  //Add on blur event to remove error state when corrected
			  domElement.keyup(function(event){
				
				if(validationServices.validateEmail($scope.user.email) == false){
					$(this).addClass('error');
				}else{
					$(this).removeClass('error');
				}
		
			});
		
			errorElements.push('user.email');
		  }
		  
		  if(validationServices.validateString($scope.user.password, 5) == false){
				
			  var domElement = $('[ng-model="user.password"]');
			  domElement.addClass('error');
		
			  //Add on blur event to remove error state when corrected
			  domElement.keyup(function(event){
				
				if(validationServices.validateString($scope.user.password, 5) == false){
					$(this).addClass('error');
				}else{					
					$(this).removeClass('error');
				}
		
			});
		
			errorElements.push('user.password');
		  }
		  
		  
		  //If we have an error then we are done.
		  var errorElementsLength = errorElements.length;
		  		  
		  if(errorElementsLength > 0){

				var topErrorElementTabIndex = 1000;
				var curErrorElementIndex = 0;

				//Set the focus to the top most element to error as set by the tab order
				for(var i = 0; i < errorElementsLength; i++){
					
					curErrorElementIndex = $scope.tabOrder.indexOf(errorElements[i]);
					
					if(curErrorElementIndex < topErrorElementTabIndex){	
						topErrorElementTabIndex = curErrorElementIndex;
					}
					
				}
				
				$('[ng-model="' + $scope.tabOrder[topErrorElementTabIndex] + '"]').focus();
				return;
		  }
		  
		 	$scope.user.user_type_id = parseInt($('#user_type_id').val());
	      	var postData = angular.toJson($scope.user);
	      	console.log(postData);
	      	$('#content-form').addClass('loading');
		    $.ajax({
		        url: '../service/fl-users', type: 'POST', data: {data: postData}, dataType: 'html',
		          success: function(data){	        	
		        	console.log(data);
		            var dataParse = jQuery.parseJSON(data);
		            if(dataParse.status == 'success'){
		              if(dataParse.data.user_id){
		                $('#content-form').removeClass('loading');
		                $state.go('userid', {id: dataParse.data.user_id});
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

