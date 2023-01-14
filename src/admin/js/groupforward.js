// groupforward.js
// Harubi-Front Group Forward
// By Abdullah Daud, chelahmy@gmail.com
// 11 January 2023


var table_index = 0;
var groups = {};
var selected_groups = [];

var append_group = function (name, ref, owner) {
	
	var ele_name = $("<a>", {
		"href" : "groupview.html?groupref=" + ref,
		"text" : name 
	});

	var ele_owner = $("<a>", {
		"href" : "useredit.html?name=" + owner,
		"text" : owner 
	});

	var ele_select = $("<input>", {
		class : "form-check-input",
		type : "checkbox",
		value : "",
		click : function (e) {
			const i = selected_groups.indexOf(ref);
			if ($(this).prop("checked")) {
				if (i < 0)
					selected_groups.push(ref);
			}
			else {
				if (i >= 0)
					selected_groups.splice(i, 1);
			}
		}
	});

	var ele_group = $("<tr>", {
		append : [
			$("<th>", {"text" : ++table_index, "scope" : "row"}),
			$("<td>", {"html" : ele_name.prop('outerHTML')}),
			$("<td>", {"html" : ele_owner.prop('outerHTML')}),
			$("<td>", {append : [ele_select]})
		]
	});

	$("#table_items").append(ele_group);
}

var load_groups = function (restart, search = '', order_by = 'name', sort = 'ASC') {
	if (restart == 1) {
		groups = {};
		selected_groups = [];
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
					
					append_group(name, ref, ownername);
					groups[ref] = name;
				}
			}
			
			if (rst.data.count <= rst.data.limit)
				$('#load_more_btn').hide();
		}
		else
			$('#load_more_btn').hide();
	});
}

var forward_to_group = function (group_ref, group_name, post_body, post_id, post_discussion_ref) {
	qserv(admin_server, {model: 'usergroup', action: 'read',
		ref: group_ref}, function(rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			
			if (post_discussion_ref == r.discussion_ref)
				show_alert(t("Not forwading to the same group @group_name.", {"@group_name": group_name}));
			else {
				qserv(main_server, {model: 'post', action: 'new',
					discussion_ref: r.discussion_ref, body: post_body, quote_discussion_ref: post_discussion_ref, quote_id: post_id}, function(rst, extra) {
					show_alert(t("Forwarded to the group @group_name.", {"@group_name": group_name}));
				});
			}
		}
	});
}

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("view_forward_usergroups", function () {
	
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
		
		load_forward_post();

		$('#forward_btn').click(function(){

			var post = $('#new_post').val();
			post = $("<div>", {html: post}).text(); // strip html tags
			
			if (post.length > 0 || forward_post_id > 0) {
					
				if (forward_post_id <= 0 || forward_discussion_ref.length <= 0)
					show_alert(t("You are trying to forward an invalid post."), "error");
				else if (selected_groups.length <= 0)
					show_alert(t("Please select a group or more."), "warning");
				else {
					for (var i in selected_groups) {
						var ref = selected_groups[i];
						var name = groups[ref];
						forward_to_group(ref, name, post, forward_post_id, forward_discussion_ref);
					}
				}
			}
			else
				$('#new_post').focus();
		})
		
		$(window).on("unload", function(){
			dispose_video_player(); // in the forward quote, if any. Otherwise, it will block everything else.
		});		
	});

})

