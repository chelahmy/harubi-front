// memberedit.js
// Harubi-Front Member of Group Edit
// By Abdullah Daud, chelahmy@gmail.com
// 7 December 2022


var allroles = [];
var selected_role = '';

var append_role = function (name) {
	
	var ele_input = $("<input>", {
		class : "form-check-input",
		type : "radio",
		name : "role",
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

var get_checked_role = function () {
	for (var i in allroles) {
		var role = allroles[i];
		if ($('#role_' + role).prop("checked"))
			return role;
	}
	
	return '';
}

var load_roles = function () {
	allroles = [];
	qserv(admin_server, {model: 'role', action: 'listall'}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var name = r[i].name;
					
					append_role(name);
					allroles.push(name);
				}
			}
		}
	});
}

var load_group = function (ref) {
	qserv(admin_server, {model: 'usergroup', action: 'read',
		ref: ref}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			var groupname = r.name;
			var ownername = r.ownername;
			
			var ele_ownername = $("<a>", {
				"href" : "useredit.html?name=" + ownername,
				"text" : ownername
			});

			$("#groupname").text(groupname);
			$("#ownername").html(ele_ownername.prop('outerHTML'));
		}
	});
}

var load_member = function (groupref, username) {
	qserv(admin_server, {model: 'member', action: 'read',
		groupref: groupref, username: username}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			$('#role_' + r.rolename).prop("checked", true);
			selected_role = r.rolename;
		}
	});
}

var update_member = function (groupref, username, rolename) {
	qserv(admin_server, {model: 'member', action: 'update', 
		groupref: groupref, username: username, rolename: rolename}, alert_after_update);
}

var add_member = function (groupref, username, rolename) {
	qserv(admin_server, {model: 'member', action: 'add',
		groupref: groupref, username: username, rolename: rolename}, alert_after_add);
}

var remove_member = function (groupref, username) {
	qserv(admin_server, {model: 'member', action: 'remove', 
		groupref: groupref, username: username}, alert_after_remove, 'member.html?groupref=' + groupref);
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("edit_members", function () {
		
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		
		var groupref = gup('groupref');
		
		if (groupref.length > 0) {

			$('#members').prop('href', 'member.html?groupref=' + groupref);

			load_roles(); // hasten roles loading
			load_group(groupref);
			
			var username = gup('username');

			if (username.length > 0) {
			
				show_alert(t("WARNING: Group is private. Do not update or remove members from a group without the owner consent."), "warning");

				show_update_button();
				show_delete_button();
				
				$('#name').prop('readonly', true);
				$('#name_help_extra').text(t('Cannot change name.'));
				$("#name").val(username);

				// delaying load member; expecting roles to finish loading first 
				setTimeout(function() {
					load_member(groupref, username);
				}, 500);				
				
				$('#update_btn').click(function(){
					var input_rolename = get_checked_role();
					
					if (selected_role != input_rolename) {
						update_member(groupref, username, input_rolename);
						selected_role = input_rolename;
					}
					else
						show_alert(t("No change"), "info");
				});

				$('#delete_btn').click(function(){
					$("#delete_type").text(t("Member"));
					$("#delete_details").text(username);
				});

				$('#confirmed_delete_btn').click(function(){
					remove_member(groupref, username);
				});
			}
			else {
				// add member
				
				show_alert(t("WARNING: Group is private. Do not add a member to a group without the owner consent."), "warning");

				show_create_button();
				
				$('#create_btn').click(function(){
					var input_name = $("#name").val();
					
					if (input_name.length <= 0)
						show_alert(t("Name cannot be empty."));
					else {
						var input_role = get_checked_role();
						
						if (input_role.length <= 0)
							show_alert(t("Please select a role."));
						else {
							add_member(groupref, input_name, input_role);
						}
					}
				});
			}
		}
		else
			show_error();
	});
		
})

