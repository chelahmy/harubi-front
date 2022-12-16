// common.js
// Harubi-Front Common
// By Abdullah Daud, chelahmy@gmail.com
// 13 November 2022

var main_server = "main/includes/main.php";
var loading_logo_path = "logo/logo.png";
var page_logo_path = "logo/logo.png";
var languages_path = "languages/";

var language = 'en';
var language_strings = {};

// Server error codes
const serr_unknown = 0;
const serr_not_signed_in = 1;
const serr_try_signup_later = 2;
const serr_signup_failed = 3;
const serr_user_exists = 4;
const serr_user_update_failed = 5;
const serr_user_restricted = 6;
const serr_signin_failed = 7;
const serr_access_denied = 8;
const serr_create_failed = 9;
const serr_read_failed = 10;
const serr_update_failed = 11;
const serr_delete_failed = 12;
const serr_record_already_exists = 13;
const serr_cannot_change_password = 14;
const serr_cannot_delete = 15;
const serr_record_missing = 16;
const err_cannot_create_ref = 17;

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

// from https://stackoverflow.com/a/4399718
function getTextNodesIn(node, includeWhitespaceNodes) {
    var textNodes = [], nonWhitespaceMatcher = /\S/;

    function getTextNodes(node) {
        if (node.nodeType == 3) {
            if (includeWhitespaceNodes || nonWhitespaceMatcher.test(node.nodeValue)) {
                textNodes.push(node);
            }
        } else {
            for (var i = 0, len = node.childNodes.length; i < len; ++i) {
                getTextNodes(node.childNodes[i]);
            }
        }
    }

    getTextNodes(node);
    return textNodes;
}

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
			since = t("@years years ago", {'@years' : years});
		else
			since = t("1 year ago");
	}
	else {
		if (days > 1)
			since = t("@days days ago", {'@days' : days});
		else if (days == 1)
			since = t("yesterday");
		else
			since = t("today");
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
		// show_alert("Server: " + resp, 'warning');
	}
	return {
		status: -1, 
		data: {
			error: -1, 
			error_msg: t("Server responded with unknown data format.")
		}
	};	
};

// Show server response error messages
var show_resp_err = function (data) {
	if ((typeof data !== 'undefined') && (typeof data.error_msg !== 'undefined')) {
		if ((typeof data.logs !== 'undefined') && data.logs.length > 0) {
			if ((typeof data.error_ext !== 'undefined') && data.error_ext.length > 0)
				show_alert(t(data.error_msg) + " - " + t(data.error_ext, data.error_ext_params) + ' : ' + data.logs, "danger");
			else
				show_alert(t(data.error_msg) + " : " + data.logs, "danger");
		}
		else {
			if ((typeof data.error_ext !== 'undefined') && data.error_ext.length > 0)
				show_alert(t(data.error_msg) + " - " + t(data.error_ext, data.error_ext_params), "danger");
			else
				show_alert(t(data.error_msg), "danger");
		}
	}
	else
		show_alert(t("Unknown error"), "danger");
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
					if (typeof on_ready === 'function')
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
				show_alert(t("Unknown error"), "danger");
		}
		else
			show_alert(t("System error"), "danger");
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
	alert_after(t("Created"), extra);
}

// for qserv on_ready when updating is successful.
var alert_after_update = function (rst = {}, extra = '') {
	alert_after(t("Updated"), extra);
}

// for qserv on_ready when deleting is successful.
var alert_after_delete = function (rst = {}, extra = '') {
	alert_after(t("Deleted"), extra);
}

// for qserv on_ready when adding is successful.
var alert_after_add = function (rst = {}, extra = '') {
	alert_after(t("Added"), extra);
}

// for qserv on_ready when removing is successful.
var alert_after_remove = function (rst = {}, extra = '') {
	alert_after(t("Removed"), extra);
}

// Load signed-in data.
// Get the current user signed-in data which are
// 1. is_signedin
// 2. is_signedin_admin
// 3. signedin_language
// 4. signedin_uname
// and call on_ready(data) when the data is ready.
var load_signedin = function (on_ready) {
	if (typeof on_ready === 'function')
		qserv(main_server, {model: 'system', action: 'signedin'}, function(rst, extra) {
			on_ready(rst.data);
		});
}

// Translate string
var t = function (str, params) {
	str = str.trim();
	
	if (str.length > 0) {
		if (str in language_strings)
			str = language_strings[str];
	}
	
	if (typeof params === 'object') {
		for (var p in params) {
			str = str.replaceAll(p, params[p]);
		}
	}
	
	return str;	
}

// Translate all elements texts.
var te = function () {
	var title = $('title').text().trim();

	if (title in language_strings)
		$('title').text(language_strings[title]);
	
	var tnodes = getTextNodesIn($('body')[0]);
	
	$.each(tnodes, function(index, tn) { 
		var text = tn.nodeValue.trim();
		if (text in language_strings)
			tn.nodeValue = language_strings[text];
	});
	
	$('form').find("input").each(function(ev) {
		var ph = $(this).attr("placeholder");
		
		if (typeof ph !== 'undefined' && ph !== false) {
			ph = ph.trim();
			if (ph in language_strings)
				$(this).attr("placeholder", language_strings[ph]);
		}
	});	
}

// Load language strings.
// And translate the current page.
// And trigger on_ready() if specified.
var load_language = function (lang, on_ready) {
	if (lang == 'en') { // default language
		if (typeof on_ready === 'function')
			on_ready();
			
		return;
	}
		
	$.getJSON( languages_path + lang + '.json', function( data ) {
		language = lang;
		language_strings = data;
		
		te();
		
		if (typeof on_ready === 'function')
			on_ready();
	});
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

// Prevent all forms from triggering submit.
var prevent_form_submit = function () {
	$(document).on("submit", "form", function(e){
		e.preventDefault();
		return  false;
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

// Load home menu from preferences.
// Standard *name* are *admin* and *home*.
var load_home_menu = function (name) {
	var pref = "home_" + name + "_menu_item_";
	qserv(main_server, {model: 'preference', action: 'read_starts_with',
		name: pref}, function(rst, extra) {
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var pval = r[i].value;
					try {
						var pobj = JSON.parse(pval);
						add_home_menu_item(pobj.i, t(pobj.t), t(pobj.d), pobj.u);
					} catch (e) {
						// show_alert("Invalid menu item object", 'warning');
					}					
				}
			}	
	});
}



