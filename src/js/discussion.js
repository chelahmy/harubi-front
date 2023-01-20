// discussion.js
// Harubi-Front Discussion
// By Abdullah Daud, chelahmy@gmail.com
// 4 January 2023

var default_avatar = "libs/bootstrap-icons-1.10.2/person.svg";

var forward_page_url = "main/forward.html";
var forward_post_id = 0;
var forward_discussion_ref = '';

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
var video_player_dts = [];
var video_player = false;
var video_playing = false;

var map_viewer_count = 0;

var react_thumbs_up = 1;

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

var ele_post_footer = function (discussion_ref, id, own_post) {
	var ele_like = $("<i>", {
		id : "like_" + id,
		class : "post-icon bi-hand-thumbs-up-fill",
		click : function (e) {
			if (own_post != 1)
				post_react(discussion_ref, id, react_thumbs_up);
		}
	});
	
	var ele_like_count = $("<small>", {
		id : "like_count_" + id,
		style : own_post == 1 ? "cursor: pointer;" : "cursor: default;",
		text : "", // show nothing on zero count
		click : function (e) {
			if (own_post == 1) {
				if ($("#react_list_" + id).is(':empty'))
					load_react_list(1, discussion_ref, id, react_thumbs_up);
				else
					$("#react_list_" + id).empty();
			}
		}
	});
	
	var ele_reply = $("<i>", {
		class : "post-icon bi-reply-fill",
		click : function () {
			dispose_video_player(); // otherwise video won't be quoted
			post_quote_id = id;
			$("#quote_view").empty();
			$("#quote_view").append(ele_quote(id, discussion_ref));
			$('#new_post').focus();
		}
	});
	
	var ele_forward = $("<i>", {
		class : "post-icon bi-forward-fill",
		click : function () {
			
			qserv(main_server, {model: 'post', action: 'forward',
				discussion_ref: discussion_ref, id: id}, function (rst, extra) {

				var url = app_rel_path + forward_page_url;
				show_url(url);
				
			});
		}
	});
	
	var ele_footer = $("<p>", {
		append : [
			ele_like,
			$("<span>", {"html" : "&nbsp;"}),
			ele_like_count,
			$("<span>", {"html" : "&nbsp;&nbsp;"}),
			ele_reply,
			$("<span>", {"html" : "&nbsp;"}),
			ele_forward
		]	
	});

	var ele_react_list = $("<div>", {
		id : "react_list_" + id
	});
	
	var ele_footer_div = $("<div>", {
		append : [
			ele_footer,
			ele_react_list
		]	
	});
	
	return ele_footer_div;
}

// Note: The video player is blocking everything. Very bad coding approach.
//		 And disposing the player is taxing.
var dispose_video_player = function () {
	if (video_player === false)
		return;
		
	var vpid = video_player.id();
	// disposing video player will destroy its video element
	// so first get its video ele parent,...
	var p_ele = $("#" + vpid).parent()[0];
	// ...then dispose the player,...
	video_player.dispose();
	video_player = false;
	// ...and then recreate its video element back
	var dt = video_player_dts[vpid];
	var ele = ele_video_player(dt.id, dt.src, dt.ext, vpid);
	ele.click(on_video_click);
	$(p_ele).append(ele);
}

// See https://github.com/videojs/video.js/issues/7358
// Note: A video player with unfriendly api. This is just one of it.
//       It changes your defined id by appending it with string of stupidity.
var clean_video_player_id = function (id) {
	var pos = id.indexOf("-vp");
	
	if (pos < 0)
		return id;
		
	return id.substring(0, pos + 3);
}

var on_video_click = function (e) {
	var player_id = clean_video_player_id(e.target.id);
	try {
		if (video_player !== false) {
			var vpid = video_player.id();
			// dispose other player
			if (vpid != player_id)
				dispose_video_player();
		}
		
		if (video_player === false) {	
			const options = {
				fluid: true,
				preload: "auto",
				autoplay: true,
				controls: true,
				loop: false
			}
			
			// TODO: apply user language
			video_player = videojs(player_id, options);
			var close_btn = video_player.controlBar.addChild('button', {}, 0);
			close_btn.controlText("Close"); // videojs will do its own translation
			var close_btn_dom = close_btn.el();
			close_btn_dom.innerHTML = '<span class="vjs-icon-cancel"></span>';
			close_btn_dom.onclick = function () {
				dispose_video_player();
			};
			video_playing = false;
		}

		if (!video_playing) {
			video_player.play();
			video_playing = true;
		}
		else {
			video_player.pause();
			video_playing = false;
		}
	}
	catch (e) {
		console.log(e);
	}
}

