// myprofile.js
// Harubi-Front My Profile
// By Abdullah Daud, chelahmy@gmail.com
// 7 December 2022

const avatar_max_filesize = 256; // kb

var input_avatar_filename = '';
var input_avatar_filesize = 0;
var user = {has_avatar:0,name:'',email:'',rolename:'',language:''};

var input_has_changed = function () {

	if (user.has_avatar > 0 && $("#remove_avatar").prop('checked'))
		return true;
	
	if (input_avatar_filename.length > 0)
		return true;
		
	if ($("#email").val() != user.email)
		return true;

	if ($("#password").val().length > 0)
		return true;
		
	if ($("#language").val() != user.language)
		return true;

	return false;
}

var set_avatar = function (has_avatar, name) {
	var src = app_rel_path + default_avatar;
	
	if (has_avatar > 0) {
		src = main_server + "?model=user&action=avatar&name=" + name;
		$("#remove_avatar_block").removeClass("d-none");
	}
	else
		$("#remove_avatar_block").addClass("d-none");
	
	var ele_avatar = $("<img>", {
		class : "img-fluid",
		style : "height: auto; width: 100%;",
		src : src
	});
	
	var ele_avatar_div = $("<div>", {
		class : "col col-lg-3 col-md-3 col-sm-4",
		append : [
			ele_avatar
		]
	});
	
	$("#avatar_image").empty();
	$("#avatar_image").append(ele_avatar_div);
}

var load_user = function () {
	user = {has_avatar:0,name:'',email:'',rolename:''};
	qserv(main_server, {model: 'user', action: 'read_own'}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			
			var ele_uname = $("<a>", {
				"href" : "userview.html?name=" + r.name,
				"text" : r.name 
			});
			
			$('#username').html(ele_uname.prop("outerHTML"));
			$("#name").val(r.name);
			$("#email").val(r.email);
			$("#language").val(r.language);
			$("#rolename").text(r.rolename);
			$("#membership").text(t("Member since @ago", {'@ago' : since_phrase(r.created_utc)}));
			user.has_avatar = r.has_avatar;
			user.name = r.name;
			user.email = r.email;
			user.rolename = r.rolename;
			user.language = r.language;
		
			set_avatar(r.has_avatar, r.name);			
		}
	});
}
/*
var update_user = function (password, email, language) {
	qserv(main_server, {model: 'user', action: 'update_own', // alert_after_update
		password: password, email: email, language: language}, function (rst, extra) {
			
			if (language != user.language)
				window.location.reload();
				
			alert_after_update(rst, extra);
		}); 
}
*/
var update_user = function (password, email, language, remove_avatar) {
	
	var form_data = new FormData();
	
	form_data.append("model", "user");
	form_data.append("action", "update_own");
	form_data.append("password", password);
	form_data.append("email", email);
	form_data.append("language", language);
	form_data.append("remove_avatar", remove_avatar);

	var file = $("#avatar")[0].files[0];
	form_data.append("avatar", file);
	
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
				$("#upload_progress").show();
				myXhr.upload.addEventListener('progress', function (e) {
					if (e.lengthComputable) {
						$('#upload_progress').attr({
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
			
				if (language != user.language)
					window.location.reload();
					
				alert_after_update(rst, extra);

				if (remove_avatar > 0)
					set_avatar(0, name);

				$("#upload_progress").hide();
			});
		}
	});
}


$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	prevent_form_submit();
	
	when_allowed("edit_own_profile", function () {
		
		show_page();
		
		show_view_button();
		show_update_button();
		
		load_user();
		
		count_unread_post(1, "myprofile", function (discussion_typeref, count) {
			$("#username-counter").text(count);
		});

		$("#avatar").on("change", function (e) {
			input_avatar_filename = this.files[0].name;
			input_avatar_filesize = this.files[0].size;
			$("#avatar_filename").text(input_avatar_filename);
		});

		$('#view_btn').click(function(){
			show_url("userview.html?name=" + user.name);
		});

		$('#update_btn').click(function(){
			if (input_has_changed()) {
				
				if (input_avatar_filename.length > 0 && input_avatar_filesize > (avatar_max_filesize * 1024))
					show_alert(t("Avatar file size is too large, more than @size kb.", {"@size" : avatar_max_filesize}), "warning");
				else {
					var password = $("#password").val();
					var re_password = $("#re_password").val();
					
					if (password.length > 0 && password != re_password)
						show_alert(t("Passwords mismatched."), "warning");
					else {
						var email = $("#email").val();
						var language = $("#language").val();
						var remove_avatar = 0;
						
						if (user.has_avatar > 0 && $("#remove_avatar").prop('checked'))
							remove_avatar = 1;
										
						if (email.length <= 0)
							show_alert(t("Email cannot be empty."), "warning");
						else if (language.length <= 0)
							show_alert(t("Language cannot be empty."), "warning");
						else
							update_user(password, email, language, remove_avatar);
					}
				}
			}
			else
				show_alert("Nothing changed.", "info");
		});
	});
		
})

