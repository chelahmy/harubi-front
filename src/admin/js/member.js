// member.js
// Harubi-Front Group Members
// By Abdullah Daud, chelahmy@gmail.com
// 7 December 2022


var table_index = 0;

var append_member = function (groupref, username, rolename, created_utc) {
	
	var ele_username = $("<a>", {
		"href" : "memberedit.html?groupref=" + groupref + "&username=" + username,
		"text" : username
	});

	var ele_role = $("<a>", {
		"href" : "roleedit.html?name=" + rolename,
		"text" : rolename
	});

	var ele_member = $("<tr>", {
		append : [
			$("<th>", {"text" : ++table_index, "scope" : "row"}),
			$("<td>", {"html" : ele_username.prop('outerHTML')}),
			$("<td>", {"html" : ele_role.prop('outerHTML')}),
			$("<td>", {"text" : created_utc > 0 ? since_phrase(created_utc) : ''}),
		]
	});

	$("#table_items").append(ele_member);
}

var list_members = function (restart, groupref, search = '', order_by = 'created_utc', sort = 'ASC') {
	if (restart == 1) {
		$("#table_items").empty();
		table_index = 0;
		$('#load_more_btn').show();
	}

	qserv(admin_server, {model: 'member', action: 'list',
		restart: restart, groupref: groupref, search: search, order_by: order_by, sort: sort}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var username = r[i].username;
					var rolename = r[i].rolename;
					var created_utc = r[i].created_utc;
					
					append_member(groupref, username, rolename, created_utc);
				}
			}
			
			if (rst.data.count <= rst.data.limit)
				$('#load_more_btn').hide();
		}
		else
			$('#load_more_btn').hide();
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


$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("list_members", function () {
	
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		
		var groupref = gup('groupref');
		
		if (groupref.length > 0) {
				
			show_alert(t("WARNING: Group is private."), "warning");
			
			$('#addmember').prop('href', 'memberedit.html?groupref=' + groupref);

			load_group(groupref);
			
			var input_search = '';
			var input_order_by = 'created_utc';
			var input_sort = 'ASC';

			sortables("member_table", input_order_by, input_sort, function (order_by, sort) {
				input_order_by = order_by;
				input_sort = sort;
				input_search = $('#search').val();
				list_members(1, groupref, input_search, input_order_by, input_sort);
			});

			input_search = $('#search').val();
			list_members(1, groupref, input_search, input_order_by, input_sort);
			
			$('#search_btn').click(function(){
				input_search = $('#search').val();
				list_members(1, groupref, input_search, input_order_by, input_sort);
			})
			
			$('#load_more_btn').click(function(){
				// reinstate user input values
				$('#search').val(input_search);
				list_members(0, groupref, input_search, input_order_by, input_sort);
			})
		}
		else
			show_error();
	});

})

