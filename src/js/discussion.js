// discussion.js
// Harubi-Front Discussion
// By Abdullah Daud, chelahmy@gmail.com
// 4 January 2023

var attachment_max_file_size = "256M";
var attachment_max_files = 8;
var attachment_warnings = 0;
var attachment_count = 0;

var post_quote_id = 0;

var this_discussion_ref = '';
var latest_post_id = 0;
var ele_since_list = [];
var load_newer_posts_timer;

var video_player_count = 0;
var video_player_ids = [];

var ele_post_header = function (id, body, posted_by_username, created_utc) {

	var ele_posted_by = $("<a>", {
		"href" : app_rel_path + user_page_url + "?name=" + posted_by_username,
		"text" : posted_by_username 
	});

	var ele_posted_by_strong = $("<strong>", {
		append : [
			ele_posted_by
		]
	});

	var ele_since = $("<small>", {
		"text" : created_utc > 0 ? since_phrase(created_utc) : ''
	});

	ele_since_list.push({ele: ele_since, utc: created_utc});

	var ele_header = $("<p>", {
		append : [
			ele_posted_by_strong,
			$("<span>", {"html" : "&nbsp;&ndash;&nbsp;"}),
			ele_since
		]	
	});
	
	return ele_header;
}

var ele_post_footer = function (id) {
	var ele_like = $("<i>", {
		class : "home-icon bi-hand-thumbs-up-fill",
		style : "font-size: 1.25rem; cursor: pointer;",
		click : function () {

		}
	});
	
	var ele_reply = $("<i>", {
		class : "home-icon bi-reply-fill",
		style : "font-size: 1.5rem; cursor: pointer;",
		click : function () {
			post_quote_id = id;
			$("#quote_view").empty();
			$("#quote_view").append(ele_prepost(id));
			$('#new_post').focus();
		}
	});
	
	var ele_forward = $("<i>", {
		class : "home-icon bi-forward-fill",
		style : "font-size: 1.5rem; cursor: pointer;",
		click : function () {

		}
	});
	
	var ele_footer = $("<p>", {
		append : [
			ele_like,
			$("<span>", {"html" : "&nbsp;&nbsp;"}),
			ele_reply,
			$("<span>", {"html" : "&nbsp;"}),
			ele_forward
		]	
	});
	
	return ele_footer;
}

var ele_post_content = function (id, body, attachment) {

	var ele_video_attachment = $("<div>", {class: "row gy-2"});
	var ele_image_attachment = $("<div>", {class: "row gy-2"});
	var ele_file_attachment = $("<div>", {class: "row gy-2"});
	var ele_body = $("<div>", {class: "row gy-2"});
	
	var video_count = 0;
	var image_count = 0;
	var file_count = 0;
	
	try {
		var aobj = JSON.parse(attachment);
		var video_ext = ["mp4", "webm", "ogg"];
		var image_ext = ["png", "jpeg", "jpg", "gif"];
		for (var i in aobj) {
			if (aobj.hasOwnProperty(i)) {
				var attc = aobj[i];
				var src = main_server + "?model=post&action=get_attachment&discussion_ref=" + this_discussion_ref + "&repo=" + attc.repo;
				var ext = attc.fname.split('.').pop();
				
				if (video_ext.includes(ext)) {
				
					var ele_video_notice = $("<p>", {
						class : "vjs-no-js",
						append : [
							$("<span>", {"text" : t("To view this video please enable JavaScript, and consider upgrading to a web browser that @supports_HTML5_video",
								{"@supports_HTML5_video" : $("<a>", {href : "https://videojs.com/html5-video-support/", target : "_blank",
									text : t("supports HTML5 video")}).prop('outerHTML')})})
						]
					});
				
					var ele_video_source = $("<source>", {
						"src" : src,
						type : "video/" + ext
					});
					
					var player_id = "video-player-" + id + "-" + video_player_count;
					
					var ele_video = $("<video>", {
						"id" : player_id,
						class : "video-js img-responsive",
						style : "max-height: 100%; max-width: 100%;",
						append : [
							ele_video_source,
							ele_video_notice
						]
					});
										
					video_player_ids.push(player_id);
					
					var ele_video_div = $("<div>", {
						class : "col-lg-4 col-md-6 col-sm-12",
						append : [
							ele_video
						]
					});

					ele_video_attachment.append(ele_video_div);
					++video_count;
					++video_player_count;
				}
				else if (image_ext.includes(ext)) {
					var ele_image = $("<img>", {
						class : "img-responsive",
						style : "max-height: 100%; max-width: 100%; cursor: pointer;",
						"src" : src
					});

					var ele_image_div = $("<div>", {
						class : "col-lg-2 col-md-3 col-sm-4",
						append : [
							ele_image
						]
					});

					ele_image_attachment.append(ele_image_div);
					++image_count;
				}
				else {
					var ele_link = $("<a>", {
						href : src,
						text : attc.fname
					});
				
					var ele_link_p = $("<p>", {
						append : [
							ele_link
						]
					});
				
					ele_file_attachment.append(ele_link_p);
					++file_count;
				}
			}
		}
		
	} catch (e) {
		
	}
	
	var ele_content = $("<div>", {
		class : "row"
	});
	
	if (video_count > 0)
		ele_content.append(ele_video_attachment);
	
	if (image_count > 0) {
		new Viewer(ele_image_attachment[0]);
		ele_content.append(ele_image_attachment);
	}
	
	if (file_count > 0)
		ele_content.append(ele_file_attachment);
	
	ele_body.append($("<pre>", {"html" : body}));
	ele_content.append(ele_body);
	
	return ele_content;
}

