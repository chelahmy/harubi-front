// mymemberedit.js
// Harubi-Front Member of Own Group Edit
// By Abdullah Daud, chelahmy@gmail.com
// 10 December 2022


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
	append_role('administrator');
	allroles.push('administrator');
	append_role('member');
	allroles.push('member');
}

var load_group = function (ref) {
	qserv(main_server, {model: 'usergroup', action: 'read_own',
		ref: ref}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			var groupname = r.name;
			var ownername = r.ownername;
			
			var ele_ownername = $("<a>", {
				"href" : "userview.html?name=" + ownername,
				"text" : ownername
			});

			$("#groupname").text(groupname);
			$("#ownername").html(ele_ownername.prop('outerHTML'));
		}
	});
}

var load_member = function (groupref, username) {
	qserv(main_server, {model: 'member', action: 'read_own',
		groupref: groupref, username: username}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			$('#role_' + r.rolename).prop("checked", true);
			selected_role = r.rolename;
		}
	});
}

var update_member = function (groupref, username, rolename) {
	qserv(main_server, {model: 'member', action: 'update_own', 
		groupref: groupref, username: username, rolename: rolename}, alert_after_update);
}

var add_member = function (groupref, name, rolename) {
	qserv(main_server, {model: 'member', action: 'add_own',
		groupref: groupref, name: name, rolename: rolename}, alert_after_add);
}

var remove_member = function (groupref, name) {
	qserv(main_server, {model: 'member', action: 'remove_own', 
		groupref: groupref, name: name}, alert_after_remove, 'mymember.html?groupref=' + groupref);
}

$(window).on('load', function () {

	load_logo();
	
	when_allowed("edit_own_members", function () {
		
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		
		var groupref = gup('groupref');
		
		if (groupref.length > 0) {

			$('#members').prop('href', 'mymember.html?groupref=' + groupref);

			load_roles(); // hasten roles loading
			load_group(groupref);
			
			var username = gup('username');

			if (username.length > 0) {
			
				show_update_button();
				show_delete_button();
				
				$('#name').prop('readonly', true);
				$('#name_help_extra').text('Cannot change name.');
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
						show_alert("No change", "info");
				});

				$('#delete_btn').click(function(){
					$("#delete_type").text("Member");
					$("#delete_details").text(username);
				});

				$('#confirmed_delete_btn').click(function(){
					remove_member(groupref, username);
				});
			}
			else {
				// add member
				
				show_create_button();
				
				$('#create_btn').click(function(){
					var input_name = $("#name").val();
					
					if (input_name.length <= 0)
						show_alert("Name cannot be empty.");
					else {
						var input_role = get_checked_role();
						
						if (input_role.length <= 0)
							show_alert("Please select a role.");
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

