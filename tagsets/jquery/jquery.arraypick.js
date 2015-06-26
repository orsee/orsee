/*
name	 arraypick
type	 jQuery
param	 options                  hash                    object containing config options
param	 options[event]           string                  mouse event to trigger plugin
param	 options[layout]          string                  set div layout to vertical or horizontal
                                  ('vertical','horizontal')
param	 options[valuefield]      string                  field to insert value, if not same as click field (name of input field)
param	 options[arraydata]	hash	(JSON) array containing {show:x,value:x,disabled:bool} nodes
param	options[numcols]	integer	number of columns, def. sqrt(array length)
param	options[opacity]	float	set opacity of array container
param	callback	function	function gets passed selected (value,show,index)
*/

jQuery.fn.arraypick = function(options, callback) {

	var settings = {
		arraydata	: [],
		numcols		: 0,
		maxnumcols	: 0,
		event		: 'click',
		valuefield	: null,
		opacity		: 0.9
		};
		
	if(options) {
		jQuery.extend(settings, options);
	};
	
	var callback = callback || function() { };
	
	// if no numcols, set to rounded square root for lmost square box
	if (settings.numcols>0) {
		var numcols = settings.numcols;
	} else {
		var numcols = Math.round(Math.sqrt(settings.arraydata.length));
	}
 	if (settings.maxnumcols> 0 && numcols > settings.maxnumcols) {
 		var numcols = settings.maxnumcols;
 	} 
	jQuery(this)[settings.event](function(e) {
		
		var self = this,
		$self = jQuery( this ),
		$body = jQuery( "body" );
		
		// clear any malingerers
		jQuery("#AP_arrcont").remove();
		

		// append arrcont to body
		// add class "AP" for mouseout recognition, although there is only
		// one arrcont on the screen at a time
		var $arrcont = jQuery("<div id='AP_arrcont' class='AP' />").appendTo( $body );
		$arrcont.css("opacity",settings.opacity);
		binder( $arrcont );
		
		$arrcol = new Array();
 		for (var i=0;i<numcols;i++) {
 			$arrcol[i] = jQuery("<div class='AP_arrcol' id='arrcol" + i + "' />").appendTo( $body );
			$arrcol[i].addClass('floatleft');
		}
				
		// all the action right here
		// fill in the hours container (minutes rendered in hour mouseover)
		// then make hour container visible
		renderarr();
		putcontainer();
		
		/*----------------------helper functions below-------------------------*/
				
		function renderarr() {
			// fill in the $arrcont div
			var c = 0; 
			// counter as index 2 of hr id, gives us index 
			
			// number of rows per col
 			var r=1;
 			var rows=Math.ceil(settings.arraydata.length/numcols);
 
 			for (var i=0;i<settings.arraydata.length;i++)
 			{
 				$hd = jQuery("<div class='AP_arr' id='hr_" + i + "_" + c + "'>" + settings.arraydata[i].show + "</div>");
				binder($hd);
				$arrcol[c].append($hd);
				if (r==rows) { c++; r=1; }
 				else { r++; }
			}
			for (var i=0;i<numcols;i++) $arrcont.append($arrcol[i]);
		}
		
		function putcontainer() {
			if ( e.type != 'focus') {
				$arrcont[0].style.left = e.pageX - 5 + 'px';
				//$arrcont[0].style.top = e.pageY - (Math.floor($arrcont.height() / 2)) + 'px';
				$arrcont[0].style.top = e.pageY + 'px';
				rectify($arrcont);
			}
			else {
				$self.after($arrcont);
			}
			$arrcont.slideDown('fast');		
		}
		
		function rectify($obj) { 
			// if a div is off the screen, move it accordingly
			var ph = document.documentElement.clientHeight 
						? document.documentElement.clientHeight 
						: document.body.clientHeight;
			var pw = document.documentElement.clientWidth
						? document.documentElement.clientWidth
						: document.body.clientWidth;
			var t = parseInt( $obj[0].style.top );
			var l = parseInt( $obj[0].style.left );
			var st = document.documentElement.scrollTop 
						? document.documentElement.scrollTop 
						: document.body.scrollTop;
			// run off top
			if (t + $obj.height() - st > ph) {
				$obj.css("top",st + ph - $obj.height() - 10 + 'px');
			}
			if ( l <= 0 ) {
				$obj.css("left", '10px');
			}
		}
		
		function binder($obj) {
		// all the binding is done here
		// event handlers have been abstracted out,
		// so they can handle mouse or key events
		
			// bindings for hc (hours container)
			if($obj.attr("id") == 'AP_arrcont') {
				$obj.mouseout(function(e) { arrcont_out(e) });
			}
			
			// bindings for $hd (hour divs)
			else if ($obj.attr("class") == 'AP_arr') {
				$obj.mouseover(function(e) { arrdiv_over($obj, e) });
				$obj.mouseout(function() { arrdiv_out($obj) });					
				$obj.click(function() {	arrdiv_click($obj) });
			}
		};
		
		function arrcont_out(e) {
			/*
			this tells divs to clear only if rolling all the way 
			out of arrcont.
			relatedTarget "looks ahead" to see where the mouse
			has moved to on mouseOut.
			IE uses the more sensible "toElement".
			try/catch for Mozilla bug on relatedTarget-input field.
			*/
			try {
				var t = (e.toElement) ? e.toElement : e.relatedTarget;
				if (!(jQuery(t).is("div[class^=AP], iframe"))) {
					// Safari incorrect mouseover/mouseout
					//if (!jQuery.browser.safari) {
						cleardivs();
					//}
				}	
			}
			catch(e) {
				cleardivs();
			}
		}
		
		function arrdiv_over($obj, e) {
			var h = $obj.attr("id").split('_')[1],
				i = $obj.attr("id").split('_')[2],
				l,
				t;
			$obj.addClass("AP_over");
			return false;
		}
		
		function arrdiv_out($obj) {
			$obj.removeClass("AP_over");
			return false;
		}
		
		function arrdiv_click($obj) {
			var h = $obj.attr("id").split('_')[1];
			setval(settings.arraydata[h].value,settings.arraydata[h].show,h);
			cleardivs();
		}
		
		function setval(avalue,ashow,ah) { 
			if(!settings.valuefield) {
				self.value = avalue;
			}
			else {
	//			jQuery("input[name=" + settings.valuefield + "]").val(avalue);
				jQuery("#" + settings.valuefield).val(avalue);
			}
			callback.apply( $self, [ avalue, ashow, ah ]);
		}
		
		function cleardivs() {
			$arrcont.slideUp('fast');
		}
		
	return false;
	});
	
	return this;

}

