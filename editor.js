/*
// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
function debounce(func, wait, immediate) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		var later = function() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
};

var updateTextarea = debounce(function(html) {
	$('textarea[name=body]').val(html);
}, 250);
*/

// Use summernote for the HTML WYSIWYG editor
$(document).ready(function() {
	
	// Progressive Enhancement
	$('textarea').hide();

	$('form').on('submit', function() {
		$('textarea[name="body"]').html($('.summernote').code());
	});

	$('.summernote').summernote({
		styleWithSpan: false,
		minHeight: 300, 
		/* Not working, switching to onSubmit event
		onkeyup: function(e) {
			updateTextarea($('.summernote').code());
		},
		onblur: function(e) {
			updateTextarea($('.summernote').code());
		},
		onChange: function(e) {
			updateTextarea($('.summernote').code());
		},*/
		// Fix Microsoft Word Pastes
		onpaste: function() {
			setTimeout(function() {
				var html = $(this).code();
				var newHtml = $('<div></div>').append(html)
					.find('*').removeAttr('style').removeAttr('lang').removeAttr('class').removeAttr('width').removeAttr('border').removeAttr('valign').end()
					.find('table').removeAttr('cellspacing').removeAttr('cellpadding').addClass('table').end()
					.html()
					.replace(/<o:p><\/o:p>/g, '')
					.replace(/<o:p>&nbsp;<\/o:p>/g, '');
				$(this).code('').code(newHtml);
			}, 10);
		},
		toolbar: [
			['style', ['style']],
			['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
			['insert', ['picture', 'link', 'video']],
			['para', ['ul', 'ol', 'paragraph', 'table']],
			['misc', ['undo', 'redo', 'fullscreen', 'codeview', 'help']],
		]
	});
});