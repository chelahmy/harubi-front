// myprofile.js
// Harubi-Front My Profile
// By Abdullah Daud, chelahmy@gmail.com
// 7 December 2022

var user = {name:'',email:'',rolename:'',language:''};

var input_has_changed = function () {
	if ($("#email").val() != user.email)
		return true;

	if ($("#password").val().length > 0)
		return true;
		
	if ($("#language").val() != user.language)
		return true;

	return false;
}

var load_user = function () {
	user = {name:'',email:'',rolename:''};
	qserv(main_server, {model: 'user', action: 'read_own'}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			$("#username").text(r.name);
			$("#name").val(r.name);
			$("#email").val(r.email);
			$("#language").val(r.language);
			$("#rolename").text(r.rolename);
			$("#membership").text("Member since" + " " + since_phrase(r.created_utc));
			user.name = r.name;
			user.email = r.email;
			user.rolename = r.rolename;
			user.language = r.language;
		}
	});
}

var update_user = function (password, email, language) {
	qserv(main_server, {model: 'user', action: 'update_own',
		password: password, email: email, language: language}, alert_after_update);
}

$(window).on('load', function () {

	load_logo();
	
	when_allowed("edit_own_profile", function () {
		
		show_page();
		
		show_update_button();
		
		load_user();

		$('#update_btn').click(function(){
			if (input_has_changed()) {
				var password = $("#password").val();
				var re_password = $("#re_password").val();
				
				if (password.length > 0 && password != re_password)
					show_alert("Passwords mismatched", "warning");
				else {
					var email = $("#email").val();
					var language = $("#language").val();
					
					if (email.length <= 0)
						show_alert("Email cannot be empty", "warning");
					else if (language.length <= 0)
						show_alert("Language cannot be empty", "warning");
					else
						update_user(password, email, language);
				}
			}
			else
				show_alert("No change", "info");
		});
	});
		
})

