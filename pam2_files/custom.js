(function(){
	"use strict";
	
	//Page Preloader
	$(window).load(function() {
		$("#intro").delay(300).fadeOut();
		$(".animationload").delay(400).fadeOut("slow");
		
		//$(".h_iframe").delay(700).fadeIn("slow");
		
		//$("body").css("background", "none");
		//$("body").css("background", "#000000");
		//$("body").html("<div style='align:center; margin-top:200px;' class='pi-center pi-text-grey'>non disponibile... in questo momento<br />;-) <br/><code>MSSQL Server <br>/error 233: The column '%.*ls' in table '%.*ls' cannot be null.</code></div>")
	});

	
	// Init global DOM elements, functions and arrays
    window.app 			     = {el : {}, fn : {}};
	app.el['window']         = $(window);
	app.el['document']       = $(document);
    app.el['loader']         = $('#loader');
    app.el['mask']           = $('#mask');
	
	app.fn.screenSize = function() {
		var size, width = app.el['window'].width();
		if(width < 320) size = "Not supported";
		else if(width < 480) size = "Mobile portrait";
		else if(width < 768) size = "Mobile landscape";
		else if(width < 960) size = "Tablet";
		else size = "Desktop";
		// $('#screen').html( size + ' - ' + width );
		// console.log( size, width );
	};
	
	// Resized based on screen size
		app.el['window'].resize(function() {
			app.fn.screenSize();
		});	

})();



if (app.el['window'].width() > 320){
		
	// animate on scroll (add the class triggerAnimation animated)
	$('.animated').css('opacity', '0');
	$('.triggerAnimation').waypoint(function() {
		var animation = $(this).attr('data-animate');
		$(this).css('opacity', '');
		$(this).addClass("animated " + animation);
	},
		{
			offset: '85%',
			triggerOnce: true
		}
	);
}

// Animated Appear Element (for delay parameter class:animated hiding and data-delay="500")
	if (app.el['window'].width() > 320){
		
		$('.animated').appear(function() {
		  var element = $(this);
		  var animation = element.data('animate');
		  var animationDelay = element.data('delay');
		  if(animationDelay) {
			  setTimeout(function(){
				  element.addClass( animation + " visible" );
				  element.removeClass('hiding');
			  }, animationDelay);
		  } else {
			  element.addClass( animation + " visible" );
			  element.removeClass('hiding');
		  }               

		}, {accY: -150});
    
	} else {
	
		$('.animated').css('opacity', 1);
		
	}
	
	/*
	var IE;
	IE = navigator.appVersion;
	if (IE < 10){
	} else {
	}
	
	var test_canvas = document.createElement("canvas") //try and create sample canvas element
	var canvascheck=(test_canvas.getContext)? true : false //check if object supports getContext() method, a method of the canvas element
	alert(canvascheck) //alerts true if browser supports canvas element
	*/
	
    // fade in .back-to-top
    $(window).scroll(function () {
        if ($(this).scrollTop() > 500) {
            $('.back-to-top').fadeIn();
        } else {
            $('.back-to-top').fadeOut();
        }
    });

    // scroll body to 0px on click
    $('.back-to-top').click(function () {
        $('html, body').animate({
            scrollTop: 0,
            easing: 'swing'
        }, 750);
        return false;
    });
	