var ele_video_player = function (id, src, ext, pid = 0) {
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
	
	var player_id = pid;
	
	if (player_id <= 0)
		player_id = "video-player-" + video_player_count++ + "-vp";
	
	var ele_video = $("<video>", {
		"id" : player_id,
		class : "video-js img-responsive",
		style : "max-height: 100%; max-width: 100%; cursor: pointer;",
		append : [
			ele_video_source,
			ele_video_notice
		]
	});

	video_player_dts[player_id] = {id: id, src: src, ext: ext};
	
	return ele_video;		
}

var ele_map_viewer = function (src) {
	var id = "map_" + map_viewer_count++;
	
	var ele_map = $("<div>", {
		id : id
	});
	
	$.getJSON(src, function (data) {
		show_map(data, id);
	});
	
	return ele_map;
}

var ele_post_content = function (discussion_ref, id, body, attachment) {

	var ele_map_attachment = $("<div>", {class: "row gy-2"});
	var ele_video_attachment = $("<div>", {class: "row gy-2"});
	var ele_image_attachment = $("<div>", {class: "row gy-2"});
	var ele_file_attachment = $("<div>", {class: "row gy-2"});
	var ele_body = $("<div>", {class: "row gy-2"});
	
	var map_count = 0;
	var video_count = 0;
	var image_count = 0;
	var file_count = 0;

	attachment = attachment.trim();
	
	if (attachment.length > 0) {
		try {
			var aobj = JSON.parse(attachment);
			var video_ext = ["mp4", "webm", "ogg"];
			var image_ext = ["png", "jpeg", "jpg", "gif"];
			for (var i in aobj) {
				if (aobj.hasOwnProperty(i)) {
					var attc = aobj[i];
					var src = main_server + "?model=post&action=get_attachment&discussion_ref=" + discussion_ref + "&repo=" + attc.repo;
					var ext = attc.fname.split('.').pop().toLowerCase();
					
					if (ext == "geojson") {
					
						var ele_map_div = $("<div>", {
							class : "col-lg-6 col-md-6 col-sm-12"
						});

						var ele_map = ele_map_viewer(src);
						ele_map_div.append(ele_map);
						ele_map_attachment.append(ele_map_div);
						++map_count;
					}
					else if (video_ext.includes(ext)) {
					
						var ele_video_div = $("<div>", {
							class : "col-lg-6 col-md-6 col-sm-12"
						});

						var ele_video = ele_video_player(id, src, ext);
						ele_video.click(on_video_click);
						ele_video_div.append(ele_video);
						ele_video_attachment.append(ele_video_div);
						++video_count;
					}
					else if (image_ext.includes(ext)) {
					
						var ele_image = $("<img>", {
							class : "img-responsive",
							style : "max-height: 100%; max-width: 100%; cursor: pointer;",
							"src" : src
						});

						var ele_image_div = $("<div>", {
							class : "col-lg-3 col-md-3 col-sm-4",
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
			console.log(e);
		}
	}
	
	var ele_content = $("<div>", {
		class : "row"
	});
	
	if (map_count > 0)
		ele_content.append(ele_map_attachment);
	
	if (video_count > 0)
		ele_content.append(ele_video_attachment);
	
	if (image_count > 0) {
		new Viewer(ele_image_attachment[0]);
		ele_content.append(ele_image_attachment);
	}
	
	if (file_count > 0)
		ele_content.append(ele_file_attachment);
	
	ele_body.append($("<pre>", {
		html : body
	}));
	
	ele_content.append(ele_body);
	
	return ele_content;
}

var ele_quote = function (quote_id, quote_discussion_ref, hr = false) {

	if (quote_id <= 0)
		return;

	var ele_block = $("<div>", {
		class : "row"
	});
	
	var ele_quote_icon = $("<i>", {
		class : "post-icon post-quote bi-quote"
	});
	
	var ele_quote_icon_col = $("<div>", {
		class : "col-1",
		append : [
			ele_quote_icon
		]
	});
	
	var ele_quote_body_col = $("<div>", {
		class : "col"
	});

	if (hr)
		ele_quote_body_col.append($("<hr>", {style : "border-top: 1px dotted"}));
	
	// attach quote to post body
	load_post_quote(ele_quote_body_col, quote_discussion_ref, quote_id);
	
	ele_block.append(ele_quote_icon_col);
	ele_block.append(ele_quote_body_col);

	return ele_block;
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

var ele_avatar = function (has_avatar, name) {
	var src = app_rel_path + default_avatar;
	
	if (has_avatar > 0)
		src = main_server + "?model=user&action=avatar&name=" + name;
	
	var ele_avatar = $("<img>", {
		class : "img-fluid",
		style : "height: auto; width: 100%; border-radius: 50%;",
		src : src
	});
	
	var ele_avatar_link = $("<a>", {
		"href" : app_rel_path + user_page_url + "?name=" + name,
		append : [
			ele_avatar
		]
	});
	
	return ele_avatar_link;
}

var ele_post = function (discussion_ref, id, body, attachment, quote_id, quote_discussion_ref, posted_by_username, posted_by_has_avatar, own_post, created_utc) {
	
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
	
	var ele_quote_block = ele_quote(quote_id, quote_discussion_ref, true);
	
	var ele_header = ele_post_header(id, body, posted_by_username, created_utc);
	var ele_footer = ele_post_footer(discussion_ref, id, own_post);
		
	var ele_post = ele_post_content(discussion_ref, id, body, attachment);

	if (typeof ele_quote_block !== "undefined")
		ele_post.prepend(ele_quote_block);
	
	ele_post.prepend(ele_header);
	ele_post.append(ele_footer);
	
	var ele_post_col = $("<div>", {
		class : "col-11",
		append : [
			ele_post
		]
	});
	
	var ele_post_avatar = ele_avatar(posted_by_has_avatar, posted_by_username);
	
	var ele_post_avatar_col = $("<div>", {
		class : "col-1",
		append : [
			ele_post_avatar
		]
	});
	
	var ele_post_block = $("<div>", {
		class : "row",
		append : [
			ele_post_avatar_col,
			ele_post_col,
			$("<hr>")
		]
	});
	
	return ele_post_block;
}

var prepend_post = function (discussion_ref, id, body, attachment, quote_id, quote_discussion_ref, posted_by_username, posted_by_has_avatar, own_post, created_utc) {
	
	var ele = ele_post(discussion_ref, id, body, attachment, quote_id, quote_discussion_ref, posted_by_username, posted_by_has_avatar, own_post, created_utc);

	$("#posts").prepend(ele);
}

var append_post = function (discussion_ref, id, body, attachment, quote_id, quote_discussion_ref, posted_by_username, posted_by_has_avatar, own_post, created_utc) {
	
	var ele = ele_post(discussion_ref, id, body, attachment, quote_id, quote_discussion_ref, posted_by_username, posted_by_has_avatar, own_post, created_utc);

	$("#posts").append(ele);
}

var load_post_quote = function (host_ele, discussion_ref, id) {
	if (id <= 0)
		return;
		
	if (discussion_ref.length <= 0)
		discussion_ref = this_discussion_ref;
		
	qserv(main_server, {model: 'post', action: 'read',
		discussion_ref: discussion_ref, id: id}, function (rst, extra) {
		
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			var ele = ele_post_content(discussion_ref, id, r.body, r.attachment);
			host_ele.prepend(ele);
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
					var id = r[i].id;
					prepend_post(discussion_ref, id, r[i].body, r[i].attachment,
						r[i].quote_id, r[i].quote_discussion_ref,
						r[i].posted_by_username, r[i].posted_by_has_avatar, r[i].own_post, r[i].created_utc);

					if (restart && (id > latest_post_id))
						latest_post_id = id;
					
					load_reacts(discussion_ref, id, react_thumbs_up);
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
					var id = r[i].id;
					append_post(discussion_ref, id, r[i].body, r[i].attachment,
						r[i].quote_id, r[i].quote_discussion_ref,
						r[i].posted_by_username, r[i].posted_by_has_avatar, r[i].own_post, r[i].created_utc);

					if (id > latest_post_id)
						latest_post_id = id;

					load_reacts(discussion_ref, id, react_thumbs_up);
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

var post_new = function (discussion_ref, body, quote_discussion_ref, quote_id) {
	qserv(main_server, {model: 'post', action: 'new',
		discussion_ref: discussion_ref, body: body, quote_discussion_ref: quote_discussion_ref, quote_id: quote_id}, function (rst, extra) {
		
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

var load_forward_post = function () {
	qserv(main_server, {model: 'post', action: 'get_forward'}, function(rst, extra) {
		forward_post_id = rst.data.forward_post_id;
		forward_discussion_ref = rst.data.discussion_ref;
		dispose_video_player(); // otherwise a running video player will block from showing quoted video
		$("#quote_view").empty();
		$("#quote_view").append(ele_quote(forward_post_id, forward_discussion_ref));
		$('#new_post').focus();
	});
}

var load_reacts = function (discussion_ref, id, type) {
	$("#like_" + id).removeClass("post-icon-select");
				
	qserv(main_server, {model: 'post', action: 'count_reacts',
		discussion_ref: discussion_ref, id: id, type: type}, function(rst, extra) {
		
		if (rst.data.count > 0) {
			$("#like_count_" + id).text(rst.data.count);
		
			qserv(main_server, {model: 'post', action: 'get_react',
				discussion_ref: discussion_ref, id: id}, function(rst2, extra2) {
				if (rst2.data.count > 0) {
					var r = rst2.data.records[0];

					if ((r.type & react_thumbs_up) > 0)
						$("#like_" + id).addClass("post-icon-select");
				}
			});
		}
		else
			$("#like_count_" + id).text(""); // show nothing on zero count
	});
}

var post_react = function (discussion_ref, id, type) {
	qserv(main_server, {model: 'post', action: 'react',
		discussion_ref: discussion_ref, id: id, type: type}, function(rst, extra) {
		if (rst.data.own != 1)
			load_reacts(discussion_ref, id, type);
	});
}

var load_react_list = function (restart, discussion_ref, id, type) {
	var rlid = "#react_list_" + id;
	var ele_rl = $(rlid);
	var ele, ele_more;
	
	if (restart) {
		ele_rl.empty();
	}
	
	qserv(main_server, {model: 'post', action: 'list_reacts',
		restart: restart, discussion_ref: discussion_ref, id: id, type: type}, function(rst, extra) {

		if (rst.data.count > 0) {
			if (restart)
				ele_rl.append([$("<div>"), $("<br>")]);
				
			ele = ele_rl.find(">:first-child");
			
			if (ele.is(':empty')) {
				// add "load more" icon
				ele_more = $("<i>", {
					class : "post-icon bi-three-dots",
					click : function (e) {
						load_react_list(0, discussion_ref, id, type);
					}
				});
				
				ele.append(ele_more);		
			}
			else
				ele_more = ele.find(">:last-child");
						
			var r = rst.data.records;
			
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var username = r[i].username;

					var ele_user = $("<a>", {
						"href" : app_rel_path + user_page_url + "?name=" + username,
						"text" : username 
					});
					
					ele_user.insertBefore(ele_more);
					$("<span>", {"html" : "&nbsp;"}).insertAfter(ele_user);
				}
			}
			
			if (rst.data.count < rst.data.limit)
				ele_more.remove(); // remove the "load more" icon
		}
		else {
			ele = ele_rl.find(">:first-child");
			
			if (!ele.is(':empty'))
				ele.find(">:last-child").remove(); // remove the "load more" icon
		}
	});
}

var insert_text = function (ele_input, text) {
	var pos = ele_input.prop('selectionStart');
	var v = ele_input.val();
	var t_before = v.substring(0,  pos);
	var t_after  = v.substring(pos, v.length);

	ele_input.val(t_before + text + t_after);
}

var add_emoticons = function (index = 0) {
	var len = 1;

	if (index > 0)
		len = 16;

	var ele_post = $("#new_post");
	var ele_emoji_block;

	if ($("#post_emoji").length == 0) {
		ele_emoji_block = $("<p>", {
			id : "post_emoji"
		});
		
		ele_emoji_block.insertBefore(ele_post);
	}
	else
		ele_emoji_block = $("#post_emoji");
	
	var i, uc = parseInt("1F600", 16);
	
	for (i = index; i < (index + len) && i < 80; i++) {
		var emo = "&#" + (uc + i) + ";";
		var ele_emo = $("<span>", {
			style : "margin: 5px; cursor: pointer;",
			html : emo,
			click : function (e) {
				insert_text(ele_post, $(this).text());
			}
		});
		
		ele_emoji_block.append(ele_emo);
	}
	
	if (i < 80) {
		var ele_more = $("<span>", {
			style : "cursor: pointer;",
			text : "...",
			click : function (e) {
				add_emoticons(index + len);
				$(this).remove();
			}
		});
	
		ele_emoji_block.append(ele_more);
	}
}

var init_discussion = function () {

	add_emoticons();
	
	$('#load_earlier_btn').click(function(){
		load_posts(0, this_discussion_ref);
	});

	$('#load_newer_btn').click(function(){
		load_newer_posts(this_discussion_ref);
	});

	$('#post_btn').click(function(){
		var post = $('#new_post').val();
		post = $("<div>", {html: post}).text(); // strip html tags in post
		if (post.length > 0 || post_quote_id > 0 || attachment_count > 0) {
			post_new(this_discussion_ref, post, this_discussion_ref, post_quote_id);
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
				show_alert(t('Only 1 attachment file is allowed.'), 'warning');
			else
				show_alert(t('Max @files files for attachment.', {'@files': attachment_max_files}), 'warning');
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

	$(window).on("unload", function(){
		dispose_video_player();
	});		
}



