
app.controller('EmailSettingsCtrl', ['$scope', '$state', '$stateParams', 'contentServices', '$timeout', function($scope, $state, $stateParams, contentServices, $timeout) {
  
  $scope.error = '';
  
  contentServices.getResource('fl-settings/1').then(function(settings){
    
    $scope.error = '';
    $scope.settingsFormSuccess = false;

    if(settings.status == 'success'){
      $('#settings-content-form').removeClass('loading');
      $scope.settings = settings.data;  

    }else{
      $scope.error = settings.message;
    }

  });  

  contentServices.getResource('fl-integrations/1').then(function(mailgun){
    
    $scope.error = '';
    $scope.mailgunFormSuccess = false;

    if(mailgun.status == 'success'){
      $('#mailgun-content-form').removeClass('loading');
      $scope.mailgun = mailgun.data;

    }else{
      $scope.error = mailgun.message;
    }

  });

  // contentServices.getResource('fl-settings/3').then(function(session_duration){
    
  //   $scope.error = '';
  //   $scope.settingsFormSuccess = false;

  //   if(session_duration.status == 'success'){
  //     $('#session_duration-form').removeClass('loading');
  //     $scope.session_duration = session_duration.data;  

  //   }else{
  //     $scope.error = session_duration.message;
  //   }

  // });

  $scope.saveSettings = function(){

      var postData = angular.toJson($scope.settings);

      $('#settings-content-form').addClass('loading');

      $.ajax({
        url: '../service/fl-settings/1', type: 'POST', data: {data: postData}, dataType: 'html',
          success: function(data){
            console.log(data);
            var dataParse = jQuery.parseJSON(data);
            if(dataParse.status == 'success'){
              if(dataParse.data.setting_id){
                $scope.settingsFormSuccess = true;
                $('#settings-content-form').removeClass('loading');
              }
            }else{
              $scope.errors = dataParse.message;
            }
            $scope.$apply();
          }
      }); 
  }
  
  $scope.saveMailgun = function(){

      var postData = angular.toJson($scope.mailgun);

      $('#mailgun-content-form').addClass('loading');

      $.ajax({
        url: '../service/fl-integrations/1', type: 'POST', data: {data: postData}, dataType: 'html',
          success: function(data){
            console.log(data);
            var dataParse = jQuery.parseJSON(data);
            if(dataParse.status == 'success'){
              if(dataParse.data.integration_id){
                $scope.mailgunFormSuccess = true;
                $('#mailgun-content-form').removeClass('loading');
              }
            }else{
              $scope.errors = dataParse.message;
            }
            $scope.$apply();
          }
      }); 
  }
  // $scope.saveSession_duration = function(){
  //   var postData = angular.toJson($scope.session_duration);

  //     $('#session_duration-form').addClass('loading');

  //     $.ajax({
  //       url: '../service/fl-settings/3', type: 'POST', data: {data: postData}, dataType: 'html',
  //         success: function(data){
  //           console.log(data);
  //           var dataParse = jQuery.parseJSON(data);
  //           if(dataParse.status == 'success'){
  //             if(dataParse.data.setting_id){
  //               $scope.session_durationFormSuccess = true;
  //               $('#session_duration-form').removeClass('loading');
  //             }
  //           }else{
  //             $scope.errors = dataParse.message;
  //           }
  //           $scope.$apply();
  //         }
  //     }); 
  // }
}]);
