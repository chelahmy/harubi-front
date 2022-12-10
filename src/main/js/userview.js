// userview.js
// Harubi-Front My Profile
// By Abdullah Daud, chelahmy@gmail.com
// 10 December 2022

var load_user_profile = function (name) {
	qserv(main_server, {model: 'user', action: 'view_profile',
		name: name}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			$('#username').text(r.name);
			$("#membership").text("Member since" + " " + since_phrase(r.created_utc));
		}
	});
}

$(window).on('load', function () {

	load_logo();
	
	when_allowed("view_user_profile", function () {
		
		show_page();
		
		var name = gup('name');
		
		if (name.length > 0)				
			load_user_profile(name);
		else
			show_error();
	});
		
})

