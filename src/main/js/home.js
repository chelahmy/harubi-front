// home.js
// Harubi-Front Home Page
// By Abdullah Daud, chelahmy@gmail.com
// 18 November 2022

var is_signedin = false;
var signedin_uname = '';

var signout_user = function () {
	qserv(main_server, {model: 'user', action: 'signout'}, function (rst, extra) {
		show_url("home.html?ref=2");
	});
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
		is_signedin = data.is_signedin;
		signedin_uname = data.signedin_uname;
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

		if (is_signedin)
			$('#username').text(signedin_uname);
	
		load_home_menu('main');

		$('#signout_btn').click(function(){
			signout_user();
		})

	}, "signin.html" + signin_params);

})

