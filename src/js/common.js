// common.js
// Harubi-Front Common
// By Abdullah Daud, chelahmy@gmail.com
// 13 November 2022

var app_rel_path = "./";
var default_avatar = "libs/bootstrap-icons-1.10.2/person.svg";
var main_server = app_rel_path + "main/includes/main.php";
var loading_logo_path = app_rel_path + "logo/logo.png";
var page_logo_path = app_rel_path + "logo/logo.png";
var modules_path = app_rel_path + "modules/";
var languages_path = app_rel_path + "languages/";

var user_page_url = "main/userview.html";

var language = 'en';
var language_strings = {};

var unread_post_counts = {};

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

var set_app_rel_path = function (path) {
	app_rel_path = path;
	main_server = app_rel_path + "main/includes/main.php";
	loading_logo_path = app_rel_path + "logo/logo.png";
	page_logo_path = app_rel_path + "logo/logo.png";
	modules_path = app_rel_path + "modules/";
	languages_path = app_rel_path + "languages/";
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

// Calculate the number of minutes since ts.
var minutes_since = function (ts) {
	const lastLoginDate = new Date(ts * 1000);
	const todaysDate = new Date(); // Current date
	const diffMinutes = Math.round(Math.abs(lastLoginDate - todaysDate) / 60000);
	return diffMinutes;
}

// Calculate the number of years, days, hours or minutes since ts.
// And return the semantical phrase.
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
		else {
			// since = t("today");
			var minutes = minutes_since(ts);
			if (minutes < 1)
				since = t("just now");
			else if (minutes == 1)
				since = t("1 minute ago");
			else if (minutes < 60)
				since = t("@minutes minutes ago", {'@minutes' : minutes});
			else {
				var hours = parseInt(minutes / 60);
				if (hours > 1)
					since = t("@hours hours ago", {'@hours' : hours});
				else
					since = t("1 hour ago");
			}
		}
	}
	
	return since;
}

// Convert byte string to number.
// E.g., "1024k" -> 1048576 
var bytestr_to_num = function (bytes) {
	if (Number.isInteger(bytes))
		return bytes;
	if (typeof bytes === 'string' || bytes instanceof String) {
		var i, len = bytes.length, sval = '';
		for (i = 0; i < len; i++) {
			var c = bytes.substr(i, 1);
			if (c >= '0' && c <= '9')
				sval += c;
			else {
				var val = parseInt(sval);
				if (c == 'k' || c == 'K')
					return val * 1024;
				if (c == 'm' || c == 'M')
					return val * 1024 * 1024;
				if (c == 'g' || c == 'G')
					return val * 1024 * 1024 * 1024;
				if (c == 't' || c == 'T')
					return val * 1024 * 1024 * 1024 * 1024;
				return val;
			}
		}
		return parseInt(sval);
	}
	return parseInt(bytes);
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

// Unhide the view button
var show_view_button = function () {
	$('#view_btn').removeClass('d-none');
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
	$('html,body').animate({scrollTop: ele_alert.offset().top}, 'fast');
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

var handle_qserv_success = function (result, on_ready, extra = '', on_error = function () {}) {
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
				if (typeof on_error === 'function')
					on_error();
				handled = true;
			}
		}
	}
	
	if (!handled) { // system communication was ok but response was unexpected
		show_alert(t("Unknown error"), "danger");
		if (typeof on_error === 'function')
			on_error();
	}
}

