// signup.js
// Harubi-Front Sign Up
// By Abdullah Daud, chelahmy@gmail.com
// 25 April 2023

var signup_user = function (name, password, email) {
	qserv(main_server, {model: 'user', action: 'signup',
		name: name, password: password, email: email}, function (rst, extra) {
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
		 
	$('#signup_btn').click(function(){
		var name = $("#name").val();
		var password = $("#password").val();
		var email = $("#email").val();
		var remember_me = $("#remember-me").is(':checked');

		if (name.length > 0 && password.length > 0 && email.length > 0) {
			if ($("#confirm-password-input").hasClass("d-none")) {
				$("#confirm-password-input").removeClass("d-none");
				show_alert(t("Please confirm password."));
				$("#confirm-password").focus()
			}
			else {
				var confirm_password = $("#confirm-password").val();
				if (confirm_password != password) {
					show_alert(t("Password mismatched."), "warning");
					$("#confirm-password").focus()					
				}
				else {
					signup_user(name, password, email);
				}
			}
		}
		else
			show_alert(t("User name, password and email address are required."), "warning");
	});
		
})

