// signin.js
// Harubi-Front Administration Sign In
// By Abdullah Daud, chelahmy@gmail.com
// 18 November 2022

var signin_user = function (name, password, remember_me) {
	$.post(admin_server, {model: 'user', action: 'signin',
		name: name, password: password, remember_me: remember_me}, function (rst, extra) {
		show_url("home.html");
	});
}

$(window).on('load', function () {

	load_logo();

	$(document).on("submit", "form", function(e){
		e.preventDefault();
		return  false;
	});

	load_signedin(function (data) {
		load_language(data.signedin_language, function () {
		var ref = gup('ref');
		
		if (ref.length > 0) {
			var refval = parseInt(ref);
			
			if (refval == 1)
				show_alert(t("Access denied."), "warning");
		}
		});
	});
	
	$('#signin_btn').click(function(){
		var name = $("#name").val();
		var password = $("#password").val();
		var remember_me = $("#remember-me").is(':checked');
		
		if (name.length > 0 && password.length > 0)
			signin_user(name, password, remember_me);
		else
			show_alert(t("Both name and password are required."), "warning");
	});
		
})

