// permrole.js
// Harubi-Front Permission Role
// By Abdullah Daud, chelahmy@gmail.com
// 14 November 2022


var allroles = [];
var checked_roles = [];

var append_role = function (name) {
	
	var ele_input = $("<input>", {
		class : "form-check-input",
		type : "checkbox",
		value : "",
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

	$("#input_items").append(ele_role);
}

var load_permroles = function (permname) {
	checked_roles = [];
	qserv(admin_server, {model: 'permrole', action: 'listall',
		permname: permname}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var name = r[i].name;
					
					$('#role_' + name).prop("checked", true);
					checked_roles.push(name);
				}
			}
		}
	});
}

var load_roles = function (permname) {
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
			
			load_permroles(permname);
		}
	});
}

var strong = function (str) {
	return "<strong>" + str + "</strong>";
}

var add_permrole = function (permname, rolename) {
	qserv(admin_server, {model: 'permrole', action: 'add',
		permname: permname, rolename: rolename}, alert_after_add, {msg: t('@rolename role added', {'@rolename' : rolename})});
}

var remove_permrole = function (permname, rolename) {
	qserv(admin_server, {model: 'permrole', action: 'remove',
		permname: permname, rolename: rolename}, alert_after_remove, {msg: t('@rolename role removed', {'@rolename' : rolename})});
}

$(window).on('load', function () {

	load_logo();

	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("edit_permission_roles", function () {
		
		show_page();
		
		var permname = gup('permname');
		
		if (permname.length > 0) {
		
			var ele_permedit = $("<a>", {
				"href" : "permissionedit.html?name=" + permname,
				"text" : permname
			});

			$("#permission_link").html(ele_permedit.prop('outerHTML'));

			show_update_button();
			load_roles(permname);

			$('#update_btn').click(function(){
					
				new_checked_roles = [];
				changed = false;
				
				allroles.forEach(function (role) {
					if ($('#role_' + role).prop("checked")) {
						if (!checked_roles.includes(role)) {
							add_permrole(permname, role);
							changed = true;
						}
							
						new_checked_roles.push(role);
					}
					else {
						if (checked_roles.includes(role)) {
							remove_permrole(permname, role);
							changed = true;
						}
					}
				});
				
				if (!changed)
					show_alert(t('Nothing changed.'), 'info');
					
				checked_roles = new_checked_roles;
				
			});
		}
		else
			show_error();
	});
		
})

