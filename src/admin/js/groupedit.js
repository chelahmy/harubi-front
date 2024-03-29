// groupedit.js
// Harubi-Front Group Edit
// By Abdullah Daud, chelahmy@gmail.com
// 7 December 2022


var input_groupname = '';
var input_ownername = '';

var load_group = function (ref) {
	qserv(admin_server, {model: 'usergroup', action: 'read',
		ref: ref}, function (rst, extra) {
		if (rst.data.count > 0) {
			show_page();
			var r = rst.data.records[0];
			input_groupname = r.name;
			input_ownername = r.ownername;
			$("#name").val(input_groupname);
			$("#ownername").val(input_ownername);
		}
	});
}

var create_group = function (ownername, name) {
	qserv(admin_server, {model: 'usergroup', action: 'create',
		ownername: ownername, name: name}, function (rst, extra) {
		alert_after_create(rst, "groupedit.html?groupref=" + rst.data.ref); // redirect to prevent accidental multiple create
	});
}

var update_group = function (ref, name) {
	qserv(admin_server, {model: 'usergroup', action: 'update',
		ref: ref, name: name}, alert_after_update);
}

var delete_group = function (ref) {
	qserv(admin_server, {model: 'usergroup', action: 'delete',
		ref: ref}, alert_after_delete, "group.html");
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("edit_usergroups", function () {
		
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		
		var groupref = gup('groupref');
		
		if (groupref.length > 0) {
		
			show_alert(t("WARNING: Group is private. Do not update or delete a group without the owner consent."), "warning");

			show_view_button();
			show_update_button();
			show_delete_button();
			$('#ownername').prop('readonly', true);
			$('#owner_help_extra').text(t('Cannot change owner.'));
			
			load_group(groupref);

			$('#view_btn').click(function(){
				show_url("groupview.html?groupref=" + groupref);
			});

			$('#update_btn').click(function(){
				var new_groupname = $("#name").val();
				
				if (new_groupname != input_groupname) {
					update_group(groupref, new_groupname);
					input_groupname = new_groupname;
				}
				else
					show_alert(t("Nothing changed."), "info");
			});

			$('#delete_btn').click(function(){
				var delete_groupname = $("#name").val();
				$("#delete_type").text(t("Group"));
				$("#delete_details").text(delete_groupname);
			});

			$('#confirmed_delete_btn').click(function(){
				delete_group(groupref);
			});
		}
		else {
			// new group
			
			show_alert(t("WARNING: Group is private. Do not create a group without the owner consent."), "warning");

			show_create_button();
			
			$('#create_btn').click(function(){
				create_group($("#ownername").val(), $("#name").val());
			});
		}
	});
		
})

