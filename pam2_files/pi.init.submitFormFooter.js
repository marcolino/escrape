jQuery(function($){

	//region Init Footer Form submit
	$('.pi-footer-form1').submit(function(){

		var $form = $(this),
			$error = $form.find('.pi-error-container'),
			action  = $form.attr('action');

		$error.slideUp(750, function() {
			$error.hide();

			var $name  = $form.find('.form-control-name'),
				$email = $form.find('.form-control-email'),
				$comments  = $form.find('.form-control-comments');

			$.post(action, {
					name: $name.val(),
					email: $email.val(),
					comments: $comments.val()
				},
				function(data){
					$error.html(data);
					$error.slideDown('slow');

					if (data.match('success') != null) {
						$name.val('');
						$email.val('');
						$comments.val('');
					}
				}
			);

		});

		return false;

	});
	//endregion

});