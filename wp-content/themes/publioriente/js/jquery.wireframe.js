// JavaScript Document

/*!
 * WireFrame v.0.1
 * Copyright (c) 2012 Dream-Theme.com
 */

/* 
	jQuery required.
	Run this on the document ready event if you are using a fixed layout.
	Works on fixed layout only.
*/

jQuery(document).ready(function($){

$.fn.wireframe = function(){
	$container = $(this);
	// Recalculate columns width in IE7 (solves box-sizing issue)
	if ($.browser.msie && $.browser.version < 8){
		$(".col", $container).each(function(){
			var	_width = $(this).width(),
				_outer_width = $(this).outerWidth();
			_width = 2*_width -_outer_width - 1;
			
			$(this).css({"width":_width});
		});
	}
	// Recalculates some columns width in Opera (solves the problem with incorrect handling tenths of a percent)
	if ($.browser.opera){
		$(".w_1-3", $container).each(function(){
			var _width = $(this).parent().width()/3;
				$(this).css({"width":_width});
		});
		$(".w_2-3", $container).each(function(){
			var _width = $(this).parent().width()/3*2;
				$(this).css({"width":_width});
		});
	}
}

});