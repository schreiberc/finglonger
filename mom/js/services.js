app.factory('contentServices', function($http, $q, $rootScope) {
  return{
     getResource: function(uri){
        var deferred = $q.defer();
        $.ajax({
          url: '../service/'+uri,
          type: 'GET',
          success: function(data){
        	  var dataParse = jQuery.parseJSON(data);
              deferred.resolve(dataParse);
          }
        });
      
      return deferred.promise;
     }    
  }
});
app.factory('validationServices', function($http, $q, $rootScope){
	return{
		validateEmail: function(_emailAddress){
			
			if(_emailAddress == null){
				return false;
			}
			
			var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
		    return pattern.test(_emailAddress);
		},
		validatePhoneNumber: function(_phoneNumber){
			
			if(_phoneNumber == null){
				return false;
			}
			
			phoneNumber = _phoneNumber.replace(/\D/g,'');
			var pattern = /^\d{10,11}$/;
		    return pattern.test(phoneNumber);
		},
		validateString: function(_string, _length){
						
			if(_string == null){
				return false;
			}
			
			if(_length == null){
				length = 3;
			}
			
			if(_string.length < length){
				return false;
			}
			
			return true;
		}
	}
});
