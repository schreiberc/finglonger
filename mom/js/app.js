'use strict';
var user;
var app = angular.module('cms', ['ui.router'])

.run( function run ($http, $state, $rootScope, $stateParams) {
  user = sessionStorage.getItem('user');
})

.config(['$stateProvider', '$urlRouterProvider', function($stateProvider, $urlRouterProvider) {

  $urlRouterProvider.when('', '/resources');  

  $stateProvider
    .state('login', {
      url: '/login',
      views: {
        'main': {
          controller: 'LoginCtrl',
          templateUrl: 'components/user-services/login.tpl.html'
        }
      },
      data:{requiresLogin: false, pageTitle: 'Login' }
    })
    .state('forgot-password', {
      url: '/forgot-password',
      views: {
        'main': {
          controller: 'ForgotPasswordCtrl',
          templateUrl: 'components/user-services/forgot-password.tpl.html'
        }
      },
      data:{requiresLogin: false, pageTitle: 'Forgot Password' }
    })
    .state('reset-password', {
      url: '/reset-password/:token',
      views: {
        'main': {
          controller: 'PasswordResetCtrl',
          templateUrl: 'components/user-services/reset-password.tpl.html'
        }
      },
      data:{requiresLogin: false, pageTitle: 'Reset Password' }
    })
    
    .state('resources', {
      url: '/resources',
      views: {
        'main': {
          controller: 'ListCtrl',
          templateUrl: 'components/resources/list.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'List Resources' }
    })
    .state('create-resource', {
      url: '/create-resource/:name',
      views: {
        'main': {
          controller: 'CreateResourceCtrl',
          templateUrl: 'components/resources/single.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'Create Resource' }
    }) 
    .state('resourceid', {
      url: '/resource/:id',
      views: {
        'main': {
          controller: 'ResourceCtrl',
          templateUrl: 'components/resources/single.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'Single Resource' }
    }) 
    .state('users', {
        url: '/users',
        views: {
          'main': {
            controller: 'ListUsersCtrl',
            templateUrl: 'components/users/list.tpl.html'
          },
          'navbar': {
            controller: 'NavbarCtrl',
            templateUrl: 'components/navigation.tpl.html'
          }
        },
        params:{
        	deletedUser:null
        },
        data:{ requiresLogin: true, pageTitle: 'Users' }
      })  
    .state('userid', {
    	url: '/users/:id',
    	views:{
    		'main':{
    			controller:'UserCtrl',
    			templateUrl:'components/users/single.tpl.html'
    		},
    		'navbar':{
    			controller: 'NavbarCtrl',
    	        templateUrl: 'components/navigation.tpl.html'
    		}
    	},
    	data:{ requiresLogin: true, pageTitle: 'Single User' }
    	
    })
    .state('create-user', {
      url: '/create-user',
      views: {
        'main': {
          controller: 'CreateUserCtrl',
          templateUrl: 'components/users/single.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'Create User Type' }
    }) 
    .state('user-types', {
      url: '/user-types',
      views: {
        'main': {
          controller: 'ListUserTypesCtrl',
          templateUrl: 'components/user-types/list.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },params:{
      	deletedUserType:null
      },
      data:{ requiresLogin: true, pageTitle: 'List User Types' }
    })
    .state('create-user-type', {
      url: '/create-user-type',
      views: {
        'main': {
          controller: 'CreateUserTypeCtrl',
          templateUrl: 'components/user-types/single.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'Create User Type' }
    }) 
    .state('user-typeid', {
      url: '/user-types/:id',
      views: {
        'main': {
          controller: 'UserTypeCtrl',
          templateUrl: 'components/user-types/single.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'Single User Type' }
    })

    .state('emails', {
      url: '/emails',
      views: {
        'main': {
          controller: 'ListEmailsCtrl',
          templateUrl: 'components/emails/list.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'List Resources' }
    })
    .state('create-email', {
      url: '/create-email',
      views: {
        'main': {
          controller: 'CreateEmailCtrl',
          templateUrl: 'components/emails/single.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'Create Resource' }
    }) 
    .state('emailid', {
      url: '/emails/:id',
      views: {
        'main': {
          controller: 'EmailCtrl',
          templateUrl: 'components/emails/single.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'Single Resource' }
    })  
    .state('emailsettings', {
      url: '/email-settings',
      views: {
        'main': {
          controller: 'EmailSettingsCtrl',
          templateUrl: 'components/email-settings/page.tpl.html'
        },
        'navbar': {
          controller: 'NavbarCtrl',
          templateUrl: 'components/navigation.tpl.html'
        }
      },
      data:{ requiresLogin: true, pageTitle: 'Single Resource' }
    })       

}])

.controller( 'AppCtrl', function AppCtrl ( $rootScope, $scope, $state, $http, $location ) {

  var refreshTime = 100000; // every 10 minutes in milliseconds
  window.setInterval( function() {
    $http.get('../service/are-resources-changed').then(function(data){
      $scope.getCurrentVersion();
    });
  }, refreshTime );

  $scope.$on('$stateChangeStart', function(event, toState, toParams, fromState, fromParams) {
    user = sessionStorage.getItem('user');
          
    $http.get('../service/are-resources-changed').then(function(data){
      if(data.data.status != 'error'){
        if(data.data.data.are_resources_added == true || data.data.data.are_resources_removed == true){
          $http.get('../service/reinitialize').then(function(data){
            location.reload();
          });
        }else{
          $scope.getCurrentVersion();
        }
      }else{
        if(toState.data.requiresLogin){
          $scope.logout();
        }
      }
    });

    if(user == null && toState.data.requiresLogin){

      event.preventDefault();
            
      $state.go('login');      
    }
    else if(user != null && !toState.data.requiresLogin){

      event.preventDefault();
      $state.go('resources');
    }
  });
  $scope.getCurrentVersion = function(){
    $.ajax({
      url: '../service/get-version',
      type: 'GET',
      success: function(data){
          var dataParse = jQuery.parseJSON(data);
          if(dataParse.status == 'success'){
            $scope.getLatestVersion(dataParse.data);
          }else{
            $scope.errors = dataParse.message;
          }
          $scope.$apply();
        }
    });
  }
  $scope.getLatestVersion = function(current_version){
    $.ajax({
      url: 'http://finglonger.io/version.php',
      type: 'GET',      
      success: function(data){
          var latest_version = jQuery.parseJSON(data);

          $scope.compareVersions(current_version, latest_version)
          $scope.$apply();
        }
    });
  }
  $scope.softwareUpdateAvailable = false;
  $scope.databaseUpdateAvailable = false;
  $scope.compareVersions = function(current_version, latest_version){
    var softwareCompare = versionCompare(latest_version.software_version, current_version.software_version);
    var databaseCompare = versionCompare(latest_version.database_version, current_version.database_version);
    
    if(softwareCompare){
      $scope.softwareUpdateAvailable = true;
    }

    if(databaseCompare){
      $scope.databaseUpdateAvailable = true; 
    }

  }
  $scope.logout = function(){
    $.ajax({
      url: '../service/logout',
      type: 'POST',      
      success: function(data){
          var dataParse = jQuery.parseJSON(data);
          if(dataParse.status == 'success'){
            sessionStorage.removeItem('user');
            user = null;
            $state.go('login');         
          }else{
            $scope.errors = dataParse.message;
          }
          $scope.$apply();
        }
    });
    
  }
})

.controller('NavbarCtrl', ['$scope', '$state', function($scope, $state) {  
  $scope.currentState = $state.current.name;

}])
;
function versionCompare(v1, v2, options) {
    var lexicographical = options && options.lexicographical,
        zeroExtend = options && options.zeroExtend,
        v1parts = v1.split('.'),
        v2parts = v2.split('.');
    function isValidPart(x) {
        return (lexicographical ? /^\d+[A-Za-z]*$/ : /^\d+$/).test(x);
    }
    if (!v1parts.every(isValidPart) || !v2parts.every(isValidPart)) {
        return NaN;
    }
    if (zeroExtend) {
        while (v1parts.length < v2parts.length) v1parts.push("0");
        while (v2parts.length < v1parts.length) v2parts.push("0");
    }

    if (!lexicographical) {
        v1parts = v1parts.map(Number);
        v2parts = v2parts.map(Number);
    }

    for (var i = 0; i < v1parts.length; ++i) {
        if (v2parts.length == i) {
            return 1;
        }

        if (v1parts[i] == v2parts[i]) {
            continue;
        }
        else if (v1parts[i] > v2parts[i]) {
            return 1;
        }
        else {
            return -1;
        }
    }
    if (v1parts.length != v2parts.length) {
        return -1;
    }
    return 0;
}