// roleedit.js
// Harubi-Front Role Edit
// By Abdullah Daud, chelahmy@gmail.com
// 14 November 2022


var rolename = '';
var premium = 0;

var load_role = function (name) {
	qserv(admin_server, {model: 'role', action: 'read',
		name: name}, function (rst, extra) {
		if (rst.data.count > 0) {
			show_page();
			var r = rst.data.records[0];
			rolename = r.name;
			premium = r.premium;
			$("#name").val(name);
			$("#premium").prop('checked', premium > 0);
		}
	});
}

var create_role = function (name, premium) {
	qserv(admin_server, {model: 'role', action: 'create',
		name: name, premium: premium}, alert_after_create);
}

var update_role = function (name, newname, premium) {
	qserv(admin_server, {model: 'role', action: 'update',
		name: name, newname: newname, premium: premium}, alert_after_update);
}

var delete_role = function (name) {
	qserv(admin_server, {model: 'role', action: 'delete',
		name: name}, alert_after_delete, "role.html");
}

$(window).on('load', function () {

	load_logo();
	
	when_allowed("edit_roles", function () {
		
		show_page();
		
		rolename = gup('name');
		
		if (rolename.length > 0) {
		
			show_update_button();
			show_delete_button();
			load_role(rolename);

			$('#update_btn').click(function(){
				var new_rolename = $("#name").val();
				var new_premium = $("#premium").prop('checked') ? 1 : 0;
				
				if (new_rolename != rolename || new_premium != premium) {
					update_role(rolename, new_rolename, new_premium);
					rolename = new_rolename;
					premium = new_premium;
				}
				else
					show_alert("No change", "info");
			});

			$('#delete_btn').click(function(){
				delete_rolename = $("#name").val();
				$("#delete_type").text("Role");
				$("#delete_details").text(delete_rolename);
			});

			$('#confirmed_delete_btn').click(function(){
				delete_rolename = $("#name").val();
				delete_role(delete_rolename);
			});
		}
		else {
			// new role
			show_create_button();
			
			$('#create_btn').click(function(){
				create_role($("#name").val(), $("#premium").prop('checked') ? 1 : 0);
			});
		}
	});
		
})

