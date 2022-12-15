// home.js
// Harubi-Front Home Page
// By Abdullah Daud, chelahmy@gmail.com
// 18 November 2022

var signout_user = function () {
	qserv(main_server, {model: 'user', action: 'signout'}, function (rst, extra) {
		show_url("home.html?ref=2");
	});
}

var load_signedin = function () {
	qserv(main_server, {model: 'system', action: 'signedin'}, function (rst, extra) {
		var r = rst.data;
		if (r.is_signedin) {
			$('#username').text(r.signedin_uname);
		}
	});
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	var signin_params = '?ref=1'; // with message: access denied
	var ref = gup('ref');
	
	if (ref.length > 0) {
		var refval = parseInt(ref);
		
		if (refval == 2) // signed out: see admin_signout_user()
			signin_params = ''; // do not show the access denied message
	}
	
	when_allowed("view_main", function () {
	
		show_page();
		load_signedin();
		
		add_home_menu_item("person", t("Profile"), t("My profile."), "myprofile.html");
		add_home_menu_item("people", t("Groups"), t("My private user groups."), "mygroup.html");

		$('#signout_btn').click(function(){
			signout_user();
		})

	}, "signin.html" + signin_params);

})

