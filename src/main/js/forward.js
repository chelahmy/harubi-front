// forward.js
// Harubi-Front Forward Post to other Discussions
// By Abdullah Daud, chelahmy@gmail.com
// 11 January 2023

var postid = 0;

$(window).on('load', function () {

	load_logo();
	
	load_signedin(function (data) {
		load_language(data.signedin_language);
	});
	
	when_allowed("view_forward", function () {
	
		show_page();

		load_home_menu('forward_main');
	});

})

