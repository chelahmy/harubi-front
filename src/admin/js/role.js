// role.js
// Harubi-Front Role
// By Abdullah Daud, chelahmy@gmail.com
// 16 November 2022


var table_index = 0;

var append_role = function (name, premium) {
	
	var ele_name = $("<a>", {
		"href" : "roleedit.html?name=" + name,
		"text" : name 
	});

	var ele_role = $("<tr>", {
		append : [
			$("<th>", {"text" : ++table_index, "scope" : "row"}),
			$("<td>", {"html" : ele_name.prop('outerHTML')}),
			$("<td>", {"html" : premium > 0 ? '<strong>&check;</strong>' : ''}),
		]
	});

	$("#table_items").append(ele_role);
}

var load_roles = function (restart) {
	if (restart == 1) {
		$("#table_items").empty();
		table_index = 0;
	}

	qserv(admin_server, {model: 'role', action: 'list',
		restart: restart}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var name = r[i].name;
					var premium = r[i].premium;
					
					append_role(name, premium);
				}
			}
		}
		else
			$('#load_more_btn').hide();
	});
}

$(window).on('load', function () {

	load_logo();
	
	when_allowed("list_roles", function () {
	
		show_page();
		load_roles(1);
		
		$('#load_more_btn').click(function(){
			load_roles(0);
		})
	});

})

