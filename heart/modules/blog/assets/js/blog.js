(function($) {
	$(function(){

		/* ========== Start of Search Function List ============== */

		// Ajax Search Function
		function ajaxSearch(form, url) {
			$.ajax({
				url: url,
				type: 'POST',
				data: $(form).serialize(),
				beforeSend: function() {
					$('#filter-form').append('<span id="waiting" class="loading"></span>');
				},
				complete: function() {
					$('#waiting').remove();
				},
				error: function(jqXHR, textStatus, errorThrown) {
					$('#waiting').remove();
					$('#filter-form').append('<span id="result-error">'+errorThrown+'</span>');
				},
				success: function(data) {
					$('#data_table_wrapper').html(data);
				}
			});
		};

		var timeoutReference;
		$('#filter-box').on('keyup', function(e){
			var form = $('#filter-form');
			var url = form.attr('action');
			if (timeoutReference) clearTimeout(timeoutReference);
			timeoutReference = setTimeout(function() {
				ajaxSearch(form, url);
			}, 500);
		});

		$('#fiter-form').submit(function(e){
			e.preventDefault();
		});

		/* ========== End of Search Function List ============== */

		//check slug
		$('#form_slug, #form_title').bind('blur',function(){
			var slug = $('#form_slug').val();
			var post_data = $('#blog_form').serialize();
			$.post(SITEURL+ADMIN+'/blog/check-slug',post_data,function(data){
				$('#slug_error').html(data);
			});
		});

		// Post Auto Save Function
		function postAutoSave() {
			$.ajax({
				url: SITEURL+ADMIN+"/blog/autosave",
				type: 'POST',
				data:$('.blog_form').serialize(),
				success: function(data){
					var result = data;
					if(result.status != 'no_save') {
						$('#post_id').val(result.post_id);
						$('#autosave-msg').html(result.time);
					}
				}
			});
		};

		var timeout;

		if(typeof CKEDITOR !== 'undefined') {
			CKEDITOR.on('instanceCreated', function (e) {
				e.editor.on('change', function (ev) {
					if (timeout) clearTimeout(timeout);
					timeout = setTimeout(function(){
						postAutoSave();
					}, 30000);
				});
			});
		};

		$('#blog_form input, #blog_form textarea, #blog_form select').change(function(){
			if (timeout) clearTimeout(timeout);
			timeout = setTimeout(function () {
				postAutoSave();
			}, 30000);
		});

		// Autosave at 1 min
		/*if(document.getElementById('blog-create') || document.getElementById('blog-edit'))
		{
			setInterval(function () {
				postAutoSave();
			}, 60000);
		}*/

		// Tag Input
		if(document.getElementById('tags')) {
			$('#tags').tagsInput({
				width:'auto',
				autocomplete_url: SITEURL + ADMIN + '/tag/autocomplete'
			});
		}

		// Date Picker
		if(document.getElementById('datepicker')) {
			$( "#datepicker" ).datetimepicker({
				'dateFormat' : 'yy-mm-dd',
				'timeFormat': 'hh:mm tt'
			});
		}

		// Show hide Schedule Auto Manual
		var manual = $('#sch_type_manual');
		var auto = $('#sch_type_auto');
		$(manual).on('click', function(){
			if(manual.attr('checked', 'checked')) {
				$('div#manual-sch').fadeIn('fast').css('display', 'inline-block');
			}
		});
		$(auto).on('click', function(){
			$('div#manual-sch').fadeOut('fast');
		});

		// Featured Image Function
		$('#featured_add_btn').colorbox({
			scrollable: false,
			innerWidth: "70%",
			opacity: 0.5,
			onComplete : function() {
				$.colorbox.resize();
			},
			onClosed : function() {
				$('.media_file_info').die('click');
			}
		});

		// Remove Featured Image
		$('#featured_remove_btn').on('click', function(){
			$('#featured_preview img').remove();
			var val = '';
			$('#featured_id').val(val);
			$(this).hide();
			$('#featured_add_btn').show();
		});

		// Category Page Section ========================
		$('a.c-edit-box').colorbox({
			scrollable: false,
			innerWidth: 600,
			innerHeight: 280,
			onComplete: function() {

				$.colorbox.resize();

				$('form#blog-cat-edit').on('submit', function(e) {

					var form_data = $(this).serialize();

					var form_url = $(this).attr('action');

					$.post(form_url, form_data, function(obj){
						var data = jQuery.parseJSON(obj);
						var msg_box = $('#edit-msg');
						if(data.status == 'ok') {
							$(msg_box).html(data.success);
							$(msg_box).show();
							$(msg_box).addClass('msg-success');
							$.colorbox.resize();
							if ($('#category_select').length > 0) {
								$.post(SITEURL + ADMIN + '/blog/category/getCategory/' + data.saveID,function(data){
									$('#category_select').html(data);
								});
								console.log(data);
								$('#category_select').val(data.saveID);
							} else {
								window.location.reload();
							}
							$.colorbox.close();
						} else if (data.status == 'invalid') {
							$(msg_box).html(data.name + '<br/>' + data.slug);
							$(msg_box).show();
							$(msg_box).addClass('msg-error');
							$.colorbox.resize();
						} else {
							$(msg_box).html(data.error);
							$(msg_box).show();
							$(msg_box).addClass('msg-error');
							$.colorbox.resize();
						}
					});

					e.preventDefault();
				});
			}
		});

		/* Language Select Box */
		if (typeof $().chosen !== 'undefined') {
			$('#change_lang').chosen({width: "95%"});
		};

		// Select option selected by value
		function selectItemByValue(elmnt, value){

			for(var i=0; i < elmnt.options.length; i++)
			{
				if(elmnt.options[i].value == value)
				elmnt.selectedIndex = i;
			}
		}

	});
})(jQuery);
