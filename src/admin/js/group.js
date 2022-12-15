// group.js
// Harubi-Front Group
// By Abdullah Daud, chelahmy@gmail.com
// 16 November 2022


var table_index = 0;

var append_group = function (name, ref, owner, created_by_username, created_utc) {
	
	var ele_name = $("<a>", {
		"href" : "groupedit.html?groupref=" + ref,
		"text" : name 
	});

	var ele_owner = $("<a>", {
		"href" : "useredit.html?name=" + owner,
		"text" : owner 
	});

	var ele_created_by = $("<a>", {
		"href" : "useredit.html?name=" + created_by_username,
		"text" : created_by_username 
	});

	var ele_members = $("<a>", {
		"href" : "member.html?groupref=" + ref,
		"text" : t("members") 
	});

	var ele_group = $("<tr>", {
		append : [
			$("<th>", {"text" : ++table_index, "scope" : "row"}),
			$("<td>", {"html" : ele_name.prop('outerHTML')}),
			$("<td>", {"html" : ele_owner.prop('outerHTML')}),
			$("<td>", {"html" : ele_created_by.prop('outerHTML')}),
			$("<td>", {"text" : created_utc > 0 ? since_phrase(created_utc) : ''}),
			$("<td>", {"html" : ele_members.prop('outerHTML')})
		]
	});

	$("#table_items").append(ele_group);
}

var load_groups = function (restart, search = '', order_by = 'name', sort = 'ASC') {
	if (restart == 1) {
		$("#table_items").empty();
		table_index = 0;
		$('#load_more_btn').show();
	}

	qserv(admin_server, {model: 'usergroup', action: 'list',
		restart: restart, search: search, order_by: order_by, sort: sort}, function(rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var name = r[i].name;
					var ref = r[i].ref;
					var ownername = r[i].ownername;
					var created_by_username = r[i].created_by_username;
					var created_utc = r[i].created_utc;
					
					append_group(name, ref, ownername, created_by_username, created_utc);
				}
			}
		}
		else
			$('#load_more_btn').hide();
	});
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("list_usergroups", function () {
	
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		var input_search = '';
		var input_order_by = 'name';
		var input_sort = 'ASC';

		sortables("usergroup_table", input_order_by, input_sort, function (order_by, sort) {
			input_order_by = order_by;
			input_sort = sort;
			input_search = $('#search').val();
			load_groups(1, input_search, input_order_by, input_sort);
		});

		input_search = $('#search').val();
		load_groups(1, input_search, input_order_by, input_sort);
		
		$('#search_btn').click(function(){
			input_search = $('#search').val();
			load_groups(1, input_search, input_order_by, input_sort);
		})
		
		$('#load_more_btn').click(function(){
			// reinstate user input values
			$('#search').val(input_search);
			load_groups(0, input_search, input_order_by, input_sort);
		})
	});

})

