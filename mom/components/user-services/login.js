app.controller('LoginCtrl', ['$scope', '$state', function($scope, $state) {  
  $scope.username = '';
  $scope.password = '';
  $scope.errors = '';
  $.ajax( {
        method: "GET", url: "../service/get-setup-status?setup=true"
  }).done(function(data) {    
    data = jQuery.parseJSON(data);
        
    if(data.status !== null && data.status == 'success'){
    	
	    if(!data.data.config || !data.data.database){
	    		//window.location = '../setup';
	    }
    }else if(data.status !== null && data.status == 'error'){
    	
    	if(data.error !== null){
    		$scope.errors = data.message;
    	}else{
    		$scope.errors = 'An unknown error has occured.';
    	}
    	
    }else{
    	$scope.errors = 'An unknown error has occured.';
    }
    
  });
  
  $scope.login = function(){

  var user = {
    user_name: $scope.username,
    password: $scope.password
  };
  var postData = angular.toJson(user);
    $.ajax({
      url: '../service/login',
      type: 'POST',
      data: {data: postData},
      dataType: 'html',
        success: function(data){
          var dataParse = jQuery.parseJSON(data);
          if(dataParse.status == 'success'){
            if(dataParse.data.user_type_id === 2){
              user = sessionStorage.setItem('user', '$scope.username');
              $state.go('resources')               
            }else{
              $scope.errors = "You need MOM level access to login.";  
            }
          }else{ 
        	         	  
        	if(dataParse.message.indexOf('Database connection failed') > -1){
        		$scope.errors = "Whoops!  Can't connect to the database.";
        	}else{

        		$scope.errors = 'Your login info is bad and you should feel bad!';
          	}
          }
          $scope.$apply();
        }
    });    
  }
}])

.controller( 'ForgotPasswordCtrl', function PasswordResetRequestController( $rootScope, $scope, $state ) {
  $scope.errors = '';
  $scope.message = '';
  $scope.formSuccess = false;
  $scope.submit = function(){
    
    var user = {
      email: $scope.email,
      reset_url: window.location.hostname+window.location.pathname+'#!/reset-password'
    };
    var postData = angular.toJson(user);
       
    $.ajax({
      url: '../service/request-change-password',
      type: 'POST',
      data: {data: postData},
      dataType: 'html',
        success: function(data){
        	console.log(data);
        	
          var dataParse = jQuery.parseJSON(data);
          if(dataParse.status == 'success'){
            $scope.formSuccess = true;
            $scope.message = dataParse.message;              
          }else{
            $scope.errors = dataParse.message;
          }
          $scope.$apply();
        }
    });

  }
})
.controller( 'PasswordResetCtrl', function PasswordResetController( $rootScope, $scope, $state, $stateParams ) {
  $scope.errors = '';
  $scope.submit = function(){
    
    var request = {
      password: $scope.confirmpassword,
      token: $stateParams.token
    };
    var postData = angular.toJson(request);

    $.ajax({
      url: '../service/change-password',
      type: 'POST',
      data: {data: postData},
      dataType: 'html',
        success: function(data){
        console.log(data);         
          var dataParse = jQuery.parseJSON(data);
          if(dataParse.status == 'success'){
            $state.go('login');             
          }else{
            $scope.errors = dataParse.message;
          }
          $scope.$apply();
        }
    });

  }
})