// mygroupview.js
// Harubi-Front My Group View
// By Abdullah Daud, chelahmy@gmail.com
// 30 December 2022


var discussion_ref = '';
var latest_post_id = 0;
var ele_since_list = [];

var quote_btn_style = "--bs-btn-padding-y: .01rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;";

var append_post_input = function (text) {
	var new_post = $('#new_post').val();
	$('#new_post').val(new_post + text);
	$('#new_post').focus();
}

var ele_post = function (body, posted_by_username, created_utc) {
	
	var ele_posted_by = $("<a>", {
		"href" : "userview.html?name=" + posted_by_username,
		"text" : posted_by_username 
	});

	var ele_since = $("<span>", {
		"text" : created_utc > 0 ? since_phrase(created_utc) : ''
	});

	ele_since_list.push({ele: ele_since, utc: created_utc});
	
	var ele_quote = $("<button>", {
		class : "btn btn-secondary",
		style : quote_btn_style,
		html : "&ldquo; &rdquo;",
		click : function () {
			append_post_input("\"" + body + "\" - " + posted_by_username + "\n");
		}
	});
	
	var ele_header = $("<p>", {
		append : [
			ele_posted_by,
			$("<span>", {"html" : "&nbsp;"}),
			ele_since,
			$("<span>", {"html" : "&nbsp;"}),
			ele_quote
		]	
	});
	
	var ele_post = $("<div>", {
		class : "row",
		append : [
			ele_header,
			$("<pre>", {"text" : body}),
			$("<hr>")
		]
	});
	
	return ele_post;
}

var prepend_post = function (body, posted_by_username, created_utc) {
	
	var ele = ele_post(body, posted_by_username, created_utc);

	$("#posts").prepend(ele);
}

var append_post = function (body, posted_by_username, created_utc) {
	
	var ele = ele_post(body, posted_by_username, created_utc);

	$("#posts").append(ele);
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
					prepend_post(r[i].body, r[i].posted_by_username, r[i].created_utc);

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
		discussion_ref: discussion_ref, last_id: latest_post_id}, function (rst, extra) {
		
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					append_post(r[i].body, r[i].posted_by_username, r[i].created_utc);

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
	});
}

var post_new = function (discussion_ref, body) {
	qserv(main_server, {model: 'post', action: 'new',
		discussion_ref: discussion_ref, body: body}, function (rst, extra) {
		
		$('#new_post').val(''); // empty the post input box
		
		load_newer_posts(discussion_ref);
	});
}

var load_group = function (ref) {
	qserv(main_server, {model: 'usergroup', action: 'read_own',
		ref: ref}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];

			var ele_name = $("<a>", {
				"href" : "mygroupedit.html?groupref=" + ref,
				"text" : r.name
			});

			$("#groupname").html(ele_name.prop('outerHTML'));
			
			var ele_owner = $("<a>", {
				"href" : "userview.html?name=" + r.ownername,
				"text" : r.ownername
			});

			$("#ownername").html(ele_owner.prop('outerHTML'));

			discussion_ref = r.discussion_ref;
			load_posts(1, r.discussion_ref);
		}
	});
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("view_own_usergroups", function () {
		
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		
		var groupref = gup('groupref');
		
		if (groupref.length > 0) {
		
			load_group(groupref);

			$('#load_earlier_btn').click(function(){
				load_posts(0, discussion_ref);
			});

			$('#load_newer_btn').click(function(){
				load_newer_posts(discussion_ref);
			});

			$('#post_btn').click(function(){
				var body = $('#new_post').val();
				post_new(discussion_ref, body);
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
			setInterval(function () {
				load_newer_posts(discussion_ref);
			}, 10000);
		}
		else
			show_error();
	});
		
})

