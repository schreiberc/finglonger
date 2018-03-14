  var formevent;
  var param = function(obj) {
    var query = '', name, value, fullSubName, subName, subValue, innerObj, i;
      
    for(name in obj) {
      value = obj[name];
        
      if(value instanceof Array) {
        for(i=0; i<value.length; ++i) {
          subValue = value[i];
          fullSubName = name + '[' + i + ']';
          innerObj = {};
          innerObj[fullSubName] = subValue;
          query += param(innerObj) + '&';
        }
      }
      else if(value instanceof Object) {
        for(subName in value) {
          subValue = value[subName];
          fullSubName = name + '[' + subName + ']';
          innerObj = {};
          innerObj[fullSubName] = subValue;
          query += param(innerObj) + '&';
        }
      }
      else if(value !== undefined && value !== null)
        query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
    }
      
    return query.length ? query.substr(0, query.length - 1) : query;
  };

$( document ).ready(function() {
	
	$.ajax( {
	    method: "GET", url: "../service/get-setup-status?setup=true",
	    success:function(response){
	    		console.log(response);
			  
			  if(response.hasOwnProperty('status') == false){
			
				  //$('#error #problem').html('An unknown error has occured. I wish we knew more.');
			  
			  }else if(response.status = 'success'){
				  
				  var data = response.data;
				  
				  if(data.config && data.database){
				      window.location = '../mom';
				    }else if(data.config){
				      $('#install').hide();
				      $('#error #problem').html('Looks like you already have a config file.<br/>Please reset it if you wish to run setup again.<br />1) Navigate to "service/finglonger/setup" <br /> 2) Replace the contents of db_config.php with default_config.php.');
				      $('#error').show();
				    }else if(data.database){
				      $('#error #problem').html('Looks like your database is already setup.');
				      $('#install').hide();
				    }
				  
			  }else{
				  $('#error #problem').html('An unknown error has occured. I wish we knew more.');
			      $('#error').show();
			  }
	    	
	    }
	  })
	
  /*$.ajax( {
    method: "GET", url: "../service/get-setup-status?setup=true"
  }).done(function(data) {    
    
    data = jQuery.parseJSON(data);
    if(data.data.config && data.data.database){
      window.location = '../mom';
    }else if(data.data.config){
      $('#install').hide();
      $('#error #problem').html('Looks like you already have a config file.<br/>Please reset it if you wish to run setup again.<br />1) Navigate to "service/finglonger/setup" <br /> 2) Replace the contents of db_config.php with default_config.php.');
      $('#error').show();
    }else if(data.data.database){
      $('#error #problem').html('Looks like your database is already setup.');
      $('#install').hide();
    }
   
  });*/
});

$('#restart-setup').click(function(){
  location.reload();
});

$('#install').on('submit', function(e){
  // validation code here
  formevent = e;
  e.preventDefault();

  var data = {hostname: e.currentTarget[3].value, db_name: e.currentTarget[0].value, user_name: e.currentTarget[1].value, password: e.currentTarget[2].value};
  
  $('#install').fadeOut();
  $('#installing').fadeIn(600, function(){
    setDbCreds(data);
  });

});

var setDbCreds = function(data){
  //var data = {hostname: hostname, db_name: db_name, user_name: user_name, password: password};

  var postData = JSON.stringify(data);
  $.ajax( {
    method: "POST",
    data: {data:postData},
    dataType: 'html',
    url: "../service/set-db-creds?setup=true"
  } )
  .done(function(data) {   
	  
	  console.log(data);
	  
    data = jQuery.parseJSON(data);
    if(data.status == "success"){
      $('#install-details').text('set-db-creds');
      setTimeout(function(){
        canInsertSystemTables();
      },150);
      $( "#loaded" ).stop().animate({
        width: '20%'      
      }, 500, function() {
        
      });
    }else{      
      $('#error').show();
      $('#problem').text(data.message);
    }    
      
  })
  .fail(function(data) {

    console.log(data);
  });

}

