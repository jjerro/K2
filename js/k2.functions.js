jQuery.noConflict();

if (typeof K2 == 'undefined') var K2 = {};

K2.debug = false;

// 
//K2.prototype.ajaxComplete = [];

K2.ajaxGet = function(data, complete_fn) {
	jQuery.ajax({
		url:		K2.AjaxURL,
		data:		data,

		error: function(request) {
			jQuery('#notices')
				.show()
				.append('<p class="alert">Error ' + request.status + ': ' + request.statusText + '</p>');
		},

		success: function() {
			jQuery('#notices').hide().html();
		},

		complete: function(request) {

			// Disable obtrusive document.write
			document.write = function(str) {};

			if ( complete_fn ) {
				complete_fn( request.responseText );
			}

			// Lightbox v2.03.3 - Adds new images to lightbox
			if (typeof myLightbox != "undefined" && myLightbox instanceof Lightbox && myLightbox.updateImageList) {
				myLightbox.updateImageList();
			}

			/*
			if ( K2.callbacks && K2.callbacks.length > 0 ) { 
				for ( var i = 0; i < K2.callbacks.length; i++ ) {
					K2.callbacks[i]();
				}
			 }
			*/

			// Inform JAWS
			updateBuffer();
		}
	});
}

function OnLoadUtils() {
	jQuery('#comment-personaldetails').hide();
	jQuery('#showinfo').show();
	jQuery('#hideinfo').hide();
};

function ShowUtils() {
	jQuery('#comment-personaldetails').slideDown();
	jQuery('#showinfo').hide();
	jQuery('#hideinfo').show();
};

function HideUtils() {
	jQuery('#comment-personaldetails').slideUp();
	jQuery('#showinfo').show();
	jQuery('#hideinfo').hide();
};


/* Fix the position of an element when it is about to be scrolled off-screen */
function smartPosition(obj) {
	if ( jQuery.browser.msie && parseInt(jQuery.browser.version, 10) < 7 ) {
		return;
	}
	
	jQuery(window).scroll(function() {
		// Detect if content is being scroll offscreen.
		if ( (document.documentElement.scrollTop || document.body.scrollTop) >= jQuery(obj).offset().top) {
			jQuery('body').addClass('smartposition');
		} else {
			jQuery('body').removeClass('smartposition');
		}
	});
};


// Set the number of columns based on window size
function dynamicColumns() {
	var window_width = jQuery(window).width();

	if ( window_width >= (K2.layoutWidths[2] + 20) ) {
		jQuery('body').removeClass('columns-one columns-two').addClass('columns-three');
	} else if ( window_width >= (K2.layoutWidths[1] + 20) ) {
		jQuery('body').removeClass('columns-one columns-three').addClass('columns-two');
	} else {
		jQuery('body').removeClass('columns-two columns-three').addClass('columns-one');
	}
};

/*
	Improving Ajax applications for JAWS users
	http://juicystudio.com/article/improving-ajax-applications-for-jaws-users.php
*/

function prepareBuffer() {
	var objNew = document.createElement('p');
	var objHidden = document.createElement('input');

	objHidden.setAttribute('type', 'hidden');
	objHidden.setAttribute('value', '1');
	objHidden.setAttribute('id', 'virtualbufferupdate');
	objHidden.setAttribute('name', 'virtualbufferupdate');

	objNew.appendChild(objHidden);
	document.body.appendChild(objNew);
};

function updateBuffer() {
	var objHidden = document.getElementById('virtualbufferupdate');

	if (objHidden) {
		if (objHidden.getAttribute('value') == '1')
			objHidden.setAttribute('value', '0');
		else
			objHidden.setAttribute('value', '1');
	}
};

jQuery(document).ready(function(){
	prepareBuffer();
});




function initOverLabels () {
	if (!document.getElementById) return;

	var labels, id, field;

	// Set focus and blur handlers to hide and show 
	// labels with 'overlabel' class names.
	labels = document.getElementsByTagName('label');
	for (var i = 0; i < labels.length; i++) {

		if (labels[i].className == 'overlabel') {

			// Skip labels that do not have a named association
			// with another field.
			id = labels[i].htmlFor || labels[i].getAttribute('for');
			if (!id || !(field = document.getElementById(id))) {
				continue;
			} 

			// Change the applied class to hover the label 
			// over the form field.
			labels[i].className = 'overlabel-apply';

			// Hide any fields having an initial value.
			if (field.value !== '') {
				hideLabel(field.getAttribute('id'), true);
			}

			// Set handlers to show and hide labels.
			field.onfocus = function () {
				hideLabel(this.getAttribute('id'), true);
			};
			field.onblur = function () {
				if (this.value === '') {
					hideLabel(this.getAttribute('id'), false);
				}
			};

			// Handle clicks to label elements (for Safari).
			labels[i].onclick = function () {
				var id, field;
				id = this.getAttribute('for');
				if (id && (field = document.getElementById(id))) {
					field.focus();
				}
			};

		}
	}
};

function hideLabel(field_id, hide) {
	var field_for;
	var labels = document.getElementsByTagName('label');
	for (var i = 0; i < labels.length; i++) {
		field_for = labels[i].htmlFor || labels[i].getAttribute('for');
		
		if (field_for == field_id) {
			labels[i].style.textIndent = (hide) ? '-1000px' : '0px';

			return true;
		}
	}
};

/*
jQuery('.attachment-image').ready(function(){
	resizeImage('.image-link img', '#page', 20);
});

jQuery(window).resize(function(){
	resizeImage('.image-link img', '#page', 20);
});


function resizeImage(image, container, padding) {
	var imageObj = jQuery(image);
	var containerObj = jQuery(container);

	var imgWidth = imageObj.width();
	var imgHeight = imageObj.height();
	var contentWidth = containerObj.width() - padding;

	var ratio = contentWidth / imgWidth;

	imageObj.width(contentWidth).height(imgHeight * ratio);
	console.log('resized to a ratio of ' + ratio);
}
*/