$(document).ready(function() {

	/*footer-contact*/
	if( $("#f-contact").length ) {
		$("#f-contact").validate({
			rules: {
				nomec: {
					required: true
					},
				emailc:{
					required:true,
					email:true
					},
				textc:{
					required:true,
					}
				
				},
			messages: {
				nomec: {
					required: "Inserisci Nome!"
					},
				emailc: {
					required: "Indirizzo Email!"
					},
			},
			
			submitHandler: function(form) {
				  var $this = $(form);
				  $.ajax({
					cache: false,
					//url: $this.attr('action'),
					url: "run-reqaJaX-contact-footer",
					type: "POST",
					//data: $(form).serialize(),
					data: $this.serialize(),
					success: function(responseData, textStatus, jqXHR) {
						//alert(responseData);
						//$('html, body').animate({scrollTop: 0,easing: 'swing'}, 750);
						//$("#order-response").html(responseData).fadeIn();
						//$("#f-order").fadeOut();
						$("#response-fcontact").fadeIn("slow").html(responseData);
						},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(errorThrown);
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
	/*/footer-contact*/
	
	
	
	
if ($.fn.leanModal) {
	$('a[rel*=leanModal]').click(function(e) {
			var nik = $(this).attr("data-nik");
			var tipo = $(this).attr("data-tipo");
			$('#box-messages').find('.box-messages-header h4').html("Messaggio Privato "+tipo+" per: @"+ nik +"");
			$('#box-messages').find('.box-messages-form').html('<input type="hidden" name="niktosend" value='+ nik +'> ');
			console.log("@"+nik);
			//alert(nik); 
			//$('#box-messages').find('.modal-footer').html('<button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button> <a href="./run?act=wdel&id='+ id +'" class="btn btn-default">Conferma eliminazione</a>');
		});
	/*$(document).on('click','a[rel*=leanModal]',function() {
			var nik = $(this).attr("data-nik");
			var tipo = $(this).attr("data-tipo");
			$('#box-messages').find('.box-messages-header h4').html("Messaggio Privato "+tipo+" per: @"+ nik +"");
			$('#box-messages').find('.box-messages-form').html('<input type="hidden" name="niktosend" value='+ nik +'> ');
			alert(nik); 
		});*/	
		
	$(function() {
		$('a[rel*=leanModal]').leanModal({ top: 180, closeButton: ".modal_close" });
	});
	
	
	
	$('a[rel*=ConfirmLeanModal]').click(function(e) { 
			var id = $(this).attr("data-id");
			var where = $(this).attr("data-where");
			$('#box-messages-confirm').find('.box-messages-header h4').html('<center>ATTENZIONE!</center>');
			$('#box-messages-confirm').find('.dynbutton').html('<a href="./run-del-message?id='+id+'&whe='+where+'" class="btn pi-btn pi-btn-red pi-btn-wide">Elimina</a>');
		});
	$(function() {
		$('a[rel*=ConfirmLeanModal]').leanModal({ top: 160, closeButton: ".modal_close" });
	});
}
	
	
	/*f-box-messages*/
	$.validator.addMethod("lettersnumberwithbasicpunc", function(value, element) {
		return this.optional(element) || /^[a-z0-9\-.,?!()èìòàéùç€'"\s]+$/i.test(value);
		}, "Letters or punctuation only please");
		
	if( $("#f-box-messages").length ){ 
		$("#f-box-messages").validate({
			rules: {
				messaggio:  {
					required: true,
					maxlength: 250,
					lettersnumberwithbasicpunc : true
					}
				},
			messages: {
				messaggio: {
					lettersnumberwithbasicpunc : "Solo caratteri alfa numerici."
					}
				},
			submitHandler: function(form) {
				var $this = $(form);
				$.ajax({
					cache: false,
					url: "./run-reqaJaX-freemessages",
					type: "POST",
					data: $this.serialize(),
					success: function(responseData, textStatus, jqXHR) {
						//alert(responseData);
						//$('html, body').animate({scrollTop: 0,easing: 'swing'}, 750);
						//$("#order-response").html(responseData).fadeIn();
						//$("#f-order").fadeOut();
						$("#response-f-box-messages").fadeIn("slow").html(responseData);
						},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(errorThrown);
						},
					complete: function(){
						// reset form after completition
						$this[0].reset();
						}
					});
					return false;
				}
			
		});
	};
	
	
	if( $("#f-feedback").length ){ 
		$("#f-feedback").validate({
			rules: {
				testo:  {
					required: true,
					maxlength: 2000,
					lettersnumberwithbasicpunc : true
					}
				},
			testo: {
				messaggio: {
					lettersnumberwithbasicpunc : "Solo caratteri alfa numerici."
					}
				},
			submitHandler: function(form) {
				var $this = $(form);
				$.ajax({
					cache: false,
					url: "./run-reqaJaX-feedback",
					type: "POST",
					data: $this.serialize(),
					success: function(responseData, textStatus, jqXHR) {
						//alert(responseData);
						//$('html, body').animate({scrollTop: 0,easing: 'swing'}, 750);
						//$("#order-response").html(responseData).fadeIn();
						//$("#f-order").fadeOut();
						//$("#f-feedback").fadeOut("slow").html();
						$("#f-feedback").fadeIn("slow").html(responseData);
						},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(errorThrown);
						},
					complete: function(){
						// reset form after completition
						//$this[0].reset();
						}
					});
					return false;
				}
			
		});
	};
	
	/****f-search*****/
	$.validator.addMethod("lettersnumberwithbasicpunc", function(value, element) {
		return this.optional(element) || /^[a-z0-9\-.,?!()èìòàéùç€'"\s]+$/i.test(value);
		}, "Letters or punctuation only please");
	
	$.validator.addMethod("alertCharacter", function(value, element) {
		var p = /Scrivi più caratteri./;
		return (value.match(p)) ? RegExp.$1 : true;
		}, "Scrivi più caratteri.");
	
		
	if( $("#f-search").length ){
		$("#f-search").validate({
			errorPlacement: function(error, element){
				element.attr("value",error.text());
				//element.val(error[0].outerText);

			},
			rules: {
				q: {
					alertCharacter: true,
					required : true,
					lettersnumberwithbasicpunc : true,
					minlength : 3,
					maxlength: 40
					}
				},
			messages: {
				q: {
					minlength: "Scrivi più caratteri.",
					maxlength: "Tropo testo."
					}
				}
			}); 
		};
	/****f-search*****/
	
	
	/****zcreen*****/
	var WSWres = window.screen.availWidth;
	var WSHres = window.screen.availHeight;
	var ScreenWidth		= screen.width;
	var ScreenHeight	= screen.height;
	$("input[name='userScreenResolution']").val(ScreenWidth +"/"+ ScreenHeight);
	//$("#swr").html(ScreenWidth);
	//$("#shr").html(ScreenHeight);
	//console.log("Av: "+ WSWres +"/"+ WSHres );
	//console.log("Or: "+ ScreenWidth +"/"+ ScreenHeight );
	/*/zcreen*******/
	
});