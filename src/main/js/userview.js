// userview.js
// Harubi-Front My Profile
// By Abdullah Daud, chelahmy@gmail.com
// 10 December 2022

forward_page_url = "main/forward.html";

var load_user_profile = function (name) {
	qserv(main_server, {model: 'user', action: 'view_profile',
		name: name}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			$('#username').text(r.name);
			$("#membership").text(t("Member since @ago", {'@ago' : since_phrase(r.created_utc)}));
			
			if (r.has_avatar > 0) {
				$("#avatar").prop("src", main_server + "?model=user&action=avatar&name=" + r.name);
			}

			this_discussion_ref = r.discussion_ref;
			load_posts(1, r.discussion_ref);
		}
	});
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("view_user_profile", function () {
		
		prevent_form_submit();
		
		show_page();
		
		var name = gup('name');
		
		if (name.length > 0) {			
			load_user_profile(name);
			init_discussion();
		}
		else
			show_error();
	});
		
})

