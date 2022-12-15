// preferenceedit.js
// Harubi-Front Preference Edit
// By Abdullah Daud, chelahmy@gmail.com
// 17 November 2022

var preference_value = '';

var load_preference = function (name) {
	qserv(admin_server, {model: 'preference', action: 'read',
		name: name}, function (rst, extra) {
		if (rst.data.count > 0) {
			var r = rst.data.records[0];
			$("#name").val(r.name);
			$("#value").val(r.value);
			preference_value = r.value;
		}
	});
}

var create_preference = function (name, value) {
	qserv(admin_server, {model: 'preference', action: 'create',
		name: name, value: value}, alert_after_create);
}

var update_preference = function (name, value) {
	qserv(admin_server, {model: 'preference', action: 'update',
		name: name, value: value}, alert_after_update);
}

var delete_preference = function (name) {
	qserv(admin_server, {model: 'preference', action: 'delete',
		name: name}, alert_after_delete, "preference.html");
}

$(window).on('load', function () {

	load_logo();

	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("edit_preferences", function () {
		
		show_page();
		
		var preference_name = gup('name');
		
		if (preference_name.length > 0) {
		
			show_update_button();
			show_delete_button();
			$('#name').prop('readonly', true);
			$('#name_help_extra').text(t('This cannot be changed.'));
			load_preference(preference_name);

			$('#update_btn').click(function(){
				new_preference_value = $("#value").val();
				
				if (new_preference_value != preference_value) {
					update_preference(preference_name, new_preference_value);
					preference_value = new_preference_value;
				}
				else
					show_alert(t("No change"), "info");
			});

			$('#delete_btn').click(function(){
				delete_preference_name = $("#name").val();
				$("#delete_type").text(t("Preference"));
				$("#delete_details").text(delete_preference_name);
			});

			$('#confirmed_delete_btn').click(function(){
				delete_preference_name = $("#name").val();
				delete_preference(delete_preference_name);
			});
		}
		else {
			// new preference
			show_create_button();
			
			$('#create_btn').click(function(){
				create_preference($("#name").val(), $("#value").val());
			});
		}
	});
		
})

