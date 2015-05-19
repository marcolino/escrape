/*(function($) {
$.fn.expandcollapse = function() {
    return this.each(function() {
        obj = $(this);
        switch (obj.css("display")) {
            case "block":
                displayValue = "none";
                break;

            case "none":                    
            default:
                displayValue = "block";
        }

        obj.css("display", displayValue);
    });
};
} (jQuery));

$("traget").click(function() {
        $("form").expandcollapse();
});*/

$(document).ready(function() {
	
	if( $("#f-login").length ) {
		$("#f-login").validate({
			errorPlacement: function (error, element) {
				element.attr("placeholder",error.text());
			},
			rules: {
				},
			messages: {
				emaill: {
					required: "Indirizzo email!"
					},
				passl: {
					required: "La tua password!"
					},
			}
		
		});
	}
	
	if( $("#f-getdata").length ) {
		$("#f-getdata").validate({
			errorPlacement: function (error, element) {
				element.attr("placeholder",error.text());
				//alert(error.text());
			},
			rules: {
				},
			messages: {
				emaill: {
					required: "Indirizzo email!"
					}
			},
			submitHandler: function(form) {
				  var $this = $(form);
				  $.ajax({
					cache: false,
					//url: $this.attr('action'),
					url: "./run-reqaJaX-getdata",
					type: "POST",
					//data: $(form).serialize(),
					data: $this.serialize(),
					success: function(responseData, textStatus, jqXHR) {
						//console.log(responseData);
						//alert("Success!");
						alert(responseData);
						//$('html, body').animate({scrollTop: 0,easing: 'swing'}, 750);
						//$("#f-order").fadeOut();
						//$("#response").fadeIn("slow").html(responseData);
						},
						
					error: function(jqXHR, responseData, textStatus, errorThrown) {
						console.log(responseData);
						
						if(textStatus !== "Utente non trovato" ){
							alert("Riprovare più tardi...");
							console.log(textStatus);
							}
						if(textStatus == "Utente non trovato" ){
							alert("Utente non trovato!(D)");
							}
						
						//console.log(request.responseText);
						//alert("Error!");
						//alert(request.responseText);
						
						/*if( textStatus == "Nikname in uso" || textStatus == "Email in uso" ){
							
							if( textStatus == "Nikname in uso") {
								$('#modalShowInfoUpdate').find('.modal-body').html('<h1 class="text-danger"><i class="fa fa-exclamation-circle"></i> Errore!</h1><p>Nikname in uso!</p>');
							}
							if( textStatus == "Email in uso") {
								$('#modalShowInfoUpdate').find('.modal-body').html('<h1 class="text-danger"><i class="fa fa-exclamation-circle"></i> Errore!</h1><p>Email in uso!</p>');
							}
						
						} else {
							
							$('#modalShowInfoUpdate').find('.modal-body').html('<h1 class="text-danger"><i class="fa fa-exclamation-circle"></i> Errore!</h1><p class="small">Customer service:<br />AM 10:30/11:00 - PM 15:30/16:00<br /> +39 ...</p>');
							}
							
						$('#modalShowInfoUpdate').modal('show');*/
						},
						
					complete: function(){
						// reset form after completition
						$this[0].reset();
						}
					});
					return false; 
				}
		
		});
	}
	
	/* switch on click same button two form */
	$("#bt-getdata").click(function(){
		$('#f-login, #f-getdata').toggle('',function() {
			if($('#f-getdata').is(':visible')) {
				$("#bt-getdata").html('<i class="fa fa-lock" title="Login"></i>');
			}
			if($('#f-login').is(':visible')) {
				$("#bt-getdata").html('<i class="fa fa-question-circle" title="Dati dimenticati"></i>');
			}
		});
	});
	
/***@@@/




	
/***@@@ mobile validate procedure*/
	if( $("#fm-login").length ) {
		$("#fm-login").validate({
			errorPlacement: function(error, element){
				error.insertAfter(element).hide().fadeIn('slow');
			},
			rules: {
				},
			messages: {
				emaill: {
					required: "Indirizzo email!"
					},
				passl: {
					required: "La tua password!"
					},
			}
		});
	}
	
	if( $("#fm-getdata").length ) {
		$("#fm-getdata").validate({
			errorPlacement: function(error, element){
				error.insertAfter(element).hide().fadeIn('slow');
			},
			rules: {
				},
			messages: {
				emaill: {
					required: "Indirizzo email!"
					}
			},
			submitHandler: function(form) {
				  var $this = $(form);
				  $.ajax({
					cache: false,
					//url: $this.attr('action'),
					url: "./run-reqaJaX-getdata",
					type: "POST",
					//data: $(form).serialize(),
					data: $this.serialize(),
					success: function(responseData, textStatus, jqXHR) {
						//alert("Success!submit");
						//console.log(responseData);
						alert(responseData);
						//$('html, body').animate({scrollTop: 0,easing: 'swing'}, 750);
						//$("#f-order").fadeOut();
						//$("#response").fadeIn("slow").html(responseData);
						},
						
					error: function(jqXHR, responseData, textStatus, errorThrown) {
						console.log(responseData);
						
						if(textStatus !== "Utente non trovato" ){
							alert("Riprovare pi&ugrave; tardi...");
							console.log(textStatus);
							}
						if(textStatus == "Utente non trovato" ){
							alert("Utente non trovato!(M)");
							}
						
						/*if( textStatus == "Nikname in uso" || textStatus == "Email in uso" ){
							
							if( textStatus == "Nikname in uso") {
								$('#modalShowInfoUpdate').find('.modal-body').html('<h1 class="text-danger"><i class="fa fa-exclamation-circle"></i> Errore!</h1><p>Nikname in uso!</p>');
							}
							if( textStatus == "Email in uso") {
								$('#modalShowInfoUpdate').find('.modal-body').html('<h1 class="text-danger"><i class="fa fa-exclamation-circle"></i> Errore!</h1><p>Email in uso!</p>');
							}
						
						} else {
							
							$('#modalShowInfoUpdate').find('.modal-body').html('<h1 class="text-danger"><i class="fa fa-exclamation-circle"></i> Errore!</h1><p class="small">Customer service:<br />AM 10:30/11:00 - PM 15:30/16:00<br /> +39 ...</p>');
							}
							
						$('#modalShowInfoUpdate').modal('show');*/
						},
						
					complete: function(){
						// reset form after completition
						$this[0].reset();
						}
					});
					return false; 
				}
			
			
		});
	}
	
	$("#btm-getdata, #btm-login").click(function(){
		$('#fm-login, #fm-getdata').slideToggle('',function() {
			if($('#fm-getdata').is(':visible')) {
				$(".message-switch").html("Inserisci l'email usata in fase di registrazione.");
			}
			if($('#fm-login').is(':visible')) {
				$(".message-switch").html("Inserisci l'email e password usata in fase di registrazione.");
			}
		});
	});
	

});