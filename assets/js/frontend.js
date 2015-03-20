jQuery(document).ready(function($) {
//console.log(cbox_variables);
	$.each( cbox_variables, function( key, value ) {
		if(key == 'max_width'){
			max_width = value;
		}
		if(key == 'max_height'){
			max_height = value;
		}
		if(key == 'lazy_delay'){
			lazy_delay = value;
		}
		if(key == 'use_colorbox'){
			use_colorbox = value;
		}
		if(key == 'colorbox_size' ){
			if( value != 'none' ){
				colorbox_size = value;
			}
			else{
				colorbox_size = false;
			}
		}
		if(key == 'desk_size' && value != 'none'){
			if( value != 'none' ){
				desk_size = value;
			}
			else{
				desk_size = false;
			}
		}
		if(key == 'mobile_size' && value != 'none'){
			if( value != 'none' ){
				mobile_size = value;
			}
			else{
				mobile_size = false;
			}
		}
		if(key == 'force_media_file'){
			force_media_file = value;
		}
		if(key == 'force_media_file'){
			force_media_file = value;
		}
	});
//console.log(cbox_images);
	// check if max height and widht for colorbox are set; if not set at 60%;
	if( !max_height ){
		var max_height = '60%';
	}
	if( !max_width ){
		var max_width = '60%';
	}
	if( !lazy_delay ){
		// be good to make this a callback option too - 
		var lazy_delay = '500';
	}

	setTimeout( function(){
		var $images = $("img[data-lazy-cbox]");
		$.each( $images, function() {
			lazy_load_image( this );
		});
	}, lazy_delay );
	
	function lazy_load_image( img ) {
		var $img = $(img),
			src = $img.attr( 'data-lazy-cbox' );
		
		// change colorbox slide size if defined in settings 
		if(use_colorbox){
			if( force_media_file ){	
				if( $(img).data('lazy-cbox-anchor') ){
					$href = $(img).data('lazy-cbox-anchor');
					var anchor = $img.parent('a')[0];
					$(anchor).attr('href', $href);			
				}
			}
			
		}

		$img.hide()
			.removeAttr( 'data-lazy-cbox' )
			.attr( 'data-lazy-loaded', 'true' );

		img.src = src;
		$img.fadeIn();
	}

	var viewX = $(window).innerWidth() || $(document.documentElement).clientWidth() || $(body).clientWidth();
	function load_breakpoint_image( img ) {
		var src, $size, $img = jQuery( img );
		// check if is a size-dependent image (will have data-cbox-small)
		if( $img.attr('data-cbox-small') ){
			var $smallBreak = 550;
			if( !viewX || viewX == 0 ){
				$size = 'large';
				src = $img.attr('data-cbox-large')
			}
			else if( viewX <= $smallBreak ){
				$size = 'small'; 
				src = $img.attr('data-cbox-small')
			}
			else{
				$size = 'large';
				src = $img.attr('data-cbox-large')
			}
		}	
		else{
			src = $img.attr( 'data-cbox-src' );
		}
		$img.removeAttr( 'data-cbox-src' )
			.attr( 'data-lazy-loaded', 'true' )

		if( $size ){
			var $heightData = 'data-' + $size + '-height'; 
			var $height = $img.attr( $heightData );
			$img.attr('height',$height);
			var $widthData = 'data-' + $size + '-width'; 
			var $width = $img.attr( $widthData );
			$img.attr('width',$width);
		}
		
		img.src = src;
	}


	// gallery options	
	$gallery_counter = 1;
	$('.gallery').each( function (){
		$(this).find('a').colorbox({rel: "gallery_" + $gallery_counter, maxWidth: max_width ,maxHeight: max_height});
		$gallery_counter++; 
	})

	// colorbox the single images
	$('a[href$=\".jpg\"], a[href$=\".png\"]').not( 'a.cboxElement' ).colorbox( { maxWidth: max_width ,maxHeight: max_height });

});
