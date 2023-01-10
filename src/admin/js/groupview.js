// groupview.js
// Harubi-Front Group View
// By Abdullah Daud, chelahmy@gmail.com
// 29 December 2022

user_page_url = "admin/useredit.html";

var load_group = function (ref) {
	qserv(admin_server, {model: 'usergroup', action: 'read',
		ref: ref}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];

			var ele_name = $("<a>", {
				"href" : "groupedit.html?groupref=" + ref,
				"text" : r.name
			});

			$("#groupname").html(ele_name.prop('outerHTML'));
			
			var ele_owner = $("<a>", {
				"href" : "useredit.html?name=" + r.ownername,
				"text" : r.ownername
			});

			$("#ownername").html(ele_owner.prop('outerHTML'));

			this_discussion_ref = r.discussion_ref;
			load_posts(1, r.discussion_ref);
		}
	});
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("view_usergroups", function () {
		
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		
		var groupref = gup('groupref');
		
		if (groupref.length > 0) {
		
			load_group(groupref);

			init_discussion();
		}
		else
			show_error();
	});
		
})

