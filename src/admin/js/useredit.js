// useredit.js
// Harubi-Front User Edit
// By Abdullah Daud, chelahmy@gmail.com
// 14 November 2022

var roles_loaded = false;
var roles = [];
var user = {has_avatar:0,name:'',email:'',rolename:''};

var get_selected_role = function () {
	for (var i in roles) {
		var name = roles[i];
		if ($("#role_" + name).prop('checked'))
			return name;
	}
	return '';
}

var append_role = function (name) {

	var ele_input = $("<input>", {
		class : "form-check-input",
		type : "radio",
		name : "flexRadioDefault",
		id : "role_" + name
	});

	var ele_label = $("<label>", {
		class : "form-check-label",
		for : "role_" + name,
		text : name
	});
	
	var ele_role = $("<div>", {
		class : "form-check",
		append : [
			ele_input,
			ele_label
		]
	});

	$("#roles").append(ele_role);
}

var load_roles = function () {
	roles = [];
	qserv(admin_server, {model: 'role', action: 'listall'}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var name = r[i].name;
					
					append_role(name);
					roles.push(name);
				}
			}
		}
		
		roles_loaded = true;
	});
}

var input_has_changed = function () {

	if (user.has_avatar > 0 && $("#remove_avatar").prop('checked'))
		return true;
	
	if ($("#name").val() != user.name)
		return true;

	if ($("#email").val() != user.email)
		return true;

	if (get_selected_role() != user.rolename)
		return true;
		
	if ($("#password").val().length > 0)
		return true;
		
	return false;
}

var load_user = function (name) {
	user = {has_avatar:0,name:'',email:'',rolename:''};
	qserv(admin_server, {model: 'user', action: 'read',
		name: name}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			$("#username").text(r.name);
			$("#name").val(r.name);
			$("#email").val(r.email);
			$("#role_" + r.rolename).prop('checked', true);
			$("#membership").text(t("Member since @ago", {'@ago' : since_phrase(r.created_utc)}));
			user.has_avatar = r.has_avatar;
			user.name = r.name;
			user.email = r.email;
			user.rolename = r.rolename;

			var src = "../libs/bootstrap-icons-1.10.2/person.svg";
			var style = "width: 150px; height: 150px;";
			
			if (r.has_avatar > 0) {
				src = main_server + "?model=user&action=avatar&name=" + r.name;
				style = "max-height: 100%; max-width: 100%;"
				$("#remove_avatar_block").removeClass("d-none");
			}
			
			var ele_avatar = $("<img>", {
				class : "img-responsive",
				style : style,
				src : src
			});
			
			var ele_avatar_div = $("<div>", {
				class : "col-lg-3 col-md-3 col-sm-4",
				append : [
					ele_avatar
				]
			});
			
			ele_avatar_div.appendTo("#avatar_image");
		}
	});
}

var create_user = function (name, password, email, rolename) {
	qserv(admin_server, {model: 'user', action: 'create',
		name: name, password: password, email:email, rolename: rolename}, alert_after_create);
}

var update_user = function (name, password, email, rolename) {
	qserv(admin_server, {model: 'user', action: 'update',
		name: name, password: password, email:email, rolename: rolename}, alert_after_update);
}

var delete_user = function (name) {
	qserv(admin_server, {model: 'user', action: 'delete',
		name: name}, alert_after_delete, "user.html");
}

function load_user_after_roles(username) {
	if(roles_loaded === false) {
		window.setTimeout(load_user_after_roles.bind(null, username), 100); /* checks every 100 milliseconds*/
	} else {
		load_user(username);
	}
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("edit_users", function () {
		
		show_page();
		
		if (!roles_loaded)
			load_roles();
		
		var username = gup('name');
		
		if (username.length > 0) {
		
			show_view_button();
			show_update_button();
			show_delete_button();
			$('#name').prop('readonly', true);
			$('#name_help_extra').text(t('This cannot be changed.'));
			$('#password_help_extra').text(t('Leave this field empty to keep the existing password.'));
			
			load_user_after_roles(username);

			$('#view_btn').click(function(){
				show_url("../main/userview.html?name=" + username);
			});

			$('#update_btn').click(function(){
				if (input_has_changed()) {
					var name = $("#name").val();
					var password = $("#password").val();
					var email = $("#email").val();
					var rolename = get_selected_role();
					
					if (email.length <= 0)
						show_alert(t("Email cannot be empty."), "warning");
					else if (rolename.length <= 0)
						show_alert(t("Please select a role."), "warning");
					else {
						update_user(name, password, email, rolename);
					}			
				}
				else
					show_alert(t("Nothing changed."), "info");
			});

			$('#delete_btn').click(function(){
				delete_username = $("#name").val();
				$("#delete_type").text(t("User"));
				$("#delete_details").text(delete_username);
			});

			$('#confirmed_delete_btn').click(function(){
				delete_username = $("#name").val();
				delete_user(delete_username);
			});
		}
		else {
			// new user
			$('#user_scripts').hide();
			show_create_button();
			
			$('#create_btn').click(function(){
				var name = $("#name").val();
				var password = $("#password").val();
				var email = $("#email").val();
				var rolename = get_selected_role();
				
				if (name.length <= 0)
					show_alert(t("Name cannot be empty."), "warning");
				else if (password.length <= 0)
					show_alert(t("Password cannot be empty."), "warning");
				else if (email.length <= 0)
					show_alert(t("Email cannot be empty."), "warning");
				else if (rolename.length <= 0)
					show_alert(t("Please select a role."), "warning");
				else {
					create_user(name, password, email, rolename);
				}			
			});
		}
	});
		
})

