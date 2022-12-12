// user.js
// Harubi-Front User
// By Abdullah Daud, chelahmy@gmail.com
// 15 November 2022


var table_index = 0;

var append_user = function (name, email, rolename, created_utc, signins, last_signedin_utc) {

	var ele_name = $("<a>", {
		"href" : "useredit.html?name=" + name,
		"text" : name 
	});

	var ele_user = $("<tr>", {
		append : [
			$("<th>", {"text" : ++table_index, "scope" : "row"}),
			$("<td>", {"html" : ele_name.prop('outerHTML')}),
			$("<td>", {"text" : email}),
			$("<td>", {"text" : rolename}),
			$("<td>", {"text" : created_utc > 0 ? since_phrase(created_utc) : ''}),
			$("<td>", {"text" : signins}),
			$("<td>", {"text" : last_signedin_utc > 0 ? since_phrase(last_signedin_utc) : ''}),
		]
	});

	$("#table_items").append(ele_user);
}

var load_users = function (restart, search = '', premium = 0, order_by = 'name', sort = 'ASC') {
	if (restart == 1) {
		$("#table_items").empty();
		table_index = 0;
		$('#load_more_btn').show();
	}

	qserv(admin_server, {model: 'user', action: 'list',
		restart: restart, search: search, premium: premium, order_by: order_by, sort: sort}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var name = r[i].name;
					var email = r[i].email;
					var rolename = r[i].rolename;
					var created_utc = r[i].created_utc;
					var signins = r[i].signins;
					var last_signedin_utc = r[i].last_signedin_utc;
					
					append_user(name, email, rolename, created_utc, signins, last_signedin_utc);
				}
			}
		}
		else
			$('#load_more_btn').hide();
	});
}

$(window).on('load', function () {

	load_logo();
	
	when_allowed("list_users", function () {
	
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();
		
		var input_search = '';
		var input_premium = 0;
		var input_order_by = 'name';
		var input_sort = 'ASC';

		sortables("user_table", input_order_by, input_sort, function (order_by, sort) {
			input_order_by = order_by;
			input_sort = sort;
			input_search = $('#search').val();
			input_premium = $("#premium").prop('checked') ? 1 : 0;
			load_users(1, input_search, input_premium, input_order_by, input_sort);
		});

		input_search = $('#search').val();
		input_premium = $("#premium").prop('checked') ? 1 : 0;
		load_users(1, input_search, input_premium, input_order_by, input_sort);
		
		$('#search_btn').click(function(){
			input_search = $('#search').val();
			input_premium = $("#premium").prop('checked') ? 1 : 0;
			load_users(1, input_search, input_premium, input_order_by, input_sort);
		})
		
		$('#load_more_btn').click(function(){
			// reinstate user input values
			$('#search').val(input_search);
			$("#premium").prop('checked', input_premium == 1);
			load_users(0, input_search, input_premium, input_order_by, input_sort);
		})
	});

})

