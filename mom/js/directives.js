app.directive('dropdown', function ($timeout) {
    return {
        restrict: "C",
        link: function (scope, elm, attr) {
            $timeout(function () {
                $(elm).dropdown().dropdown('setting', {
                    onChange: function (value) {
                       
                    	//Check to see if we can update the model
                    	if($(elm).attr('ng-model') != null){
                    		
                    		var model = $(elm).attr('ng-model').split('.');
                    		var model_length = model.length;
                    		var object = scope[model[0]];
                    		
                    		for (var i = model_length -1; i > -1; i--){
                    			if(i == model_length - 1){
                    				object[model[i]] = value;
                    			}else{
                    				object[model[i]] = object[model[i + 1]];
                    			}
                    		}
                    	}                   	
                    }
                });
            }, 0);
        }
    };
});

