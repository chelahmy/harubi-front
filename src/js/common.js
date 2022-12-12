// common.js
// Harubi-Front Common
// By Abdullah Daud, chelahmy@gmail.com
// 13 November 2022

var main_server = "main/includes/main.php";
var loading_logo_path = "logo/logo.png";
var page_logo_path = "logo/logo.png";

// Server error codes
const serr_not_signed_in = 1;
const serr_try_signup_later = 2;
const serr_signup_failed = 3;
const serr_user_exists = 4;
const serr_user_update_failed = 5;
const serr_user_restricted = 6;
const serr_signin_failed = 7;
const serr_user_read_failed = 8;
const serr_name_exists = 9;
const serr_access_denied = 10;
const serr_create_failed = 11;
const serr_read_failed = 12;
const serr_update_failed = 13;
const serr_delete_failed = 14;
const serr_record_already_exists = 15;
const serr_parent_record_missing = 16;
const serr_remove_failed = 17;
const serr_cannot_change_password = 18;
const serr_cannot_delete = 19;
const serr_missing = 20;
const err_cannot_create_ref = 21;

// gup - from https://gist.github.com/cgravolet/185484
function gup(name){
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp(regexS);
	var results = regex.exec(window.location.href);
	if (results == null) {
		return '';
	} else {
		return results[1];
	}
} // gup() - Get URL Param

var load_logo = function () {
	$('#loading_logo').attr('src', loading_logo_path);
	$('#page_logo').attr('src', page_logo_path);
}

// Calculate the number of days since ts.
var days_since = function (ts) {
	const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
	const lastLoginDate = new Date(ts * 1000);
	const todaysDate = new Date(); // Current date
	const diffDays = Math.round(Math.abs((lastLoginDate - todaysDate) / oneDay));
	return diffDays;
}

// Calculate the number of days since ts.
// And return the phrase.
var since_phrase = function (ts) {
	var since = '', days = days_since(ts);
	
	if (days > 365) {
		var years = parseInt(days / 365);
		if (years > 1)
			since = years + " years ago";
		else
			since = "1 year ago";
	}
	else {
		if (days > 1)
			since = days + " days ago";
		else if (days == 1)
			since = "yesterday";
		else
			since = "today";
	}
	
	return since;
}

// Redirect to the url. May delay (in 1000th seconds).
var show_url = function (url, delay = 0) {
	if (delay <= 0)
		window.location.replace(url);
	else {
		setTimeout(function() {
			window.location.replace(url); 
		}, delay);				
	}
}

// Redirect to the error page
var show_error = function (id) {
	if (id === undefined)
		id = 0;
	
	window.location.replace("error.html?id=" + id);
}

// Unhide the page.
// By default the page is hidden for access control reason.
var show_page = function () {
	$('#loading').addClass('d-none');
	$('#page').removeClass('d-none');
}

// Unhide the create button
var show_create_button = function () {
	$('#create_btn').removeClass('d-none');
}

// Unhide the update button
var show_update_button = function () {
	$('#update_btn').removeClass('d-none');
}

// Unhide the delete button
var show_delete_button = function () {
	$('#delete_btn').removeClass('d-none');
}

// Append alert message.
// type: primary, secondary, success, danger, warning, info, light and dark.
var show_alert = function (message, type = "primary") {
	var ele_btn = $("<button>", {
		"type" : "button",
		"class" : "btn-close",
		"data-bs-dismiss" : "alert",
		"aria-label" : "Close"
	});
	
	var ele_alert = $("<div>", {
		"class" : "alert alert-dismissible alert-" + type,
		"role" : "alert",
		append : [
			$("<div>", {"html" : message}),
			ele_btn
		]
	});
	
	$("#alerts").append(ele_alert);
}

// Parse query response according to the harubi convention.
// On error, constructs a proper error response. 
var parse_response = function (resp) {
	try {
		return JSON.parse(resp);
	} catch (e) {
		// warning_dialog("Server: " + resp);
	}
	return {
		status: -1, 
		data: {
			error: -1, 
			error_msg: "Server responded with unknown data format."
		}
	};	
};

// Show server response error messages
var show_resp_err = function (data) {
	if ((typeof data !== 'undefined') && (typeof data.error_msg !== 'undefined')) {
		if ((typeof data.logs !== 'undefined') && data.logs.length > 0) {
			if ((typeof data.error_ext !== 'undefined') && data.error_ext.length > 0)
				show_alert(data.error_msg + " - " + data.error_ext + ' : ' + data.logs, "danger");
			else
				show_alert(data.error_msg + " : " + data.logs, "danger");
		}
		else {
			if ((typeof data.error_ext !== 'undefined') && data.error_ext.length > 0)
				show_alert(data.error_msg + " - " + data.error_ext, "danger");
			else
				show_alert(data.error_msg, "danger");
		}
	}
	else
		show_alert("Unknown error", "danger");
};

// Query server wrapper.
var qserv = function (path, args, on_ready, extra = '') {
	$.post(path, args, function(result, status) {
		if (status == "success") {
			var handled = false;
			var rst = parse_response(result);
			if ((typeof rst !== 'undefined') && 
				(typeof rst.status !== 'undefined')) {
				if (rst.status == 1) {
					on_ready(rst, extra);
					handled = true;
				}
				else if (rst.status == 0 || rst.status == -1) {
					if (typeof rst.data !== 'undefined') {
						show_resp_err(rst.data);
						handled = true;
					}
				}
			}
			
			if (!handled) // system communication was ok but response was unexpected
				show_alert("Unknown error", "danger");
		}
		else
			show_alert("System error", "danger");
	});
}

