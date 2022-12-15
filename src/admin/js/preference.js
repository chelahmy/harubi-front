// preference.js
// Harubi-Front Preference
// By Abdullah Daud, chelahmy@gmail.com
// 17 November 2022


var table_index = 0;

var append_preference = function (name, value) {
	
	var ele_name = $("<a>", {
		"href" : "preferenceedit.html?name=" + name,
		"text" : name 
	});

	var ele_preference = $("<tr>", {
		append : [
			$("<th>", {"text" : ++table_index, "scope" : "row"}),
			$("<td>", {"html" : ele_name.prop('outerHTML')}),
			$("<td>", {"text" : value}),
		]
	});

	$("#table_items").append(ele_preference);
}

var load_preferences = function (restart) {
	qserv(admin_server, {model: 'preference', action: 'list',
		restart: restart}, function (rst, extra) {
		if (rst.data.count > 0) {
			if (restart == 1)
				table_index = 0;
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var name = r[i].name;
					var value = r[i].value;
					
					append_preference(name, value);
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
	
	when_allowed("list_preferences", function () {
	
		show_page();
		load_preferences(1);
		
		$('#load_more_btn').click(function(){
			load_preferences(0);
		})
	});

})

