// permission.js
// Harubi-Front Permission
// By Abdullah Daud, chelahmy@gmail.com
// 16 November 2022


var table_index = 0;

var append_permission = function (name, roles) {
	
	var ele_name = $("<a>", {
		"href" : "permissionedit.html?name=" + name,
		"text" : name 
	});

	var ele_editroles = $("<a>", {
		"href" : "permrole.html?permname=" + name,
		"text" : '+/- roles' 
	});

	var ele_permission = $("<tr>", {
		append : [
			$("<th>", {"text" : ++table_index, "scope" : "row"}),
			$("<td>", {"html" : ele_name.prop('outerHTML')}),
			$("<td>", {"text" : roles.join(', ')}),
			$("<td>", {"html" : ele_editroles.prop('outerHTML')})
		]
	});

	$("#table_items").append(ele_permission);
}

var load_permissions = function (restart, search = '') {
	if (restart == 1) {
		$("#table_items").empty();
		table_index = 0;
	}

	qserv(admin_server, {model: 'permission', action: 'list',
		restart: restart, search: search}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var name = r[i].name;
					var roles = r[i].roles;
					
					append_permission(name, roles);
				}
			}
		}
		else
			$('#load_more_btn').hide();
	});
}

$(window).on('load', function () {

	load_logo();
	
	when_allowed("list_permissions", function () {
	
		$(document).on("submit", "form", function(e){
			e.preventDefault();
			return  false;
		});
	
		show_page();

		var input_search = $('#search').val();
		load_permissions(1, input_search);
		
		$('#search_btn').click(function(){
			$('#load_more_btn').show();
			input_search = $('#search').val();
			load_permissions(1, input_search);
		})
		
		$('#load_more_btn').click(function(){
			// reinstate user input values
			$('#search').val(input_search);
			load_permissions(0, input_search);
		})
	});

})