// See alert_after_*() functions below.
// *extra* can be a string or an object {msg: '', url : '', delay: 2000}.
// *extra.msg* will overwrite msg.
// *extra* as a string will be the redirecting url.
// *extra.url" the redirecting url.
// *extra.delay" the delay in 1000th seconds before redirecting. default 2000.
var alert_after = function (msg, extra = '') {
	var url = '';
	var delay = 2000;
	
	if (typeof extra === 'object') {
		if (typeof extra.msg !== 'undefined')
			msg = extra.msg;
		if (typeof extra.url !== 'undefined')
			url = extra.url;
		if (typeof extra.delay !== 'undefined')
			delay = extra.delay;
	}
	else if (extra.length > 0)
		url = extra;
	
	if ((typeof extra === 'object') && (typeof extra.msg !== 'undefined'))
		msg = extra.msg;
	
	show_alert(msg, "success");	
	
	if (url.length > 0)
		show_url(url, delay); // delay for alert viewing, then redirect
}

// for qserv on_ready when creating is successful.
var alert_after_create = function (rst = {}, extra = '') {
	alert_after("Created", extra);
}

// for qserv on_ready when updating is successful.
var alert_after_update = function (rst = {}, extra = '') {
	alert_after("Updated", extra);
}

// for qserv on_ready when deleting is successful.
var alert_after_delete = function (rst = {}, extra = '') {
	alert_after("Deleted", extra);
}

// for qserv on_ready when adding is successful.
var alert_after_add = function (rst = {}, extra = '') {
	alert_after("Added", extra);
}

// for qserv on_ready when removing is successful.
var alert_after_remove = function (rst = {}, extra = '') {
	alert_after("Removed", extra);
}

// When the current user has the permission then execute do_task().
var when_allowed = function (permname, do_task, on_access_denied_redirect_url = '') {
	$.post(main_server, {model: 'user', action: 'has_permission', permname: permname}, function(result, status) {
		var handled = false;
		if (status == "success") {
			var rst = parse_response(result);
			if (rst.status == 1) {			
				do_task();
				handled = true;
			}
			else if (rst.status == 0) {
				if (rst.data.error == serr_access_denied) {
					if (on_access_denied_redirect_url.length > 0)
						show_url(on_access_denied_redirect_url);
					else
						show_error(1); // Redirect to the access denied page.

					handled = true;
				}
			}
		}
		if (!handled)		// Redirect to the error page with no error details
			show_error();	// because the user has not accessed the system yet.
	});
}

// Make a HTML table sortable to represent a table in a database.
// - Any column in a HTML table can be made sortable.
// - A sortable column must have <th> id that starts with "order_by_" and followed
//   by the column name in the database table. I.e., <th id="order_by_name"> where
//   *name* is the column name in the database table.
// - Clicking on a column header will toggle the sort arrow up and down.
// - A click will also trigger the  on_click event that passes parameters *order_by*
//   and *sort*. *order_by* will be set to the column name, and *sort* will be set to
//   'ASC' or 'DESC'.
// - The on_click event should refresh the HTML table by reloading the database
//   table according to the *order_by* and *sort*.
var sortables = function (table_id, selected_column, sort, on_click) {
	var columns = {};
	
	// Iterate all sortable columns
	$('table#' + table_id + ' > thead > tr > th').each(function(i, obj) {
		if (obj.id.startsWith('order_by_')) {
			var col = obj.id.substring(9);
			var label = obj.innerHTML;
			columns[col] = label;

			// Make a column label clickable
			var ele_label = $("<a>", {
				"href" : '',
				"id" : '_' + obj.id,
				"text" : label
			});
			
			// Attach sort arrow to a column label
			var ele_sort = $("<span>", {
				"id" : '_sort_' + col,
				"html" : selected_column == col ? (sort == 'ASC' ? "&uarr;" : "&darr;") : ""
			});
			
			// Make a column sortable
			$('#' + obj.id).html(ele_label.prop('outerHTML') + '&nbsp;' + ele_sort.prop('outerHTML'));
			
			// Attach the on click event handler to the column
			$('#_' + obj.id).click(function (e) {
				e.preventDefault();
				
				// Clear all order arrows accept this one
				for (var c in columns) {
					if (c != col) {
						$('#_sort_' + c).html("");
					}
				}
				
				// Toggle sorting, and call on_click(order_by, sort)
				var order = $('#_sort_' + col).text();
				if (order == $("<textarea/>").html("&uarr;").text()) {
					$('#_sort_' + col).html("&darr;");
					if (typeof on_click === "function")
						on_click(col, 'DESC');
				}
				else {
					$('#_sort_' + col).html("&uarr;");
					if (typeof on_click === "function")
						on_click(col, 'ASC');
				}
			})
		}
	});
}

// Add a home iconic menu item on the home page.
// The icon is based on boostrap-icons.
var add_home_menu_item = function (icon, title, description, url) {

	var ele_icon = $("<i>", {
		class : "home-icon bi-" + icon
	});

	var ele_icon_url = $("<a>", {
		"href" : encodeURI(url),
		"html" : ele_icon.prop('outerHTML') 
	});

	var ele_title_url = $("<a>", {
		"href" : encodeURI(url),
		"text" : title 
	});

	var ele_title = $("<h3>", {
		class : "fs-2",
		html : ele_title_url.prop('outerHTML')
	});

	var ele_col = $("<div>", {
		class : "feature col",
		append : [
			ele_icon_url,
			ele_title,
			$("<p>", {"text" : description})
		]
	});

	$("#home_menu").append(ele_col);
}