var ele_prepost = function (quote_id, hr = false) {

	if (quote_id <= 0)
		return;

	var ele_prepost_block = $("<div>", {
		class : "row"
	});
	
	var ele_prepost_quote_icon = $("<i>", {
		class : "home-icon bi-quote",
		style : "font-size: 3rem;"
	});
	
	var ele_prepost_quote_icon_col = $("<div>", {
		class : "col-1",
		append : [
			ele_prepost_quote_icon
		]
	});
	
	var ele_prepost_quote_body_col = $("<div>", {
		class : "col"
	});

	if (hr)
		ele_prepost_quote_body_col.append($("<hr>", {style : "border-top: 1px dotted"}));
	
	// attach quote to post body
	load_ref_post(ele_prepost_quote_body_col, this_discussion_ref, quote_id);
	
	ele_prepost_block.append(ele_prepost_quote_icon_col);
	ele_prepost_block.append(ele_prepost_quote_body_col);

	return ele_prepost_block;
}

var split_post_body = function (body) {
	var quote_id = 0;
	var pos = body.indexOf("<<quote ");
	if (pos >= 0) {
		body = body.substring(pos + 8);
		pos = body.indexOf(">>");
		if (pos >= 0) {
			quote_id = parseInt(body.substring(0, pos));
			body = body.substring(pos + 2);
		}
	}
	return {body: body, quote_id: quote_id};
}

var tag_wrap = function (str, wrap, tag) {
	var tstr = "";
	while (str.length > 0) {
		var pos = str.indexOf(wrap);
		if (pos >= 0) {
			var left = str.substring(0, pos);
			var mid = str.substring(pos + wrap.length);
			pos = mid.indexOf(wrap);
			if (pos >= 0) {
				var right = mid.substring(pos + wrap.length);
				mid = mid.substring(0, pos);
				tstr += left + "<" + tag + ">" + mid + "</" + tag + ">";
				str = right;
				continue;
			}
		}
		tstr += str;
		break;
	}
	return tstr;
}

var ele_post = function (id, body, attachment, posted_by_username, created_utc) {
	
	var body_parts = split_post_body(body);
	body = body_parts.body;

	const options = {
		defaultProtocol: 'https',
		formatHref: {
			mention: (href) => app_rel_path + user_page_url + "?name=" + href.substr(1),
			hashtag: (href) => "https://twitter.com/hashtag/" + href.substr(1),
		}
	};
	
	body = linkifyStr(body, options);
	body = tag_wrap(body, "**", "strong");
	body = tag_wrap(body, "*", "em");
	
	var ele_prepost_block = ele_prepost(body_parts.quote_id, true);
	
	var ele_header = ele_post_header(id, body, posted_by_username, created_utc);
	var ele_footer = ele_post_footer(id);
		
	var ele_post = ele_post_content(id, body, attachment);

	if (typeof ele_prepost_block !== "undefined")
		ele_post.prepend(ele_prepost_block);
	
	ele_post.prepend(ele_header);
	ele_post.append(ele_footer);
	
	var ele_post_col = $("<div>", {
		class : "col",
		append : [
			ele_post
		]
	});
	
	var ele_post_block = $("<div>", {
		class : "row",
		append : [
			ele_post_col,
			$("<hr>")
		]
	});
	
	return ele_post_block;
}

