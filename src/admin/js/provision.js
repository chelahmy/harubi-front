// provision.js
// Harubi-Front System Provisioning
// By Abdullah Daud, chelahmy@gmail.com
// 3 December 2022


var go_provision = function () {
	qserv("includes/provision.php", {model: 'system', action: 'provision'}, function (rst, extra) {
		show_alert(t("System provisioning successful."), "success");
	});
}

$(window).on('load', function () {

	load_logo();

	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("system_provision", function () {
		
		$('#go_btn').click(function(){
			go_provision();
		});

	});
		
})

