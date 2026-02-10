$(document).ready(function(){
	$('.hide-menu').click(function(e){
		e.preventDefault();
		$('.page').toggleClass('showS');
	});
	$('#overlay').click(function(){
		$(this).hide();
		$('.popup').hide();
	});
	 input.onfocus = function () {
        window.scrollTo(0, 0);
        document.body.scrollTop = 0;
    }
	$(function(){
		var count = $('.user-menu li').size();
		if (count <=5){
			$('.show-user-menu').hide();
		}
		else{
			$('.show-user-menu').show();
		}
		if (count > 5){
			$('.user-menu').addClass('full-height');
		}
		else{
			$('.user-menu').removeClass('full-height');
		}
	})
	$('.user-menu .more').click(function(e){
		e.preventDefault();
		$(this).hide();
		$('.less').show();
		$('.user-menu').addClass('show');
		$('.wrapper').addClass('show-menu');
	});
	$('.user-menu .less').click(function(e){
		e.preventDefault();
		$(this).hide();
		$('.more').show();
		$('.user-menu').removeClass('show');
		$('.wrapper').removeClass('show-menu');
	});
	$(function () {
		$("select, input[type=checkbox]").uniform();
	});
	// onload = onresize = function() { document.body.style.fontSize = parseInt(document.body.offsetWidth/80) + 'px' }
});