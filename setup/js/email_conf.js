var emailConf = function(){
	$('#install').fadeOut();
	
  	var data = {email_confirmation_token:token};
  
	var postData = JSON.stringify(data);
	$.ajax( {
		method: "POST",
		data: {data:postData},
		dataType: 'html',
		url: "../service/confirm-email"
	} )
	.done(function(data) {
		
		var parseData = JSON.parse(data);
		console.log(data);
		if(parseData.message != ''){
			$('#error').fadeIn();
			$('#error').html('<h1>something went wrong</h1><p>'+parseData.message+'</p>');
			
		}else{
			$('#installing').fadeIn();
		}
	})
	.fail(function() {
		$('#error').fadeIn();
		$('#error').html('<h1>something went wrong</h1><p>Ajax call failed.</p>');
	});
}

$( document ).ready(function() {
	emailConf();
});