
app.controller('ListEmailsCtrl', ['$scope', '$state', '$stateParams', 'contentServices', function($scope, $state, $stateParams, contentServices) {  
  
  $scope.error = '';

  $scope.formSubmitted = null;
  $scope.postToDelete = null;
  $scope.loading = true;

  contentServices.getResource('fl-emails').then(function(emails){
    $scope.error = '';

    if(emails.status == 'success'){
      $scope.emails = emails.data;
      $scope.loading = false;

    }else{
      $scope.error = emails.message;
    }
  });

  $scope.requestRemoveItem = function(post){
    console.log('test');
    $('#deleteModal').modal('show');
    $scope.postToDelete = post;
  }
  $scope.removeItem = function(){
    $.ajax({
      url: '../service/fl-emails/'+$scope.postToDelete.user_type_id, type: 'DELETE',
        success: function(data){
          $state.go($state.current, {}, {reload: true});
        }
    });
  }  

}]);

app.controller('EmailCtrl', ['$scope', '$state', '$stateParams', 'contentServices', '$timeout', function($scope, $state, $stateParams, contentServices, $timeout) {
  
  $scope.error = '';
  
  contentServices.getResource('fl-emails/'+$stateParams.id).then(function(emails){
    
    $scope.error = '';
    $scope.formSuccess = false;

    if(emails.status == 'success'){
      $('#content-form').removeClass('loading');
      $scope.email = emails.data;  

    }else{
      $scope.error = emails.message;
    }

  });

  

  $scope.save = function(){

      var postData = angular.toJson($scope.email);

      $('#content-form').addClass('loading');

      $.ajax({
        url: '../service/fl-emails', type: 'POST', data: {data: postData}, dataType: 'html',
          success: function(data){

            var dataParse = jQuery.parseJSON(data);
            if(dataParse.status == 'success'){
              if(dataParse.data.email_id){
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

app.controller('CreateEmailCtrl', ['$scope', '$state', '$stateParams', 'contentServices', '$timeout', function($scope, $state, $stateParams, contentServices, $timeout) {

  $scope.error = '';
  $scope.pageContent = null;

  $scope.email = {
    user_type: ''    
  }

  
  $('#content-form').removeClass('loading');

  $scope.save = function(){

      var postData = angular.toJson($scope.email);
      
      $('#content-form').addClass('loading');

      $.ajax({
        url: '../service/fl-emails', type: 'POST', data: {data: postData}, dataType: 'html',
          success: function(data){
            console.log(data);
            var dataParse = jQuery.parseJSON(data);
            if(dataParse.status == 'success'){
              if(dataParse.data.email_id){
                $scope.formSuccess = true;
                $('#content-form').removeClass('loading');
                $state.go('emailsid', {id: dataParse.data.email_id});
              }
            }else{
              $scope.errors = dataParse.message;
            }
            $scope.$apply();
          }
      }); 
  }

}]);