// Query server wrapper.
var qserv = function (path, args, on_ready, extra = '', on_error = function () {}) {
	$.post(path, args, function(result, status) {
		if (status == "success")
			handle_qserv_success(result, on_ready, extra, on_error);
		else {
			show_alert(t("System error"), "danger");
			if (typeof on_error === 'function')
				on_error();
		}
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

// Get the string of the current language.
var get_language_string = function (str) {
	str = str.trim();
	if (str.length > 0) {
		if (typeof language_strings === 'object') {
			if (str in language_strings) {
				var lstr = language_strings[str];
				lstr = lstr.trim();
				if (lstr.length > 0)
					str = lstr;
			}
		}
	}
	return str;
}
 
// Translate string
var t = function (str, params) {
	str = get_language_string(str);
	if (str.length > 0) {	
		if (typeof params === 'object') {
			for (var p in params) {
				str = str.replaceAll(p, params[p]);
			}
		}
	}
	return str;	
}

// Translate all elements texts.
var te = function () {
	$('title').text(get_language_string($('title').text()));

	var tnodes = getTextNodesIn($('body')[0]);	
	$.each(tnodes, function(index, tn) { 
		tn.nodeValue = get_language_string(tn.nodeValue);
	});
	
	$('form').find("input").each(function(ev) {
		var ph = $(this).attr("placeholder");
		
		if (typeof ph !== 'undefined' && ph !== false) {
			$(this).attr("placeholder", get_language_string(ph));
		}
	});	
}

var set_visitor_language = function (visitor_language, on_ready) {
	qserv(main_server, {model: 'system', action: 'set_visitor_language', language: visitor_language}, function(rst, extra) {
		if (typeof on_ready === 'function')
			on_ready(rst.data);
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
		
	$.getJSON(languages_path + lang + '.json', function (data) {
		language = lang;
		language_strings = data;
		
		te();
		
		if (typeof on_ready === 'function')
			on_ready();
	});
}

// Load module language strings and append them into the existing language strings.
// And translate the current page.
// And trigger on_ready() if specified.
var load_module_language = function (module, on_ready) {
	var lang = language;
	if (lang == 'en') { // default language
		if (typeof on_ready === 'function')
			on_ready();
			
		return;
	}
	
	var path = modules_path + module + '/languages/' + lang + '.json'
	$.getJSON(path, function (data) {

		for (var i in data) {
			language_strings[i] = data[i];
		}
		
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
var add_home_menu_item = function (name, icon, title, description, url) {

	var ele_counter = $("<div>", {
		class : "home-icon-counter",
		style : "position: absolute; top: 0; right: 0; z-index: 10;",
		id : "home-menu-" + name + "-counter",
		text : ""
	});

	var ele_icon = $("<i>", {
		class : "home-icon bi-" + icon,
		style : "position: relative;",
		append : [
			ele_counter
		]
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
		id : "home-menu-" + name,
		append : [
			ele_icon_url,
			ele_title,
			$("<p>", {"text" : description})
		]
	});

	$("#home_menu").append(ele_col);
}

// Load home menu from preferences.
// Standard *type* are *admin* and *main*.
var load_home_menu = function (type, on_ready) {
	var name = "home_" + type + "_menu_item_";
	qserv(main_server, {model: 'preference', action: 'read_starts_with',
		name: name}, function(rst, extra) {
			var mnames = [], items = [];
			var r = rst.data.records;
			for (var i in r) {
				if (r.hasOwnProperty(i)) {
					var pval = r[i].value;
					try {
						var pobj = JSON.parse(pval);
						items.push(pobj);
					} catch (e) {
						// show_alert("Invalid menu item object", 'warning');
					}					
				}
			}
			items.sort(function(a, b) {
				return a.w - b.w;
			});
			items.forEach(function(mi){
				var url = mi.u, module = false;
				var re = /(?:\.([^.]+))?$/; // regular expression to find file extension
				var mname = url, ext = re.exec(url)[1];
				if (typeof ext !== "undefined")
					mname = url.substring(0, url.indexOf("." + ext));
				mnames.push(mname);
				if (typeof mi.m !== "undefined") {
					if (mi.m.length > 0) {
						module = true;
						var mtype = type.indexOf('admin') >= 0 ? 'admin' : 'main';
						url = modules_path + mi.m + "/" + mtype + "/" + mi.u;
						load_module_language(mi.m);
					}
				}
				if (!module)
					add_home_menu_item(mname, mi.i, t(mi.t), t(mi.d), url);
				else {
					// Wait for the module language to be loaded,
					// mostly from the browser cache. It should be fast.
					setTimeout(function() {
						add_home_menu_item(mname, mi.i, t(mi.t), t(mi.d), url); 
					}, 200);
				}
			})
			if (typeof on_ready === "function")
				on_ready(mnames);			
	});
}

// Create a new home menu item if not already existed.
// Standard *type* are *admin* and *main*.
var new_home_menu_item = function (type, icon, title, description, module, url) {
	var name = "home_" + type + "_menu_item_";
	qserv(main_server, {model: 'preference', action: 'read_starts_with',
		name: name}, function(rst, extra) {
		// get last index
		var index = 0, isnew = true, nlen = name.length;
		var r = rst.data.records;
		for (var i in r) {
			if (r.hasOwnProperty(i)) {
				var ival = r[i].value;
				try {
					var iobj = JSON.parse(ival);
					if (iobj.t == title) {
						isnew = false;
						break;
					}
				} catch (e) {
					// show_alert("Invalid menu item object", 'warning');
				}					
				var iname = r[i].name;
				var idx = parseInt(iname.substring(nlen));
				if (idx > index)
					index = idx;
			}
		}
		if (isnew) {
			++index; // new index
			var nobj = {w:(index*10),i:icon,t:title,d:description,m:module,u:url};
			var njson = JSON.stringify(nobj);
			var nname = name + index;
			//console.log(nname);
			qserv(main_server, {model: 'preference', action: 'write',
				name: nname, value: njson}, function(rst, extra) {
					show_alert(t("A home menu item created."), "success");
				});
		}
	});
}

// Load preferences with name starts with *site*.
// And call on_ready(data) when the data is ready.
var load_site_info = function (on_ready) {
	if (typeof on_ready === 'function')
		qserv(main_server, {model: 'preference', action: 'read_site',
			}, function(rst, extra) {
			on_ready(rst.data);
		});
}

// Load preferences with name starts with *name*.
// And call on_ready(data) when the data is ready.
var load_preferences = function (name, on_ready) {
	if (typeof on_ready === 'function')
		qserv(main_server, {model: 'preference', action: 'read_starts_with',
			name: name}, function(rst, extra) {
			on_ready(rst.data);
		});
}

var count_unread_post_resp = function (rst, extra) {
	if (rst.data.count > 0) {
		var count = 0;
		
		if (unread_post_counts.hasOwnProperty(extra.discussion_typeref))
			count = unread_post_counts[extra.discussion_typeref];
			
		count += rst.data.unread_count;
		unread_post_counts[extra.discussion_typeref] = count;

		if (count >= rst.data.unread_count_limit)
			count = count.toString() + "+";
		else if (rst.data.count >= rst.data.limit)
			count_unread_post(0, extra.discussion_typeref, extra.count_handler); // recursive
		
		if (count !== 0 && (typeof extra.count_handler === "function")) {
			extra.count_handler(extra.discussion_typeref, count);
		}
	}
}

var count_unread_post = function (restart, discussion_typeref, count_handler) {
	if (restart == 1)
		unread_post_counts[discussion_typeref] = 0;
		
	qserv(main_server, {model: 'post', action: 'count_unread',
		restart: restart, discussion_typeref: discussion_typeref},
		count_unread_post_resp, {count_handler: count_handler, discussion_typeref: discussion_typeref});
}