var apply_video_players = function () {
	setTimeout(function () {
		for (var i in video_player_ids) {
			var id = video_player_ids[i];
			try {
				const options = {
					fluid: true,
					preload: "auto",
					autoplay: false,
					controls: true,
					//aspectRatio: "16:9",
					loop: false,
					// playVideo: false
				}

				videojs(id, options);
			}
			catch (e) {
				console.log(e);
			}
		}
		
		video_player_ids = [];
	}, 100);
}

var prepend_post = function (id, body, attachment, posted_by_username, created_utc) {
	
	var ele = ele_post(id, body, attachment, posted_by_username, created_utc);

	$("#posts").prepend(ele);
	apply_video_players();
}

var append_post = function (id, body, attachment, posted_by_username, created_utc) {
	
	var ele = ele_post(id, body, attachment, posted_by_username, created_utc);

	$("#posts").append(ele);
	apply_video_players();
}

var load_ref_post = function (host_ele, discussion_ref, id) {
	qserv(main_server, {model: 'post', action: 'read',
		discussion_ref: discussion_ref, id: id}, function (rst, extra) {
		
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			var body_parts = split_post_body(r.body);
			var ele = ele_post_content(id, body_parts.body, r.attachment);
			host_ele.prepend(ele);
			apply_video_players();
		}
	});
}

var load_posts = function (restart, discussion_ref) {
	if (restart) {
		$("#posts").empty();
		latest_post_id = 0;
	}
	
	qserv(main_server, {model: 'post', action: 'list',
		restart: restart, discussion_ref: discussion_ref}, function (rst, extra) {
		
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					prepend_post(r[i].id, r[i].body, r[i].attachment, r[i].posted_by_username, r[i].created_utc);

					if (restart && (r[i].id > latest_post_id))
						latest_post_id = r[i].id;
				}
			}
			
			if (rst.data.limit > rst.data.count)
				$('#load_earlier_btn').hide();
			else
				$('#load_earlier_btn').show();
		}
		else
			$('#load_earlier_btn').hide();
	});
}

var load_newer_posts = function (discussion_ref) {
	qserv(main_server, {model: 'post', action: 'list_newer',
		discussion_ref: discussion_ref, last_id: latest_post_id}, function (rst, extra) { // on_ready
		
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					append_post(r[i].id, r[i].body, r[i].attachment, r[i].posted_by_username, r[i].created_utc);

					if (r[i].id > latest_post_id)
						latest_post_id = r[i].id;
				}
			}
			
			if (rst.data.limit > rst.data.count)
				$('#load_newer_btn').hide();
			else
				$('#load_newer_btn').show();
		}
		else
			$('#load_newer_btn').hide();
	}, '', function () { // on_err
		if (typeof load_newer_posts_timer !== "undefined")
			clearInterval(load_newer_posts_timer);
	});
}

var post_new = function (discussion_ref, body) {
	qserv(main_server, {model: 'post', action: 'new',
		discussion_ref: discussion_ref, body: body}, function (rst, extra) {
		
		$('#new_post').val(''); // empty the post input box
		
		load_newer_posts(discussion_ref);
	});
}

