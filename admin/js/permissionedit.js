// permissionedit.js
// Harubi-Front Permission Edit
// By Abdullah Daud, chelahmy@gmail.com
// 14 November 2022


var load_permission = function (name) {
	qserv(admin_server, {model: 'permission', action: 'read',
		name: name}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			$("#name").val(r.name);
		}
	});
}

var create_permission = function (name) {
	qserv(admin_server, {model: 'permission', action: 'create',
		name: name}, alert_after_create, "permrole.html?permname=" + name);
}

var update_permission = function (name, newname) {
	qserv(admin_server, {model: 'permission', action: 'update',
		name: name, newname: newname}, alert_after_update);
}

var delete_permission = function (name) {
	qserv(admin_server, {model: 'permission', action: 'delete',
		name: name}, alert_after_delete, "permission.html");
}

$(window).on('load', function () {

	load_logo();
	
	when_allowed("edit_permissions", function () {
		
		show_page();
		
		var permissionname = gup('name');
		
		if (permissionname.length > 0) {
		
			var ele_editroles = $("<a>", {
				"href" : "permrole.html?permname=" + permissionname,
				"text" : "Edit roles"
			});

			$("#editroles").html(ele_editroles.prop('outerHTML'));
		
			show_update_button();
			show_delete_button();
						
			load_permission(permissionname);

			$('#update_btn').click(function(){
				new_permissionname = $("#name").val();
				
				if (new_permissionname != permissionname) {
					update_permission(permissionname, new_permissionname);
					permissionname = new_permissionname;
				}
				else
					show_alert("No change", "info");
			});

			$('#delete_btn').click(function(){
				delete_permissionname = $("#name").val();
				$("#delete_type").text("Permission");
				$("#delete_details").text(delete_permissionname);
			});

			$('#confirmed_delete_btn').click(function(){
				delete_permissionname = $("#name").val();
				delete_permission(delete_permissionname);
			});
		}
		else {
			// new permission
			show_create_button();
			
			$('#create_btn').click(function(){
				create_permission($("#name").val());
			});
		}
	});
		
})

