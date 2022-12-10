// mygroup.js
// Harubi-Front My Group
// By Abdullah Daud, chelahmy@gmail.com
// 16 November 2022


var table_index = 0;

var append_group = function (name, ref, owner, created_utc) {
	
	var ele_name = $("<a>", {
		"href" : "mygroupedit.html?groupref=" + ref,
		"text" : name 
	});

	var ele_owner = $("<a>", {
		"href" : "userview.html?name=" + owner,
		"text" : owner 
	});

	var ele_members = $("<a>", {
		"href" : "mymember.html?groupref=" + ref,
		"text" : "members" 
	});

	var ele_group = $("<tr>", {
		append : [
			$("<th>", {"text" : ++table_index, "scope" : "row"}),
			$("<td>", {"html" : ele_name.prop('outerHTML')}),
			$("<td>", {"html" : ele_owner.prop('outerHTML')}),
			$("<td>", {"text" : created_utc > 0 ? since_phrase(created_utc) : ''}),
			$("<td>", {"html" : ele_members.prop('outerHTML')})
		]
	});

	$("#table_items").append(ele_group);
}

var load_groups = function (restart, type = 0, search = '', order_by = 'name', sort = 'ASC') {
	if (restart == 1) {
		$("#table_items").empty();
		table_index = 0;
		$('#load_more_btn').show();
	}

	qserv(main_server, {model: 'usergroup', action: 'list_own',
		restart: restart, type: type, search: search, order_by: order_by, sort: sort}, function(rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var name = r[i].name;
					var ref = r[i].ref;
					var ownername = r[i].ownername;
					var created_utc = r[i].created_utc;
					
					append_group(name, ref, ownername, created_utc);
				}
			}
		}
		else
			$('#load_more_btn').hide();
	});
}

$(window).on('load', function () {

	load_logo();
	
	when_allowed("list_own_groups", function () {
	
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		
		var group_type = 0;
		var input_search = '';
		var input_order_by = 'name';
		var input_sort = 'ASC';

		input_search = $('#search').val();
		load_groups(1, group_type, input_search, input_order_by, input_sort);

		$('#my_groups').click(function(e){
			e.preventDefault();
			$('#my_groups').addClass('active');
			$('#groups_im_in').removeClass('active');
			group_type = 0;
			load_groups(1, group_type, input_search, input_order_by, input_sort);
		});
		
		$('#groups_im_in').click(function(e){
			e.preventDefault();
			$('#groups_im_in').addClass('active');
			$('#my_groups').removeClass('active');
			group_type = 1;
			load_groups(1, group_type, input_search, input_order_by, input_sort);
		});
		
		sortables("usergroup_table", "name", function (order_by, sort) {
			input_order_by = order_by;
			input_sort = sort;
			input_search = $('#search').val();
			load_groups(1, group_type, input_search, input_order_by, input_sort);
		});

		$('#search_btn').click(function(){
			$('#load_more_btn').show();
			input_search = $('#search').val();
			load_groups(1, group_type, input_search, input_order_by, input_sort);
		})
		
		$('#load_more_btn').click(function(){
			// reinstate user input values
			$('#search').val(input_search);
			load_groups(0, group_type, input_search, input_order_by, input_sort);
		})
	});

})

