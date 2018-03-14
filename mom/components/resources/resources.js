
app.controller('ListCtrl', ['$scope', '$state', '$stateParams', 'contentServices', function($scope, $state, $stateParams, contentServices) {  
  
  $scope.error = '';
  $scope.pageContent = null;
  $scope.formSubmitted = null;
  $scope.resources = [];
  $scope.filterCat = 0;
  $scope.postToDelete = null;
  $scope.loading = true;
  contentServices.getResource('fl-resources').then(function(flresources){
    $scope.error = '';

    if(flresources.status == 'success'){

      contentServices.getResource('all-resources').then(function(allresources){
        if(allresources.status == 'success'){
          contentServices.getResource('system-resources').then(function(systemresources){
            if(systemresources.status == 'success'){
              $scope.organizeResources(flresources.data, allresources.data, systemresources.data);
            }else{
              $scope.error = systemresources.message;    
            }

          }); 
        }else{
          $scope.error = allresources.message;    
        }
      });   
    }else{
      $scope.error = flresources.message;
    }
  });

  $scope.organizeResources = function(flresources, allresources, systemresources){
    
    for (var i = allresources.length - 1; i >= 0; i--) {        
      var add_resource = true;
      var add_to_system = false;
      var this_resource = null;
      var alreadyAdded = false;
      for (var x = flresources.length - 1; x >= 0; x--) {       
        if(flresources[x].resource_name == allresources[i]){
          add_resource = false;
          this_resource = flresources[x];
          alreadyAdded = true;          

        }
      }
      if(!alreadyAdded){
        for (var x = systemresources.length - 1; x >= 0; x--) {       
          var res = systemresources[x].replace(/_/g, '-');
          
          if(res == allresources[i]){
            add_to_system = true;
            
          }
        }
        if(add_to_system == true){
          this_resource = {
            resource_name: allresources[i],
            resource_category_id: 1
          }
        }
        else if(add_resource == true){
          this_resource = {
            resource_name: allresources[i],
            resource_category_id: 0
          }
        }
      }
      $scope.resources.push(this_resource);
    }
    $scope.loading = false;

  }

}]);

app.controller('ResourceCtrl', ['$scope', '$state', '$stateParams', 'contentServices', '$timeout', function($scope, $state, $stateParams, contentServices, $timeout) {
  
  $scope.error = '';
  $scope.pageContent = null;

  contentServices.getResource('fl-user-types').then(function(data){
    $scope.usertypes = data.data;    
  }); 

  $scope.myFilter = function (item, item2) { 
    
    return item === item2; 
  };

  contentServices.getResource('fl-resources/'+$stateParams.id).then(function(data){
    
    $scope.error = '';
    $scope.formSuccess = false;

    if(data.status == 'success'){
      $('#content-form').removeClass('loading');
      $scope.resource = data.data;  

    }else{
      $scope.error = data.message;
    }

  });
  $scope.loadResource = function(){
      $.ajax({url: '../service/'+$scope.resource.resource_name,type: 'GET',
          success: function(data){
            var jsonData = JSON.parse(data);
            $scope.response = (JSON.stringify(jsonData, null, 4));
            $scope.$apply();
          }
      });
    
  }
  $scope.save = function(){

      var postData = angular.toJson($scope.resource);      

      $.ajax({
        url: '../service/fl-resources', type: 'POST', data: {data: postData}, dataType: 'html',
          success: function(data){

            var dataParse = jQuery.parseJSON(data);
            if(dataParse.status == 'success'){
              if(dataParse.data.resource_id){
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

app.controller('CreateResourceCtrl', ['$scope', '$state', '$stateParams', 'contentServices', '$timeout', function($scope, $state, $stateParams, contentServices, $timeout) {

  $scope.error = '';
  $scope.pageContent = null;

  $scope.resource = {
    resource_name: $stateParams.name,
    fl_user_type_resource_access: []
  }

  contentServices.getResource('fl-user-types').then(function(data){
    $scope.usertypes = data.data;    
    for (var i = $scope.usertypes.length - 1; i >= 0; i--) {       
      if($scope.usertypes[i].user_type != 'mom'){
        $scope.resource.fl_user_type_resource_access.push({
          user_type_id: $scope.usertypes[i].user_type_id,
          get_allowed: 'false',
          post_allowed: 'false',
          delete_allowed: 'false'
        });
      }
    }
  });
  
  $('#content-form').removeClass('loading');

  $scope.save = function(){

      var postData = angular.toJson($scope.resource);

      $('#content-form').addClass('loading');

      $.ajax({
        url: '../service/fl-resources', type: 'POST', data: {data: postData}, dataType: 'html',
          success: function(data){

            var dataParse = jQuery.parseJSON(data);
            if(dataParse.status == 'success'){
              if(dataParse.data.resource_id){
                $scope.formSuccess = true;
                $('#content-form').removeClass('loading');
                $state.go('resourceid', {id: dataParse.data.resource_id});
              }
            }else{
              $scope.errors = dataParse.message;
            }
            $scope.$apply();
          }
      }); 
  }

}]);
