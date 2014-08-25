jQuery(document).ready(function($) {

	// onclick for small images shortcode
/*	$('.shortcode-instagram.slider-wrapper span.link').not('.show-content').on('click', function(e){
		var $link = $(this).find('a');
		if ( $link.length > 0 ) { $link.trigger('click'); }
	});*/
	
	$('.no-touch .trigger-first-post-pp').on('click', function(e) {
		e.preventDefault();

		var $this = $(this),
			$postContainer = $this.parents('article.post').first(),
			$firstPpAnchor = $postContainer.find('a[data-pp^="prettyPhoto"]').first();

		$firstPpAnchor.trigger('click');
		return false;
	});

	var $commentForm = $('#commentform');

	$commentForm.on('click', 'a.clear-form', function (e) {
		e.preventDefault();
		$commentForm.find('input[type="text"], textarea').val('');
		return false;
	});

	$commentForm.on('click', ' a.dt-btn.dt-btn-m', function(e) {
		e.preventDefault();
		$commentForm.find('#submit').trigger('click');
		return false;
	});

	var $container = $('.iso-container');

	// filter
	$('.filter:not(.without-isotope) .filter-categories a').on('click.presscorFilterCategories', function(e) {
		var selector = $(this).attr('data-filter');
		$container.isotope({ filter: selector });
		return false;
	});

	// filter by
	$('.filter:not(.without-isotope) .filter-extras .filter-by a').on('click', function(e) {
		var sorting = $(this).attr('data-by'),
			sort = $(this).parents('.filter-extras').find('.filter-sorting > a.act').first().attr('data-sort');

		$container.isotope({ sortBy : sorting, sortAscending : 'asc' == sort });
		return false;
	});

	// sorting
	$('.filter:not(.without-isotope) .filter-extras .filter-sorting a').on('click', function(e) {
		var sort = $(this).attr('data-sort'),
			sorting = $(this).parents('.filter-extras').find('.filter-by > a.act').first().attr('data-by');

		$container.isotope({ sortBy : sorting, sortAscending : 'asc' == sort });
		return false;
	});

	var $paginator = $('.paginator[role="navigation"]'),
		$dots = $paginator.find('a.dots');
	$dots.on('click', function() {
		$paginator.find('div:hidden').show().find('a').unwrap();
		$dots.remove();
	});

	

	// search
	$('.widget .searchform .submit').on('click', function(e) {
		e.preventDefault();
		$(this).siblings('input.searchsubmit').click();
		return false;
	});

	// pin it
	$(".soc-ico a.share-button.pinterest").click(function(event){
		event.preventDefault();
	    $("#pinmarklet").remove();
	    var e = document.createElement('script');
	    e.setAttribute('type','text/javascript');
	    e.setAttribute('charset','UTF-8');
	    e.setAttribute('id','pinmarklet');
	    e.setAttribute('src','http://assets.pinterest.com/js/pinmarklet.js?r='+Math.random()*99999999);document.body.appendChild(e);
	});
	var mtResizeTimeout;
	
	$(window).on("resize", function() {
		clearTimeout(mtResizeTimeout);
		mtResizeTimeout = setTimeout(function() {
			$(window).trigger( "metroresize" );
		}, 200);
	});
	var addSliderTimeout;
	clearTimeout(addSliderTimeout);	
	//jQuery(".swiper-container").fadeOut();
	addSliderTimeout = setTimeout(function() {
		//var homeSliderH = jQuery(".swiper-container").height();
		
		if( $(".swiper-container").length ){	
			var loading_label = jQuery("<div></div>").attr("id", "loading-label").addClass("loading-label").css("position" , "fixed").hide().appendTo(".swiper-container").first();
			loading_label.fadeIn(250);
		
			jQuery(".swiper-wrapper").animate({
				//minHeight : homeSliderH + "px"
				opacity: 1
			}, 500, function() {
			//jQuery(".swiper-container > .swiper-wrapper").fadeIn();
				loading_label.fadeOut(500);			
			});
		};
	}, 300);

	/* !Metro slider*/
	$(".swiper-container > .swiper-wrapper > .swiper-slide .preload-me").loaded(null, function() {
		

		if ($('.swiper-container').length > 0) {
			var $mainSwiperContent = $('.swiper-container').not('.swiper-container-horizontal'),
				$mainSwiperContentLength = $mainSwiperContent.find(' > .swiper-wrapper > .swiper-slide').length,
				$mainRightArrow = $mainSwiperContent.find('.arrow-right'),
				$mainLeftArrow = $mainSwiperContent.find('.arrow-left');
			if( $mainSwiperContentLength <= swiperColH){
				$($mainRightArrow).hide();
				$($mainLeftArrow).hide();
			}
			var swiperN1 = $mainSwiperContent.first().swiper({
				slidesPerSlide : swiperColH,
				onTouchMove:function(){
					var posX = swiperN1.getTranslate('x');
					if( posX >= 0 ){
						$mainRightArrow.removeClass('disable');
						$mainLeftArrow.addClass('disable');
					}else if( posX <= -($mainSwiperContent.find('.swiper-wrapper').first().width()-$mainSwiperContent.first().width()) ){
						$mainRightArrow.addClass('disable');
						$mainLeftArrow.removeClass('disable');
					}else{
						$mainLeftArrow.removeClass('disable');
						$mainRightArrow.removeClass('disable');
					}
				},
				onSlideChangeEnd :function(){
					var posX = swiperN1.getTranslate('x');
					if( posX >= 0 ){
						$mainRightArrow.removeClass('disable');
						$mainLeftArrow.addClass('disable');
					}else if( posX <= -($mainSwiperContent.find('.swiper-wrapper').first().width()-$mainSwiperContent.first().width()) ){
						$mainRightArrow.addClass('disable');
						$mainLeftArrow.removeClass('disable');
					}
				}
				
			});
			var swiperN1Length = swiperN1.slides.length;
			
			//Navigation arrows
			$mainLeftArrow.click(function(e) {
			    e.preventDefault();
				swiperN1.swipePrev();
				var swiperN1Index = swiperN1.activeIndex;
				$mainRightArrow.removeClass('disable');
				if( swiperN1Index == 0 ){
					
					$(this).addClass('disable');
				}else{
					$(this).removeClass('disable');
				}
			});
			$mainRightArrow.click(function(e) {
			    e.preventDefault();
				swiperN1.swipeNext();
				var swiperN1Index = swiperN1.activeIndex;
				$mainLeftArrow.removeClass('disable');
				if( (swiperN1Index+swiperColH) >= swiperN1Length ){
					
					$(this).addClass('disable');
				}else{
					$(this).removeClass('disable');
				}
			});

			//Vertical
			var swiperVerticalSlides = [];

			$('.swiper-container.swiper-container-horizontal').each( function() {
				var $subSwiperContent = $(this),
					$subSwiperContentLength = $subSwiperContent.find('.swiper-slide').length,
					$subUpArrow = $subSwiperContent.find('.arrow-top'),
					$subDownArrow = $subSwiperContent.find('.arrow-bottom');
				if( $subSwiperContentLength <= swiperCol){
					$($subUpArrow).hide();
					$($subDownArrow).hide();
				}
				var swiperN2 = $subSwiperContent.first().swiper({
					slidesPerSlide : swiperCol,
					mode: 'vertical',
					onTouchMove:function(){
						var posY = swiperN2.getTranslate('y');
						if( (posY) >= 0 ){
							$subDownArrow.removeClass('disable');
							$subUpArrow.addClass('disable');
						}else if( (posY) <= -($subSwiperContent.find('.swiper-wrapper').first().height() - $subSwiperContent.height()) ){
							$subDownArrow.addClass('disable');
							$subUpArrow.removeClass('disable');
						}else{
							$subUpArrow.removeClass('disable');
							$('.swiper-n2 .arrow-bottom').removeClass('disable');
						}
					},
					onSlideChangeEnd :function(){
						var posY = swiperN2.getTranslate('y');
						if( posY >= 0 ){
							$subDownArrow.removeClass('disable');
							$subUpArrow.addClass('disable');
						}else if( posY <= -($subSwiperContent.find('.swiper-wrapper').first().height()-$subSwiperContent.height()) ){
							$subDownArrow.addClass('disable');
							$subUpArrow.removeClass('disable');
						}
					}
				});

				swiperVerticalSlides.push(swiperN2);

				var swiperN2Length = swiperN2.slides.length;
				// $subUpArrow.addClass('disable');
				$subUpArrow.click(function(e) {
				    e.preventDefault();
					swiperN2.swipePrev();
					var swiperN2Index = swiperN2.activeIndex;
					$subDownArrow.removeClass('disable');
					if( swiperN2Index == 0 ){			
						$(this).addClass('disable');
					}else{
						$(this).removeClass('disable');
					}
				});

				$subDownArrow.click(function(e) {
				    e.preventDefault();
					swiperN2.swipeNext();
					var swiperN2Index = swiperN2.activeIndex;
					$subUpArrow.removeClass('disable');
					if( (swiperN2Index+swiperCol) >= swiperN2Length ){
						
						$(this).addClass('disable');
					}else{
						$(this).removeClass('disable');
					}
				});
			});

			$(window).on("metroresize", function(){
			  //Unset height
				$('.swiper-container').css({height:''});
				
				//Calc Height
				var $images = $mainSwiperContent.find('> .swiper-wrapper > .swiper-slide > img');

				if ( $images.length > 0 ) {
					var heights = $.map( $images, function( o ) { return $(o).height(); } ),
						etalonHeight = Math.min.apply( Math, heights );
				} else {
					etalonHeight = 980;
				}

				$('.swiper-container').css({height: etalonHeight});
				
				swiperN1.reInit();

			  	if ( swiperVerticalSlides.length > 0 ) {
			  		var arrLingth = swiperVerticalSlides.length;
			  		for ( var i=0; i < arrLingth; i++ ) {
			  			swiperVerticalSlides[i].reInit();
			  		}
			  	}
			}).trigger('metroresize');
		};
	});
	/* !Metro slider: end*/

});