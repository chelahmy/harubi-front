// mymember.js
// Harubi-Front My Group Members
// By Abdullah Daud, chelahmy@gmail.com
// 9 December 2022


var group_owner = false;
var group_admin = false;
var table_index = 0;

var append_member = function (groupref, username, rolename, added_by_username, created_utc) {
	
	var uname = username;
	
	if (group_owner || group_admin) {
		var ele_username = $("<a>", {
			"href" : "mymemberedit.html?groupref=" + groupref + "&username=" + username,
			"text" : username
		});
	
		uname = ele_username.prop('outerHTML');
	}

	var ele_added_by_username = $("<a>", {
		"href" : "userview.html?name=" + added_by_username,
		"text" : added_by_username
	});

	var ele_member = $("<tr>", {
		append : [
			$("<th>", {"text" : ++table_index, "scope" : "row"}),
			$("<td>", {"html" : uname}),
			$("<td>", {"text" : rolename}),
			$("<td>", {"html" : ele_added_by_username.prop('outerHTML')}),
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

	qserv(main_server, {model: 'member', action: 'list_own',
		restart: restart, groupref: groupref, search: search, order_by: order_by, sort: sort}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var username = r[i].username;
					var rolename = r[i].rolename;
					var added_by_username = r[i].added_by_username;
					var created_utc = r[i].created_utc;
					
					append_member(groupref, username, rolename, added_by_username, created_utc);
				}
			}
		}
		else
			$('#load_more_btn').hide();
	});
}

var load_group = function (ref) {
	qserv(main_server, {model: 'usergroup', action: 'read_own',
		ref: ref}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			var groupname = r.name;
			var ownername = r.ownername;
			group_owner = r.is_owner;
			group_admin = r.is_admin;
			
			var ele_addbtn = $("<a>", {
				"class" : "btn btn-primary",
				"href" : 'mymemberedit.html?groupref=' + ref,
				"role" : "button",
				"id" : "addmember",
				"text" : "Add member"
			});

			if (group_owner || group_admin)
				$('#search_group').append(ele_addbtn);
				
			var ele_ownername = $("<a>", {
				"href" : "userview.html?name=" + ownername,
				"text" : ownername
			});

			$("#groupname").text(groupname);
			$("#ownername").html(ele_ownername.prop('outerHTML'));
		}
	});
}


$(window).on('load', function () {

	load_logo();
	
	when_allowed("list_own_members", function () {
	
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		
		var groupref = gup('groupref');
		
		if (groupref.length > 0) {
				
			load_group(groupref);
			
			var input_search = '';
			var input_order_by = 'created_utc';
			var input_sort = 'ASC';

			input_search = $('#search').val();
			list_members(1, groupref, input_search, input_order_by, input_sort);
			
			sortables("member_table", "created_utc", function (order_by, sort) {
				input_order_by = order_by;
				input_sort = sort;
				input_search = $('#search').val();
				list_members(1, groupref, input_search, input_order_by, input_sort);
			});

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

