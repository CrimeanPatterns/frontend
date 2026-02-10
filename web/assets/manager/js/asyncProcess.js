var asyncProcess = {

	addProcess: function(dataUrl, container, onComplete){

		var startTime = new Date();

		var showProgress = function(status, diff){
			container.html('<img src="/lib/images/progressCircle.gif" style="width: 16px; height: 16px;"> ' + status + ', ' + diff + ' seconds');
		};

		var pollResponse = function() {
			$.ajax({
				url: dataUrl,

				success: function (response) {
					var time = new Date();
					var diff = Math.floor((time.getTime() - startTime.getTime()) / 1000);

					if(response.status == 'ready') {
						onComplete(response, diff);
					}
					else{
						showProgress(response.status, diff);
						setTimeout(function () {
							pollResponse();
						}, 3000);
					}
				},

				'error': ajaxError
			});
		};

		showProgress('sending request', 0);
		pollResponse();
	}

};