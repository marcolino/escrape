$(document).ready(function(){
	
	$(".bt-vot").click(function(){
		var id = $(this).data("id");
		var dv = $(this).data("val");
		var dov = $(this).data("dov");
		//console.log(id);
		//console.log(dv);
		//console.log(dov);
		
		$.ajax({
			type: "POST",
			cache: false,
			url: "./run-reqaJaX-voto",
			data:"id="+ id +"&dv="+ dv +"&dov="+ dov ,
			success: function(responseData, textStatus, jqXHR) {
				$("#response-vote").html(responseData);
				},
			error: function(jqXHR, responseData, textStatus, errorThrown) {
				$("#response-vote").html(responseData);
				} 
			});
		});
	
	
	/*
	$(".bt-vot").click(function(){
		var id = $(this).data("id");
		var dv = $(this).data("val");
		var dov = $(this).data("dov");
		//console.log(id);
		//console.log(dv);
		//console.log(dov);
		
		$.ajax({
			type: "POST",
			cache: false,
			url: "./run-reqaJaX-voto",
			data:"id="+ id +"&dv="+ dv +"&dov="+ dov ,
			success: function(responseData, textStatus, jqXHR) {
				if( textStatus == "already"){
					alert("Hai già dato il tuo voto!");
					} else {
					  var url = "./annuncio?id="+ id;
					  var answer = confirm("Grazie per il tuo voto!\nClicca OK se vuoi vedere il risultato aggiornato.");
						  if (answer){
							  location.href = url;
						  }
						  else{
							  //some code
						  }
					}
				
				},
			error: function(jqXHR, responseData, textStatus, errorThrown) {
				//console.log(jqXHR.responseText);
				if( textStatus == "already"){
					alert("Hai già dato il tuo voto!");
					}
				} 
			
			
			});
		});
		
	*/	
		
	
	/*$("#bt-add-favorite").click(function(){*/
	$(".bt-add-favorite").bind("click", function(){
		var ida= $(this).attr("data-ida");
		//console.log(ida);
		
		$.ajax({
			type: "POST",
			cache: false,
			url: "./run-reqaJaX-favorite",
			data:"ida="+ ida,
			success: function(responseData, textStatus, jqXHR) {
				/*var url = "./annuncio?id="+ id;
				var answer = confirm("Grazie per il tuo voto!\nClicca OK se vuoi vedere il risultato aggiornato.");
					if (answer){
						location.href = url;
					}
					else{
						//some code
					}*/
					alert("Inserito in preferiti con successo!");
				//console.log(textStatus);
				},
			error: function(jqXHR, responseData, textStatus, errorThrown) {
				//console.log(jqXHR.responseText);
				if( textStatus == "already"){
					alert("Questo annuncio è già presente nei tuoi preferiti!");
					}
				if( textStatus == "nologuser"){
					alert("Fai Login o Registrati!");
				} else {
					//alert("Questo annuncio è stato aggiunto nei tuoi Favoriti!");
					}	
				
				} 
			});
		
	});	
	
	
	
	$.validator.addMethod("lettersnumberwithbasicpunc", function(value, element) {
		return this.optional(element) || /^[a-z0-9\-.,?!()èìòàéùç€'"\s]+$/i.test(value);
		}, "Letters or punctuation only please");
	
	$("#f-comment").validate({
		
		rules: {
			comment: {
				required : true,
				lettersnumberwithbasicpunc : true,
				minlength : 25,
				minWords : 4
				}
		},
		messages: {
			comment: {
				required : "Campo obbligatorio. <br />Attenzione non inserire più volte lo stesso commento!",
				lettersnumberwithbasicpunc : "Solo caratteri alfa numerici.",
				minlength : "Non sembra un commento di qualità,...",
				minWords : "Il tuo comento non sembra di avere abbastanza parole..."
				}
		},
		submitHandler: function(form) {
				  var $this = $(form);
				  $.ajax({
					cache: false,
					//url: $this.attr('action'),
					url: "./run-reqaJaX-comments",
					type: "POST",
					//data: $(form).serialize(),
					data: $this.serialize(),
					success: function(responseData, textStatus, jqXHR) {
						//console.log(responseData);
						//alert(responseData);
						//$('html, body').animate({scrollTop: 0,easing: 'swing'}, 750);
						//$("#f-order").fadeOut();
						$("#response-comment").fadeIn("slow").html(responseData);
						$("#showConfInComment").fadeIn("slow").html("Il tuo commento è stato salvato!");
						},
					error: function(jqXHR, responseData, textStatus, errorThrown) {
						console.log(responseData);
						$("#response-comment").html(errorThrown);
						},
					complete: function(){
						// reset form after completition
						
						$this[0].reset();
						}
					});
					return false; 
				}
		
		});
		
		
	
	$("#f-mess-priv").validate({
		rules:{
			"message":{
				required: true,
				lettersnumberwithbasicpunc: true,
				minlength : 25,
				minWords : 4
				}
			},
		messages: {
			"message": {
				required : "Campo obbligatorio.",
				lettersnumberwithbasicpunc : "Solo caratteri alfa numerici.",
				minlength : "Non sembra un messaggio di qualità,...",
				minWords : "Il tuo comento non sembra di avere abbastanza parole..."
				}
		},
		
		submitHandler: function(form) {
				  var $this = $(form);
				  $.ajax({
					cache: false,
					//url: $this.attr('action'),
					url: "./run-reqaJaX-vipmessage",
					type: "POST",
					//data: $(form).serialize(),
					data: $this.serialize(),
					success: function(responseData, textStatus, jqXHR) {
						console.log(responseData);
						//alert(responseData);
						//$('html, body').animate({scrollTop: 0,easing: 'swing'}, 750);
						$("#response-message").fadeIn("slow").html(responseData);
						},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(textStatus);
						$("#response-message").html(textStatus);
						},
					complete: function(){
						// reset form after completition
						$this[0].reset();
						}
					});
					return false; 
				}
		});
	
	
	
	
	$("#bt-abuso").click(function(){
		$("#f-abuso").slideToggle();
	});
	
	$("#f-abuso").validate({
		submitHandler: function(form) {
				  var $this = $(form);
				  $.ajax({
					cache: false,
					//url: $this.attr('action'),
					url: "run-reqaJaX-abuso",
					type: "POST",
					//data: $(form).serialize(),
					data: $this.serialize(),
					success: function(responseData, textStatus, jqXHR) {
						console.log(responseData);
						//alert(responseData);
						//$('html, body').animate({scrollTop: 0,easing: 'swing'}, 750);
						$("#f-abuso").remove();
						$("#response-abuso").fadeIn("slow").html(responseData);
						},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(textStatus);
						},
					complete: function(){
						// reset form after completition
						$this[0].reset();
						}
					});
					return false; 
				}
		});
		
		
	/****/
	/*
	var idLV = $("#mainImgRef").attr("data-id"); 
	var imgLV = $("#mainImgRef").attr("data-link");
	function annVisit(){
		$.ajax({
			cache: false,
			url:"./run-reqaJaX-update-annLV?id="+idLV+"&img="+imgLV ,
			type:"POST",
			success:function(jqXHR, textStatus, errorThrown, responseText){
				console.log("success-update-visit");
				console.log(textStatus);
				},
			error:function(jqXHR, textStatus, errorThrown, responseText){
				console.log("error-update-visit");
				console.log(jqXHR.responseText);
				},
			complete:function(){},
			})
			.done(function(html) {
				 //alert("Data Saved: " + html);
			});
	};
	window.setTimeout(function() {
		console.log(idLV + " - " + imgLV );
		annVisit();
	}, 20);
	
	function showAnnLV() {
		$("#annLV").slideDown('slow');
	};
	window.setTimeout(function() { 
			//alert('test'); 
			//showAnnLV();
		}, 3000);
	*/
	/****/	
	
});