var upload_attachment = function (discussion_ref) {
	if (attachment_warnings > 0) {
		if (attachment_warnings == 1)
			show_alert(t('Attachment has warning.'), 'warning');
		else
			show_alert(t('Attachment has warnings.'), 'warning');
			
		return;
	}
	
	var files = $("#files")[0].files;
	var totalfiles = files.length;
	
	if (totalfiles <= 0)
		return;
		
	var form_data = new FormData();
	
	form_data.append("model", "post");
	form_data.append("action", "attachment");
	form_data.append("discussion_ref", discussion_ref);

	// Read selected files
	for (var index = 0; index < totalfiles; index++) {
		form_data.append("files[]", files[index]);
	}
	
	$.ajax({
		url: main_server, 
		type: 'post',
		data: form_data,
		contentType: false,
		processData: false,
		
		// Custom XMLHttpRequest
		xhr: function () {
			var myXhr = $.ajaxSettings.xhr();
			if (myXhr.upload) {
				// For handling the progress of the upload
				$("#attachment_progress").show();
				myXhr.upload.addEventListener('progress', function (e) {
					if (e.lengthComputable) {
						$('#attachment_progress').attr({
							value: e.loaded,
							max: e.total,
						});
					}
				}, false);
			}
			return myXhr;
		},
		
		success: function (response) {
			console.log(response);
			handle_qserv_success(response, function (rst, extra) {
				if (rst.data.count > 0) {
					attachment_count = rst.data.count;
					var ele_strong_title = $("<strong>", {
						"text" : t("Attachment")
					});
					var ele_title = $("<p>", {
						append : [
							ele_strong_title
						]
					});
					$("#attachment_files").append(ele_title);
					var files = rst.data.files;
					for (var i in files) {
						if (files.hasOwnProperty(i)) {
							var ele_file = $("<p>", {
								"text" : files[i]['fname']
							});
							$("#attachment_files").append(ele_file);
						}
					}
				}
				$("#attachment_progress").hide();
			});
		}
	});
}

var init_discussion = function () {

	$('#load_earlier_btn').click(function(){
		load_posts(0, this_discussion_ref);
	});

	$('#load_newer_btn').click(function(){
		load_newer_posts(this_discussion_ref);
	});

	$('#post_btn').click(function(){
		var post = $('#new_post').val();
		post = $("<div>", {html: post}).text(); // strip html tags
		if (post.length > 0 || post_quote_id > 0 || attachment_count > 0) {
			if (post_quote_id > 0)
				post = "<<quote " + post_quote_id + ">>" + post;
			post_new(this_discussion_ref, post);
			post_quote_id = 0;
			$("#quote_view").empty();
			attachment_count = 0;
			$("#attachment_files").empty();
			$("#attachment_progress").hide();
		}
		else
			$('#new_post').focus();
	});

	// Update posts timestamps every second
	setInterval(function () {
		for (var i in ele_since_list) {
			if (ele_since_list.hasOwnProperty(i)) {
				var obj = ele_since_list[i];
				obj.ele.text(obj.utc > 0 ? since_phrase(obj.utc) : '');
			}
		}
	}, 1000);

	// Load new posts every 10 seconds
	load_newer_posts_timer = setInterval(function () {
		load_newer_posts(this_discussion_ref);
	}, 10000);
	
	load_preferences("attachment", function (data) {
		if (data.count > 0) {
			var val, r = data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					if (r[i].name == "attachment_max_file_size") {
						val = parseInt(r[i].value);
						if (val > 0)
							attachment_max_file_size = val;		
					}
					else if (r[i].name == "attachment_max_files") {
						val = parseInt(r[i].value);
						if (val > 0)
							attachment_max_files = val;		
					}
				}
			}
		}
	});
	
	$(':file').on('change', function () {
		attachment_warnings = 0;
		var totalfiles = this.files.length;
		if (totalfiles > attachment_max_files) {
			if (attachment_max_files == 1)
				show_alert(t('Max attachment is 1 file.'), 'warning');
			else
				show_alert(t('Max attachment is @files files.', {'@files': attachment_max_files}), 'warning');
			++attachment_warnings;
		}
		else {
			var amfsz = bytestr_to_num(attachment_max_file_size);
			for (var index = 0; index < totalfiles; index++) {
				var file = this.files[index];
				if (file.size > amfsz) {
					show_alert(t('Attachment max file size is @size bytes.', {'@size': amfsz}), 'warning');
					++attachment_warnings;
				}
				// Also see .name, .type
			}
		}
	});

	$('#attachment_btn').click(function(){
		
	});

	$('#attach_btn').click(function(){
		upload_attachment(this_discussion_ref);
	});
}