var canInsertSystemTables = function(){
  $.ajax( "../service/can-insert-system-tables?setup=true" )
  .done(function(data) {
	  console.log(data);
    data = jQuery.parseJSON(data);
    if(data.status == "success"){
      $('#install-details').text('can-insert-system-tables');
      setTimeout(function(){
        setupSystemTables();
      },150);
      $( "#loaded" ).stop().animate({
        width: '35%'      
      }, 500, function() {
        
      });  
    }else{

      $('#error').show();
      $('#problem').text(data.message);
    }
    
  })
  .fail(function() {

    alert( "error" );
  });
}


var setupSystemTables = function(){
  $.ajax( "../service/setup-system-tables?setup=true" )
  .done(function(data) {
	 
	  console.log(data);	  
    data = jQuery.parseJSON(data);
    
    
    if(data.status == "success"){
      $('#install-details').text('setup-system-tables');
      setTimeout(function(){
        populateBaseResources();
      },150);
      $( "#loaded" ).stop().animate({
        width: '50%'      
      }, 500, function() {
        
      });
    }else{
      console.log(data);
      $('#error').show();
      $('#problem').text(data.message);
    }
  })
  .fail(function() {

    alert( "error" );
  });
}

var populateBaseResources = function(){
  $.ajax( "../service/populate-base-resources?setup=true" )
  .done(function(data) {
    data = jQuery.parseJSON(data);
    
    console.log(data);
    
    if(data.status == "success"){
      $('#install-details').text('populate-base-resources');
      setTimeout(function(){
        populateMomResources();
      },150);
      $( "#loaded" ).stop().animate({
        width: '65%'      
      }, 500, function() {
        
      });
    }else{
      console.log(data);
      $('#error').show();
      $('#problem').text(data.message);
    }
  })
  .fail(function() {

  });
}

var populateMomResources = function(){
  $.ajax( "../service/populate-mom-resources?setup=true" )
  .done(function(data) {
    data = jQuery.parseJSON(data);
    if(data.status == "success"){
      $('#install-details').text('populate-mom-resources');
      setTimeout(function(){
        populateSystemSettings();
      },150);
      $( "#loaded" ).stop().animate({
        width: '80%'      
      }, 500, function() {
        
      });
    }else{
      console.log(data);
      $('#error').show();
      $('#problem').text(data.message);
    }   
  })
  .fail(function() {

    alert( "error" );
  });
}

var populateSystemSettings = function(){
  $.ajax( "../service/populate_system_settings?setup=true" )
  .done(function(data) {
    data = jQuery.parseJSON(data);
    if(data.status == "success"){
      $('#install-details').text('populate_system_settings');
      setTimeout(function(){
        populateIntegrationData();
      },150);
      $( "#loaded" ).stop().animate({
        width: '90%'      
      }, 500, function() {
        
      });
    }else{
      console.log(data);
      $('#error').show();
      $('#problem').text(data.message);
    }  
  })
  .fail(function() {

    alert( "error" );
  });
}
var populateIntegrationData = function(){
  $.ajax( "../service/populate_integration_data?setup=true" )
  .done(function(data) {
    data = jQuery.parseJSON(data);
    if(data.status == "success"){
      $('#install-details').text('populate_integration_data');
      setTimeout(function(){
        createMomUser();
      },150);
      $( "#loaded" ).stop().animate({
        width: '95%'      
      }, 500, function() {
        
      });
    }else{
      console.log(data);
      $('#error').show();
      $('#problem').text(data.message);
    }  
  })
  .fail(function() {

    alert( "error" );
  });
}
//
var createMomUser = function(){
  var data = {user_name: formevent.currentTarget[4].value, password: formevent.currentTarget[5].value, email: formevent.currentTarget[4].value, requires_confirmation: false};
  
  var postData = JSON.stringify(data);
  $.ajax( {
    method: "POST",
    data: {data:postData},
    dataType: 'html',
    url: "../service/create-mom-user?setup=true"
  } )
  .done(function(data) {
    data = jQuery.parseJSON(data);
    if(data.status == "success"){
      $('#install-details').text('create-mom-user');
      $( "#loaded" ).stop().animate({
        width: '100%'      
      }, 500, function() {
        $('#install-details').text('redirecting...');
        setTimeout(function(){
          window.location = '../mom'
        },550);
      });
    }else{
      console.log(data);
      $('#error').show();
      $('#problem').text(data.message);
    } 

  })
  .fail(function() {

    alert( "error" );
  });
}
