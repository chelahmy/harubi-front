<?php
// common.php
// Harubi-Front Common
// By Abdullah Daud, chelahmy@gmail.com
// 12 November 2022

require_once 'harubi/harubi.php';
require_once 'filerepo.php';
require_once 'VideoStream.php';

// Application name will be loaded from settings when it is empty.
// It should be unique for every application that uses this framework.
// Otherwise, user session may criss-cross among applications.
$appname = ''; // leave it empty

session_start();

if (isset($harubi_settings_path) && strlen($harubi_settings_path) > 0)
	harubi($harubi_settings_path);
else
	harubi();
	
if (isset($harubi_settings) && isset($harubi_settings["frepo_rel_root"]))
	$frepo_rel_root = $harubi_settings["frepo_rel_root"];
	
define("err_unknown", 0);
define("err_not_signed_in", 1);
define("err_try_signup_later", 2);
define("err_signup_failed", 3);
define("err_user_exists", 4);
define("err_user_update_failed", 5);
define("err_user_restricted", 6);
define("err_signin_failed", 7);
define("err_access_denied", 8);
define("err_create_failed", 9);
define("err_read_failed", 10);
define("err_update_failed", 11);
define("err_delete_failed", 12);
define("err_record_already_exists", 13);
define("err_cannot_change_password", 14);
define("err_cannot_delete", 15);
define("err_record_missing", 16);
define("err_cannot_create_ref", 17);

$error_messages = array(
	err_unknown => "Unknown error.",
	err_not_signed_in => "User had not signed in.",
	err_try_signup_later => "Could not sign up new user now. Please try again later.",
	err_signup_failed => "Could not sign up new user.",
	err_user_exists => "User name already exists. Please use a different name.",
	err_user_update_failed => "Could not update user record.",
	err_user_restricted => "The user has been restricted.",
	err_signin_failed => "Could not sign in user.", // for security reason: should not give more details on why the sign in failed
	err_access_denied => "Access denied.",
	err_create_failed => "Record creation failed.",
	err_read_failed => "Record reading failed.",
	err_update_failed => "Record updating failed.",
	err_delete_failed => "Record deletion failed.",
	err_record_already_exists => "Record already exists.",
	err_cannot_change_password => "Cannot change password.",
	err_cannot_delete => "Cannot delete record.",
	err_record_missing => "Record missing.",
	err_cannot_create_ref => "Cannot create ref.",
);

function error_pack($code, $ext = "", $ext_param = array())
{
	global $error_messages, $harubi_settings, $harubi_logs;
	
	$logs = false;
	
	if (isset($harubi_settings) && isset($harubi_settings['logs']))
		$logs = intval($harubi_settings['logs']) > 0;
		
	return array(
		'status' => 0,
		'data' => array(
			'error' => $code,
			'error_msg' => array_key_exists($code, $error_messages) ?
				$error_messages[$code] : $error_messages[err_unknown],
			'error_ext' => $ext,
			'error_ext_params' => $ext_param,
			'logs' => $logs ? print_r($harubi_logs, true) : ''
		)
	);
}

// Get the application name from the settings.
function get_appname() {
	global $harubi_settings, $appname;
	
	if (strlen($appname) <= 0) {
		if (isset($harubi_settings) && isset($harubi_settings['appname']))
			$appname = $harubi_settings['appname'];

		if (strlen($appname) <= 0)
			$appname = 'harubi-front';
	}
		
	return $appname;
}

// Get the unique application session.
// This framework may be used by many applications that the same user use at the same time.
// Each application must have a unique session.
function get_session($name) {
	$an = get_appname();
	
	if (!isset($_SESSION[$an]))
		return NULL;
	
	if (strlen($name) > 0)
		return $_SESSION[$an][$name];
	
	return NULL;
}

function set_session($name, $val) {
	$an = get_appname();

	if (!isset($_SESSION[$an]))
		$_SESSION[$an] = array();
		
	if (strlen($name) > 0)
		$_SESSION[$an][$name] = $val;
}

function unset_session($name) {
	$an = get_appname();

	if (!isset($_SESSION[$an]))
		return;
		
	if (strlen($name) > 0)
		unset($_SESSION[$an][$name]);
}

function unset_session_all() {
	$an = get_appname();

	if (!isset($_SESSION[$an]))
		return;
		
	unset($_SESSION[$an]);
}

function isset_session($name) {
	$an = get_appname();

	if (!isset($_SESSION[$an]))
		return FALSE;
		
	if (strlen($name) > 0)
		return isset($_SESSION[$an][$name]);
		
	return FALSE;
}

function esc_str($str, $like = FALSE) {
	$db = connect_db();

	if ($db === FALSE)
		return FALSE;
	
	return esc($db, $str, $like);
}

function get_row_count($tablename) {
	$records = read(array('table' => $tablename, 'count' => TRUE));
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		return intval($records[0]['count']);
	}
	
	return 0;
}


// Generate a unique new table row ref.
// The table must has a ref column of varchar[32].
function new_table_row_ref($tablename) {
	$rc = get_row_count($tablename);
	
	if ($rc <= 0) {
		$bytes = random_bytes(16);
		return bin2hex($bytes);
	}
	
	for ($i = 0; $i < 3; $i++) {
		$bytes = random_bytes(16);
		$ref = bin2hex($bytes);
		$where = equ('ref', $ref, 'string');
		$records = read($tablename, FALSE, $where);
		$rcnt = record_cnt($records);
		
		if ($rcnt > 0)
			continue;
	
		return $ref;
	}
	
	return '';
}

function get_user_count() {
	$records = read(array('table' => 'user', 'count' => TRUE));
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		return intval($records[0]['count']);
	}
	
	return 0;
}

function get_userid_by_name($username) {
	$where = equ('name', $username, 'string');
	$records = read('user', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		return intval($records[0]['id']);
	}
	
	return 0;
}

function get_username_by_id($userid) {
	if ($userid <= 0)
		return '';
		
	$where = equ('id', $userid);
	$records = read('user', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		return $records[0]['name'];
	}
	
	return '';
}

function get_username_and_avatar($userid) {
	if ($userid <= 0)
		return FALSE;
		
	$where = equ('id', $userid);
	$records = read('user', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		return array(
			'avatar' => $records[0]['avatar'],
			'name' => $records[0]['name']
		);
	}
	
	return FALSE;
}

function inc_user_signins($userid, $signins) {
	$now = time();
	$where = equ('id', $userid);
	return update('user', array('signins' => ++$signins, 'last_signedin_utc' => $now), $where);

}

function get_roleid_by_name($rolename) {
	$where = equ('name', $rolename, 'string');
	$records = read('role', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		return intval($records[0]['id']);
	}
	
	return 0;
}

function get_rolename_by_id($roleid) {
	if ($roleid <= 0)
		return '';
		
	$where = equ('id', $roleid);
	$records = read('role', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		return $records[0]['name'];
	}
	
	return '';
}

function get_premium_role_ids() {
	$roles = array();
	$where = equ('premium', 1);
	$records = read('role', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
	
		foreach ($records as $r) {
			$roles[] = $r['id'];
		}
	}

	return $roles;
}

// Get permission role ids
function get_perm_roleids($permname) {

	$roleids = array();
	$permid = 0;
	$where = equ('name', $permname, 'string');
	$records = read('permission', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0)
		$permid = intval($records[0]['id']);
	else
		return $roleids;
		
	if ($permid > 0) {
		$where = equ('permissionid', $permid);
		$records = read('permrole', FALSE, $where);
		$rcnt = record_cnt($records);

		if ($rcnt > 0) {
			foreach ($records as $r) {
				$rid = intval($r['roleid']);
				if (!in_array($rid, $roleids))
					$roleids[] = $rid;
			}
		}
	}
		
	return $roleids;
}

function get_discussion_type($discussion_name) {
	$parts = explode(":", $discussion_name);

	if (!is_array($parts) || count($parts) < 2)
		return FALSE;

	$name = $parts[0] . ":" . $parts[1];	
	$where = equ('name', $name, 'string');

	$records = read('discussion_type', FALSE, $where);
	
	if (record_cnt($records) > 0)
		return $records[0];
	
	$autofollow = 0;
	$pref = get_preference("discussion_" . $name);
	
	if (strlen($pref) > 0) {
		$prop = json_decode($pref, TRUE);
		
		if (is_array($prop)) {
			if (isset($prop['autofollow']))
				$autofollow = intval($prop['autofollow']);
		}
	}
	
	$id = create('discussion_type', array(
			'name' => $name,
			'autofollow' => $autofollow
		));
		
	if ($id > 0)
		return array('id' => $id, 'name' => $name, 'autofollow' => $autofollow);
		
	return FALSE;
}

// Get discussion ref, or create one.
function get_discussion_ref($discussion_name) {
	$where = equ('name', $discussion_name, 'string');

	$records = read('discussion', FALSE, $where);
	
	if (record_cnt($records) > 0)
		return $records[0]['ref'];
	
	$ref = new_table_row_ref('discussion');
	
	if (strlen($ref) > 0) {
		$autofollow = 0;
		$discussion_type_id = 0;
		$discussion_type = get_discussion_type($discussion_name);
		
		if ($discussion_type !== FALSE && is_array($discussion_type)) {
			$discussion_type_id = intval($discussion_type['id']);
			$autofollow = intval($discussion_type['autofollow']);
		}
		
		$id = create('discussion', array(
			'ref' => $ref,
			'name' => $discussion_name,
			'discussion_type_id' => $discussion_type_id,
			'autofollow' => $autofollow
		));
		
		if ($id > 0)
			return $ref;
	}
		
	return '';
}

function get_discussion_by_ref($discussion_ref) {
	$where = equ('ref', $discussion_ref, 'string');
	$records = read('discussion', FALSE, $where);
	
	if (record_cnt($records) > 0)
		return $records[0];

	return FALSE;
}

function get_discussion_id_by_name($discussion_name) {
	$discussion_id = 0;
	$where = equ('name', $discussion_name, 'string');
	$records = read('discussion', FALSE, $where);
	
	if (record_cnt($records) > 0)
		$discussion_id = intval($records[0]['id']);

	return $discussion_id;
}

function get_discussion_id_by_ref($discussion_ref) {
	$discussion_id = 0;
	$where = equ('ref', $discussion_ref, 'string');
	$records = read('discussion', FALSE, $where);
	
	if (record_cnt($records) > 0)
		$discussion_id = intval($records[0]['id']);

	return $discussion_id;
}

function get_discussion_ref_by_id($discussion_id) {
	$discussion_ref = '';
	$where = equ('id', intval($discussion_id));
	$records = read('discussion', FALSE, $where);
	
	if (record_cnt($records) > 0)
		$discussion_ref = $records[0]['ref'];

	return $discussion_ref;
}

function follow_discussion($discussion_id, $follow) {
	$discussion_id = intval($discussion_id);
	
	if ($discussion_id <= 0)
		return FALSE;
		
	$userid = signedin_uid();
	
	if ($userid <= 0)
		return FALSE;
		
	$where = equ('discussion_id', intval($discussion_id)) . " AND " . equ("userid", $userid);
	$records = read('discussion_follower', FALSE, $where);
	
	if (record_cnt($records) > 0) {
		if ($follow == 0)
			if (!delete('discussion_follower', $where))
				return FALSE;
	}
	else if ($follow == 1) {
		$now = time();
		$id = create('discussion_follower', array(
			'discussion_id' => $discussion_id,
			'userid' => $userid,
			'created_utc' => $now,
		));
		
		if ($id <= 0)
			return FALSE;
	}
	
	return TRUE;
}

// Check if the current user is following a discussion.
function is_following_discussion($discussion_id) {
	$userid = signedin_uid();
	
	if ($userid <= 0)
		return FALSE;
		
	$where = equ('discussion_id', intval($discussion_id)) . " AND " . equ("userid", $userid);
	$records = read('discussion_follower', FALSE, $where);
	
	if (record_cnt($records) > 0)
		return TRUE;
	
	return FALSE;
}

function update_postread($userid, $discussion_id, $discussion_type_id, $lastread_postid) {
	$now = time();
	$lastread_postid = intval($lastread_postid);
	$where = equ('userid', intval($userid)) . " AND " . equ('discussion_id', intval($discussion_id));
	$records = read('postread', FALSE, $where);

	if (record_cnt($records) > 0) {
		$lrpostid = intval($records[0]['lastread_postid']);
		
		if ($lrpostid < $lastread_postid) {
			if (!update('postread', array('lastread_postid' => $lastread_postid, 'updated_utc' => $now), $where))
				return FALSE;
		}
		
		return intval($records[0]['id']);
	}
	else {
		$id = create('postread', array(
			'userid' => $userid,
			'discussion_id' => $discussion_id,
			'discussion_type_id' => $discussion_type_id,
			'lastread_postid' => $lastread_postid,
			'created_utc' => $now,
			'updated_utc' => $now
		));
		
		if ($id > 0)
			return $id;
	}
	
	return FALSE;
}

function delete_postread($userid, $groupref) {
	$discussion_ref = get_discussion_ref('table:usergroup:' . $groupref);
	$discussion_id = get_discussion_id_by_ref($discussion_ref);
	
	if ($discussion_id > 0) {
		$where = equ('userid', $userid) . ' AND ' . equ('discussion_id', $discussion_id);
		if (!delete('postread', $where))
			return FALSE;
	}

	return TRUE;
}

// Get group info:
// int		'uid'				- signed-in user id.
// bool		'owner'				- is the signed-in user the group owner.
// bool		'member'			- is the signed-in user a member of the group.
// bool		'admin'				- id the signed-in user an admin of the group.
// array	'member_records'	- the group member records.
function get_group_info($groupref) {
	$info = array('owner' => FALSE, 'member' => FALSE, 'admin' => FALSE,
		'uid' => 0, 'group_records' => FALSE, 'member_records' => FALSE);
	$where = equ('ref', $groupref, 'string');
	$records = read('usergroup', FALSE, $where);
	$info['group_records'] = $records;
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		$uid = signedin_uid();
		$info['uid'] = $uid;
		$owneruserid = intval($records[0]['owneruserid']);
		
		if ($uid == $owneruserid)
			$info['owner'] = TRUE;
		else {
			$groupid = intval($records[0]['id']);
			$where = equ('usergroupid', $groupid) . ' AND ' . equ('userid', $uid);
			$records = read('member', FALSE, $where);
			$info['member_records'] = $records;
			$rcnt = record_cnt($records);

			if ($rcnt > 0) {
				$info['member'] = TRUE;
				$admin_roleid = get_roleid_by_name('administrator');
				
				if ($admin_roleid > 0 && $admin_roleid == intval($records[0]['roleid']))
					$info['admin'] = TRUE; 
			}
		}
	}
	
	return $info;
}

function get_preference($name, $default = '') {
	// Try to get it from session
	$ses_preference = "preference_" . $name;
	
	if (isset_session($ses_preference))
		return get_session($ses_preference);

	// Otherwise, get it from the database
	$where = equ('name', $name, 'string');
	$records = read('preference', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		$pref = $records[0]['value'];
		set_session($ses_preference, $pref);
		return $pref;
	}
	
	return $default;
}

function set_preference($name, $value) {
	$where = equ('name', $name, 'string');
	$records = read('preference', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		$id = intval($records[0]['id']);
		
		if ($id > 0) {
			if (!update('preference', array('value' => $value), $where))
				return -1;
		}
	}
	else {
		$id = create('preference', array(
			'name' => $name,
			'value' => $value
		));
	}

	$ses_preference = "preference_" . $name;
	set_session($ses_preference, $value);
	
	return $id;	
}

function get_language() {
	// Try to get it from preference
	$lang = get_preference('language');
	
	if (strlen($lang) > 0)
		return $lang;
	
	// Otherwise, get it from settings
	if (isset($harubi_settings)) {
		if (isset($harubi_settings['language'])) {
			$lang = $harubi_settings['language'];
			if (strlen($lang) > 0)
				return $lang;
		}
	}
	
	return 'en'; // default
}

function get_page_size() {
	// Try to get it from session
	$ses_page_size = "preference_page_size";
	
	if (isset_session($ses_page_size)) {
		$ps = intval(get_session($ses_page_size));
		
		if ($ps > 0)
			return $ps;
	}

	// Or, try to get it from preference
	$ps = intval(get_preference('page_size'));
	
	if ($ps > 0) {
		set_session($ses_page_size, $ps);
		return $ps;
	}
		
	// Otherwise, get it from settings
	if (isset($harubi_settings)) {
		if (isset($harubi_settings['page_size'])) {
			$ps = intval($harubi_settings['page_size']);
			if ($ps > 0) {
				set_session($ses_page_size, $ps);
				return $ps;
			}
		}
	}
	
	return 25; // default
}

function get_new_user_roleid() {
	global $harubi_settings;
	
	$role = get_preference('new_user_role');
	
	if (strlen($role) <= 0) {
		if (isset($harubi_settings) && isset($harubi_settings['new_user_role']))
			$role = $harubi_settings['new_user_role'];
	}
	
	if (strlen($role) <= 0)
		$role = 'member';
		
	return get_roleid_by_name($role);
}

function get_session_timeout() {
	global $harubi_settings;
	
	$timeout = get_preference('session_timeout');
	
	if (strlen($timeout) > 0)
		return intval($timeout);
	elseif (isset($harubi_settings) && 
		isset($harubi_settings['session']) &&
		isset($harubi_settings['session']['timeout'])) {
		return intval($harubi_settings['session']['timeout']);	
	}
	
	return 0; // no timeout
}

function remember_me_days() {
	global $harubi_settings;
	
	$rmd = get_preference('session_remember_me_days');
	
	if (strlen($rmd) <= 0) {
		if (!isset($harubi_settings) || !isset($harubi_settings['session']) || 
			!isset($harubi_settings['session']['remember_me_days']))
			return 0;

		return intval($harubi_settings['session']['remember_me_days']);
	}

	return intval($rmd);
}

function user_str_session() {
	return get_appname() . '_user_str_session';
}

// Register 'remember me'.
// Only one device allowed.
function reg_remember_me() {
	$rmd = remember_me_days();
	
	if ($rmd <= 0) // no duration setting, so no remember_me
		return FALSE;
		
	$userid = get_session('uid');
	
	if ($userid <= 0)
		return FALSE;
	
	$cookie_name  = user_str_session();
	$cookie_value = hash('sha512', $userid . $_SERVER['HTTP_USER_AGENT'] . time());
	setcookie($cookie_name, $cookie_value, time() + (86400 * $rmd), "/");

	$now = time();

	$where = equ('userid', $userid);
	$records = read('remember_me', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		$id = intval($records[0]['id']);
		
		if ($id <= 0)
			return FALSE;
			
		if (!update('remember_me', array(
			'userstr' => $cookie_value,
			'created_utc' => $now
			), $where))
			return FALSE;
	}
	else {
		$id = create('remember_me', array(
			'userid' => $userid,
			'userstr' => $cookie_value,
			'created_utc' => $now,
		));
		
		if ($id <= 0)
			return FALSE;
	}
			
	return TRUE;
}

function unreg_remember_me() {
	$cookie_name  = user_str_session();
	setcookie($cookie_name, "", time() - 3600, "/");
}

function check_remember_me() {
	if (isset_session('uid')) // already in a session
		return;
		
	$cookie_name  = user_str_session();
	
	if (!isset($_COOKIE[$cookie_name]))
		return;
		
	$user_string = $_COOKIE[$cookie_name];
	
	if (strlen($user_string) <= 0)
		return; 
	
	$where = equ('userstr', $user_string);
	$records = read('remember_me', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt <= 0)
		return;
		
	$uid = intval($records[0]['userid']);
	
	if ($uid <= 0)
		return;

	$where = equ('id', $uid);
	$records = read('user', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt <= 0)
		return;
		
	$premium = FALSE;
	$name = $records[0]['name'];
	$uroleid = intval($records[0]['roleid']);
	
	if ($uid == 1) { // superadmin
		set_session('admin_uid', $uid);
		$premium = TRUE;
	}
	else {
		$rolename = get_rolename_by_id($uroleid);
	
		if ($rolename == 'administrator') { // administrator
			set_session('admin_uid', $uid);
			$premium = TRUE;
		}
		else {
			$prids = get_premium_role_ids();

			if (in_array($uroleid, $prids)) // premium member
				$premium = TRUE;
		}
	}

	set_session('uid', $uid);
	set_session('uname', $name);
	set_session('uroleid', $uroleid);
	set_session('upremium', $premium);
	set_session('last_accessed', time());
	set_session('language', $records[0]['language']);
}

// Sign out the current user
function sign_out() {
	set_session('uid', 0);
	unset_session('uid');
	set_session('admin_uid', 0);
	unset_session('admin_uid');
	set_session('uname', '');
	unset_session('uname');
	set_session('uroleid', 0);
	unset_session('upremium');
	set_session('upremium', FALSE);
	unset_session('uroleid');
	unset_session('last_accessed');
	unset_session('language');
	
	unset_session_all();
}

function is_signedin($uid = 0)
{
	check_remember_me();
	
	if (!isset_session('uid') || get_session('uid') <= 0)
		return false;
		
	if ($uid > 0 && get_session('uid') != $uid)
		return false;

	$timeout = get_session_timeout();
	
	if ($timeout > 0 && isset_session('last_accessed'))
	{
		$duration = time() - intval(get_session('last_accessed'));
		
		if ($duration > $timeout) // timed out
		{
			// sign out user
			sign_out();
			
			return false; 
		}
	}
		
	set_session('last_accessed', time());
	
	return true;
}

function is_signedin_admin()
{
	return (isset_session('admin_uid') && intval(get_session('admin_uid')) > 0);
}

function signedin_language()
{
	if (isset_session('language'))
		return get_session('language');
		
	return get_language(); // default language
}

function signedin_uid()
{
	if (isset_session('uid'))
		return intval(get_session('uid'));
		
	return 0;
}

function signedin_uname()
{
	if (isset_session('uname'))
		return get_session('uname');
		
	return '';
}

function is_signedin_premium()
{
	if (isset_session('upremium'))
		return get_session('upremium');
		
	return FALSE;
}

function split_signin_note($note)
{
	$note = strval($note);
	
	if (strlen($note) < 4)
		return false;

	$w = intval(substr($note, 1, 2));
	$c = intval(substr($note, 3));
	
	return array(
		'week' => $w, 
		'count' => $c
	);
}

function inc_signin_note($note)
{
	$w = 0;
	$c = 0;
	$sn = split_signin_note($note);

	if (is_array($sn))
	{
		$w = $sn['week'];
		$c = $sn['count'];
	}

	$dt = new DateTime();
	$tw = intval($dt->format('W'));
	
	if ($tw > $w)
		$c = 0;
	else
		++$c;
		
	return intval(sprintf("1%02d%02d", $tw, $c));
}

function max_signins_per_week() {
	global $harubi_settings;
	
	$spw = get_preference('session_max_signins_per_week');
	
	if (strlen($spw) <= 0) {
		if (!isset($harubi_settings) || !isset($harubi_settings['session']) || 
			!isset($harubi_settings['session']['max_signins_per_week']))
			return 0;

		return intval($harubi_settings['session']['max_signins_per_week']);
	}

	return intval($spw);
}

function is_signin_restricted($note)
{
	$spw = max_signins_per_week();

	if ($spw <= 0)
		return false;

	$sn = split_signin_note($note);

	if ($sn === false)
		return false;
	
	$dt = new DateTime();
	$w = intval($dt->format('W'));
	
	if ($w > $sn['week']) // new week
		return false;
	
	if ($sn['count'] < $spw)
		return false;
		
	return true;
}

function status_not_signedin()
{
	return error_pack(err_not_signed_in);
}

// Renew the current user last access time.
function renew_access_time() {	

	if (isset_session('uid') && isset_session('last_accessed')) {
		$timeout = get_session_timeout();
		$last = intval(get_session('last_accessed'));
		
		if ($timeout == 0 || (time() - $last) < $timeout)
			set_session('last_accessed', time());
	}
}

// Check if the current user has an access permission and is still within the session allowed time.
// This function is called everytime a beat() is called unless it is public.
// If the user has the permission then his last access timestamp will also be renewed.
function has_permission($permname) {
	
	// Allow for the first time system provisioning with no superuser
	if ($permname == 'system_provision') {
		$ucnt = get_user_count();
		
		if ($ucnt <= 0)
			return TRUE;
	}
	
	check_remember_me();
	$valid_user = false;
	
	// Administrator is always allowed.
	if (isset_session('admin_uid')) {
		$admin_uid = intval(get_session('admin_uid'));
		
		if ($admin_uid > 0)
			$valid_user = true;
	}	
	// For other user, check for permission.
	elseif (isset_session('uid') && isset_session('uroleid')) {
		$uid = intval(get_session('uid'));
		$uroleid = intval(get_session('uroleid'));
		
		if ($uid > 0 && $uroleid > 0) {
			$roleids = get_perm_roleids($permname);
			
			if (count($roleids) > 0 && in_array($uroleid, $roleids))
				$valid_user = true;
		}
	}
	
	if ($valid_user) {
		// check for session timeout
		$timeout = get_session_timeout();
		
		if ($timeout == 0)
			return true;
			
		if (isset_session('last_accessed')) {
			$last = intval(get_session('last_accessed'));
			
			if ((time() - $last) < $timeout) {
				set_session('last_accessed', time()); // renew timestamp
				return true;
			}
		}
	}

	sign_out();
	
	return false;
}

beat('system', 'heartbeat', function ()
{
	is_signedin(); // this will update the last access time

	return array(
		'status' => 1
	);
});

beat('system', 'set_visitor_language', function ($language)
{
	if ($language == 'en' || $language == 'ms') { // TODO: generalize this
		set_session('visitor_language', $language);
	}
	
	return array(
		'status' => 1,
		'data' => array(
			'visitor_language' => get_session('visitor_language')
		)
	);
});

beat('system', 'signedin', function ()
{
	$is_signedin = is_signedin(); // this will update the last access time
	
	return array(
		'status' => 1,
		'data' => array(
			'is_signedin' => $is_signedin,
			'is_signedin_admin' => is_signedin_admin(),
			'is_signedin_premium' => is_signedin_premium(),
			'signedin_language' => signedin_language(),
			'signedin_uname' => signedin_uname()
		)
	);
});

beat('user', 'has_permission', function ($permname)
{
	if (!has_permission($permname))
		return error_pack(err_access_denied);
	
	return array(
		'status' => 1
	);
});

beat('user', 'signup', function ($name, $password, $email, $remember_me = FALSE)
{
	// Delay next user sign up for 5 minutes to deter spamming
	if (isset_session('last_reg') && (time() - get_session('last_reg')) < 300)
		return error_pack(err_try_signup_later);
	
	$where = equ('name', $name, 'string');
	$records = read('user', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed);
	
	$rcnt = record_cnt($records);

	if ($rcnt <= 0) // no $name user exist, so create a new one
	{
		$now = time();
		$hash = password_hash($password, PASSWORD_BCRYPT);
		$uroleid = get_new_user_roleid();
		$ulang = get_language();
		$id = create('user', array(
			'name' => $name,
			'avatar' => '',
			'password' => $hash,
			'email' => $email,
			'roleid' => $uroleid,
			'language' => $ulang,
			'valid_thru' => 0,
			'signins' => 0,
			'last_signedin_utc' => 0,
			'signin_note' => 0,
			'created_utc' => $now,
			'updated_utc' => $now
		));    

		if ($id > 0)
		{
			set_session('last_reg', $now);
			
			// sign in the new user
			inc_user_signins($uid, 0);
			set_session('uid', $id);
			set_session('uname', $name);
			set_session('uroleid', $uroleid);
			set_session('last_accessed', $now);
			set_session('language', $ulang);

			if ($remember_me)
				reg_remember_me();

			return array(
				'status' => 1
			);
		}
				
		return error_pack(err_signup_failed);
	}

	return error_pack(err_user_exists);
});

beat('user', 'signin', function ($name, $password, $remember_me = FALSE)
{    
	$now = time();
	$where = equ('name', $name, 'string');
	$records = read('user', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed);
	
	$rcnt = record_cnt($records);

	if ($rcnt > 0) // user exists
	{
		if (password_verify($password, $records[0]['password']))
		{
			$allowed = TRUE;
			$all_signins = intval($records[0]['signins']);
			$data = array(
				'admin' => false,
				'premium' => false, 
				'signins' => 1, 
				'max_signins_per_week' => 0,
				'all_signins' => $all_signins
			);
			$uid = intval($records[0]['id']);
			$uroleid = intval($records[0]['roleid']);
			
			if ($uid == 1) { // superadmin
				set_session('admin_uid', $uid);
				$data['admin'] = true;
				$data['premium'] = true;
			}
			else {
				$prids = get_premium_role_ids();
				$rolename = get_rolename_by_id(intval($records[0]['roleid']));
			
				if ($rolename == 'administrator') { // administrator
					set_session('admin_uid', $uid);
					$data['admin'] = true;
					$data['premium'] = true;
				}
				elseif (in_array($uroleid, $prids)) // premium member
					$data['premium'] = true;
				elseif (!is_signedin($uid)) // on new sign-in for non-administrator or non-premium member
				{
					// certain users may have limited access
					$valid = intval($records[0]['valid_thru']);
					$note = $records[0]['signin_note'];
										
					if ($valid < $now) // no validity or expired
					{
						if (is_signin_restricted($note))
							$allowed = FALSE;
						else
						{
							// update signin note
							$note = inc_signin_note($note);
							
							if (!update('user', array('signin_note' => $note, 'updated_utc' => $now), equ('id', $uid)))
								return error_pack(err_user_update_failed);
								
							$note_data = split_signin_note($note);
							$data['signins'] = $note_data['count'] + 1;
							$data['max_signins_per_week'] = max_signins_per_week();
						}
					}
					else
						$data['premium'] = true;
				}
			}
			
			if ($allowed)
			{
				if (inc_user_signins($uid, $all_signins))
					$data['all_signins'] = $data['all_signins'] + 1;
				
				set_session('uid', $uid);
				set_session('uname', $name);
				set_session('uroleid', $uroleid);
				set_session('upremium', $data['premium']);
				set_session('last_accessed', time());
				set_session('language', $records[0]['language']);
				
				if ($remember_me)
					reg_remember_me();
				
				return array(
					'status' => 1,
					'data' => $data
				);
			}
			else
				return error_pack(err_user_restricted);
		}
	}

	return error_pack(err_signin_failed);
});

beat('user', 'signout', function ()
{
	unreg_remember_me();
	sign_out();
				
	return array(
		'status' => 1,
	);
});

beat('preference', 'read_site', function ()
{
	// Note: No access control for public data
	
	$where = "name LIKE 'site%'";
	$records = read('preference', FALSE, $where, 'name', 'ASC');
	$rcnt = record_cnt($records);
	$has_lang = FALSE;

	if ($rcnt > 0) {	
		foreach ($records as &$r) {
			unset($r['id']);
			if ($r['name'] == 'site_language') {
				$has_lang = TRUE;
				if (isset_session("visitor_language"))
					$r['value'] = get_session("visitor_language"); // overwrite with the visitor selected language
				elseif (isset_session("language"))
					$r['value'] = get_session("language"); // overwrite with the signed in user selected language
			}
		}
	}
	else {
		$rcnt = 0;
		$records = array();
	}

	if (!$has_lang) {
		if (isset_session("visitor_language"))
			$lang = get_session("visitor_language");
		elseif (isset_session("language"))
			$lang = get_session("language");
		else
			$lang = get_language();
		
		$records[] = array(
			'name'=> 'site_language',
			'value' => $lang
			);
		++$rcnt;
	}

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('preference', 'read_starts_with', function ($name)
{
	if (!has_permission('preference_read_starts_with'))
		return error_pack(err_access_denied);
	
	$where = "name LIKE '" . $name . "%'";
	$records = read('preference', FALSE, $where, 'name', 'ASC');
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {	
		foreach ($records as &$r) {
			unset($r['id']);
		}
	}

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('preference', 'write', function ($name, $value)
{
	if (!has_permission('preference_write'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('preference', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {	
		if (!update('preference', array('value' => $value), $where))
			return error_pack(err_update_failed);
	}
	else {
		$id = create('preference', array(
			'name' => $name,
			'value' => $value
		));
		
		if ($id <= 0)
			return error_pack(err_create_failed);
	}

	return array(
		'status' => 1
	);
});

beat('post', 'list', function ($restart, $discussion_ref)
{
	if (!has_permission('post_list'))
		return error_pack(err_access_denied);
	
	$userid = signedin_uid();
	$limit = get_page_size();
		
	$ses_table_offset = 'post_list_offset_' . $discussion_ref;
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);
	
	$discussion = get_discussion_by_ref($discussion_ref);
	
	if ($discussion === FALSE || !is_array($discussion))
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
	
	$discussion_id = intval($discussion['id']);

	if ($discussion_id <= 0)
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
		
	$where = equ('discussion_id', $discussion_id);
	
	$records = read('post', FALSE, $where, 'id', 'DESC', $limit, $offset);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
	
		$postid = 0;
		
		foreach ($records as &$r) {
			$id = intval($r['id']);
			
			if ($postid < $id)
				$postid = $id;
				 
			unset($r['discussion_id']);
			$quote_discussion_id = intval($r['quote_discussion_id']);
			unset($r['quote_discussion_id']);
			$r['quote_discussion_ref'] = get_discussion_ref_by_id($quote_discussion_id);
			$posted_by = $r['posted_by'];
			unset($r['posted_by']);
			$unava = get_username_and_avatar($posted_by);
			$r['posted_by_has_avatar'] = $unava !== FALSE && strlen($unava['avatar']) > 0 ? 1 : 0;
			$r['posted_by_username'] = $unava !== FALSE ? $unava['name'] : '';
			$r['own_post'] = $posted_by == $userid ? 1 : 0;
		}
		
		if (intval($discussion['autofollow']) > 0 || is_following_discussion($discussion_id))
			update_postread($userid, $discussion_id, $discussion['discussion_type_id'], $postid);
		
		set_session($ses_table_offset, $offset + $limit);
	}
	else
		set_session($ses_table_offset, 0);

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt,
			'limit' => $limit
		)
	);
});

beat('post', 'list_newer', function ($discussion_ref, $last_id)
{
	if (!has_permission('post_list_newer'))
		return error_pack(err_access_denied);
	
	$userid = signedin_uid();
	$limit = get_page_size();
		
	$discussion = get_discussion_by_ref($discussion_ref);
	
	if ($discussion === FALSE || !is_array($discussion))
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
	
	$discussion_id = intval($discussion['id']);

	if ($discussion_id <= 0)
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
		
	$where = equ('discussion_id', $discussion_id);
	$where .= " AND `id` > " . intval($last_id);
	
	$records = read('post', FALSE, $where, 'id', 'ASC', $limit);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
	
		$postid = 0;
		
		foreach ($records as &$r) {
			$id = intval($r['id']);
			
			if ($postid < $id)
				$postid = $id;
				 
			unset($r['discussion_id']);
			$quote_discussion_id = intval($r['quote_discussion_id']);
			unset($r['quote_discussion_id']);
			$r['quote_discussion_ref'] = get_discussion_ref_by_id($quote_discussion_id);
			$posted_by = $r['posted_by'];
			unset($r['posted_by']);
			$unava = get_username_and_avatar($posted_by);
			$r['posted_by_has_avatar'] = $unava !== FALSE && strlen($unava['avatar']) > 0 ? 1 : 0;
			$r['posted_by_username'] = $unava !== FALSE ? $unava['name'] : '';
			$r['own_post'] = $posted_by == $userid ? 1 : 0;
		}

		if (intval($discussion['autofollow']) > 0 || is_following_discussion($discussion_id))
			update_postread($userid, $discussion_id, $discussion['discussion_type_id'], $postid);		
	}

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt,
			'limit' => $limit
		)
	);
});

beat('post', 'read', function ($discussion_ref, $id)
{
	if (!has_permission('post_read'))
		return error_pack(err_access_denied);
	
	$userid = signedin_uid();

	// Note:
	// discussion_ref is required for security since post id is exposed.
	// Must not allow a post to be read by id only.
	
	$discussion = get_discussion_by_ref($discussion_ref);
	
	if ($discussion === FALSE || !is_array($discussion))
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
	
	$discussion_id = intval($discussion['id']);

	if ($discussion_id <= 0)
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
		
	$where = equ('id', $id);
	$records = read('post', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		$postid = 0;
		
		foreach ($records as &$r) {
			if (intval($r['discussion_id']) != $discussion_id)
				return error_pack(err_read_failed);
			
			$id = intval($r['id']);
			
			if ($postid < $id)
				$postid = $id;
				 
			unset($r['discussion_id']);
			$quote_discussion_id = intval($r['quote_discussion_id']);
			unset($r['quote_discussion_id']);
			$r['quote_discussion_ref'] = get_discussion_ref_by_id($quote_discussion_id);
			$posted_by = $r['posted_by'];
			unset($r['posted_by']);
			$unava = get_username_and_avatar($posted_by);
			$r['posted_by_has_avatar'] = $unava !== FALSE && strlen($unava['avatar']) > 0 ? 1 : 0;
			$r['posted_by_username'] = $unava !== FALSE ? $unava['name'] : '';
			$r['own_post'] = $posted_by == $userid ? 1 : 0;
		}

		if (intval($discussion['autofollow']) > 0 || is_following_discussion($discussion_id))
			update_postread($userid, $discussion_id, $discussion['discussion_type_id'], $postid);		
	}

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('post', 'forward', function ($discussion_ref, $id)
{
	if (!has_permission('post_forward'))
		return error_pack(err_access_denied);
	
	// Note:
	// discussion_ref is required for security since post id is exposed.
	// Must not allow a post to be forwarded by id only.
	
	$discussion_id = get_discussion_id_by_ref($discussion_ref);

	if ($discussion_id <= 0)
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
		
	$where = equ('id', $id);
	$records = read('post', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt <= 0 || $records[0]['discussion_id'] != $discussion_id)
		return error_pack(err_read_failed);
		
	set_session("forward_post_id", $id);

	return array(
		'status' => 1
	);
});

beat('post', 'get_forward', function ()
{
	if (!has_permission('post_get_forward'))
		return error_pack(err_access_denied);

	$id = 0;
	$ses = "forward_post_id";
	
	if (isset_session($ses))
		$id = intval(get_session($ses));

	if ($id <= 0)
		return error_pack(err_read_failed);
		
	$where = equ('id', $id);
	$records = read('post', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt <= 0)
		return error_pack(err_read_failed);
	
	$discussion_id = intval($records[0]['discussion_id']);	
	$discussion_ref = get_discussion_ref_by_id($discussion_id);

	return array(
		'status' => 1,
		'data' => array(
			'forward_post_id' => $id,
			'discussion_ref' => $discussion_ref
		)
	);
});

beat('post', 'new', function ($discussion_ref, $body, $quote_discussion_ref = "", $quote_id = 0)
{
	if (!has_permission('post_new'))
		return error_pack(err_access_denied);
	
	$userid = signedin_uid();
	
	if (strlen($discussion_ref) <= 0) { // post to the signed-in user discussion (see userview.html)
		$name = signedin_uname();
		
		if (strlen($name) > 0)
			$discussion_ref = get_discussion_ref('table:user:' . $name);
	}
	
	$discussion = get_discussion_by_ref($discussion_ref);
	
	if ($discussion === FALSE || !is_array($discussion))
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
	
	$discussion_id = intval($discussion['id']);
	
	if ($discussion_id <= 0)
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
	
	$quote_discussion_id = 0;
	
	if ($quote_id > 0) {
		if ($quote_discussion_ref == $discussion_ref || strlen($quote_discussion_ref) <= 0)
			$quote_discussion_id = $discussion_id;
		else {
			$quote_discussion_id = get_discussion_id_by_ref($quote_discussion_ref);
			
			if ($quote_discussion_id <= 0)
				return error_pack(err_record_missing, "quote_discussion_ref: @quote_discussion_ref", array('@quote_discussion_ref' => $quote_discussion_ref));
		}

		$where = equ('id', $quote_id);
		$records = read('post', FALSE, $where);
		$rcnt = record_cnt($records);

		if ($rcnt > 0) {
			if ($records[0]['discussion_id'] != $quote_discussion_id)
				return error_pack(err_read_failed, "quote_id: @quote_id", array('@quote_id' => $quote_id));
		}
	}

	// get attachment
	$attachment_json = '';
	$ses_attachment = "post_attachment_" . $discussion_ref;
	
	if (isset_session($ses_attachment)) {
		$attachment = get_session($ses_attachment);
		$attachment_json = json_encode($attachment);
		unset_session($ses_attachment);
	}
	
	$now = time();
	$id = create('post', array(
		'discussion_id' => $discussion_id,
		'body' => $body,
		'attachment' => $attachment_json,
		'quote_id' => $quote_id,
		'quote_discussion_id' => $quote_discussion_id,
		'posted_by' => $userid,
		'created_utc' => $now,
		'updated_utc' => $now
	));    
	
	if ($id <= 0)
		return error_pack(err_create_failed);
	
	if (intval($discussion['autofollow']) > 0 || is_following_discussion($discussion_id))
		update_postread($userid, $discussion_id, $discussion['discussion_type_id'], $id);		

	return array(
		'status' => 1
	);
});

// Count current user unread post.
// This function must be called in series so that not to block the server.
// The caller must stop calling this function when 'unread_count_limit' is reached since it is no
// point hitting the server for a higher count; It will only reduce the server performance.
// $discussion_typeref can be 'myprofile', 'mygroup', etc; which has an entry in preference that
// starts with 'discussion_typeref_' such as 'discussion_typeref_myprofile', etc; which refers to
// a discussion_type such as 'table:user', 'table:usergroup', etc.
// Or, $discussion_typeref can be set to a specific discussion_type, discussion_name or discussion_ref.
// Or, $discussion_typeref can be set to "all".
// When $discussion_typeref is unknown then the returned 'unread_count_limit' will be set to zero.
// NOTE:
// This function depends on the postread log. An orphan entry in the postread log may cause incorrect
// counting. I.e. whenever a user is removed from a discussion then his entry in the postread log
// must also be removed.
beat('post', 'count_unread', function ($restart, $discussion_typeref = "all")
{
	if (!has_permission('post_count_unread'))
		return error_pack(err_access_denied);
	
	$userid = signedin_uid();
	$unread_count = 0;
	$unread_count_limit = 0;
	$discussion_id = 0;
	$discussion_type_id = 0;

	if ($discussion_typeref != "all") { // a specific type of discussions
		// First, assuming a discussion...
		$ses_discussion_id = "post_count_unread_discussion_id_" . $discussion_typeref;

	 	if (isset_session($ses_discussion_id)) // lookup the cache first
	 		$discussion_id = get_session($ses_discussion_id);
	 	else {
			$discussion_id = get_discussion_id_by_name($discussion_typeref);
			
			if ($discussion_id <= 0)
				$discussion_id = get_discussion_id_by_ref($discussion_typeref);
				
			set_session($ses_discussion_id, $discussion_id);
		}
		
		if ($discussion_id <= 0) {
			$ses_discussion_type_id = "post_count_unread_discussion_type_id_" . $discussion_typeref;
		 
		 	if (isset_session($ses_discussion_type_id)) // lookup the cache first
		 		$discussion_type_id = get_session($ses_discussion_type_id);
		 	else {
		 		// Next, assuming a discussion_type...
				$discussion_type_rec = get_discussion_type($discussion_typeref);
				
				if ($discussion_type_rec !== FALSE && is_array($discussion_type_rec))
					$discussion_type_id = intval($discussion_type_rec['id']);
		 		
		 		if ($discussion_type_id <= 0) {
		 			// Now, assuming a discussion_typeref...
					$discussion_type = get_preference("discussion_typeref_" . $discussion_typeref);
					
					if (strlen($discussion_type) > 0) {
					
						// Check for a specific user discussion
						$pos = strpos($discussion_type, "*username*");
						
						if ($pos !== FALSE) { // It is a specific user discussion
							$uname = signedin_uname();
							$discussion_name = str_replace("*username*", $uname, $discussion_type);
							$discussion_id = get_discussion_id_by_name($discussion_name);
							set_session($ses_discussion_id, $discussion_id);
						}
						else {
							$discussion_type_rec = get_discussion_type($discussion_type);
							
							if ($discussion_type_rec !== FALSE && is_array($discussion_type_rec))
								$discussion_type_id = intval($discussion_type_rec['id']);
						}
					}
				}

				set_session($ses_discussion_type_id, $discussion_type_id);
			}
		}
					
		if ($discussion_id <= 0 && $discussion_type_id <= 0) // silently ignore unknown $discussion_typeref
			return array(
				'status' => 1,
				'data' => array(
					'unread_count' => 0,
					'unread_count_limit' => 0,
					'count' => 0,
					'limit' => 1
				)
			);					
	}

	$limit = get_page_size();
	$ses_table_offset = 'post_count_unread_' . $discussion_typeref;
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);

	if ($discussion_id > 0)
		$where = equ('userid', $userid) . " AND " . equ('discussion_id', $discussion_id);
	elseif ($discussion_type_id > 0)
		$where = equ('userid', $userid) . " AND " . equ('discussion_type_id', $discussion_type_id);
	else
		$where = equ('userid', $userid); // all discussions
		
	$records = read('postread', FALSE, $where, 'updated_utc', 'DESC', $limit, $offset);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		$ses_unread_count_limit = "unread_count_limit";
		
		if (isset_session($ses_unread_count_limit)) // lookup the cache first
			$unread_count_limit = intval(get_session($ses_unread_count_limit));
		else {
			$unread_count_limit = intval(get_preference("unread_count_limit", 100));
			set_session($ses_unread_count_limit, $unread_count_limit);
		}
		
		foreach ($records as &$r) {
			$discussion_id = intval($r['discussion_id']);
			$lastread_postid = intval($r['lastread_postid']);

			$where = equ('discussion_id', $discussion_id) . " AND `id` > " . $lastread_postid;
			$post_records = read(array('table' => 'post', 'where' => $where, 'count' => true));
			$post_rcnt = record_cnt($post_records);

			if ($post_rcnt > 0) {
				$unread_count += intval($post_records[0]['count']);
				
				if ($unread_count >= $unread_count_limit) // limit the counting to maintain performance
					break;
			}
		}
		
		set_session($ses_table_offset, $offset + $limit);
	}
	else
		set_session($ses_table_offset, 0);
	
	return array(
		'status' => 1,
		'data' => array(
			'unread_count' => $unread_count,
			'unread_count_limit' => $unread_count_limit,
			'count' => $rcnt,
			'limit' => $limit
		)
	);
});

beat('post', 'react', function ($discussion_ref, $id, $type, $style = 'toggle')
{
	if (!has_permission('post_react'))
		return error_pack(err_access_denied);
	
	$userid = signedin_uid();

	// a post must belong to a discussion
	$discussion_id = get_discussion_id_by_ref($discussion_ref);
	
	if ($discussion_id <= 0)
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
	
	// verify that the post with id exists, and it is a part of the discussion.
	$where = equ('id', $id);
	$records = read('post', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		if ($records[0]['discussion_id'] != $discussion_id)
			return error_pack(err_read_failed);
			
		if ($records[0]['posted_by'] == $userid) // policy: cannot react to one's own post
			return array(
				'status' => 1,
				'data' => array(
					'own' => 1,
					'changes' => 0
				)
			);		
	}
	else
		return error_pack(err_read_failed);

	$changes = 0;
	$where = equ('postid', $id) . " AND " . equ('userid', $userid);
	$records = read('postreact', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		$nt = 0;		
		$t = intval($records[0]['type']);
		$bitvals = array(1, 2, 4, 8, 16, 32, 64, 128);
		
		foreach ($bitvals as $bv) {
			if (($type & $bv) > 0) { // for every selected reacts,
				if ($style == 'toggle') {
					if (($t & $bv) == 0) // toggle
						$nt += $bv;
					++$changes;
				}
				else if ($style == 'trigger') {
					if (($t & $bv) == 0)
						++$changes;
					$nt += $bv;
				}
			}
			elseif (($t & $bv) > 0) // keep unselected reacts
				$nt += $bv;				
		}
		
		if (!update('postreact', array('type' => $nt), $where))
			return error_pack(err_update_failed);
	}
	else {
		$id = create('postreact', array(
			'postid' => $id,
			'userid' => $userid,
			'type' => $type
		));    
		
		if ($id <= 0)
			return error_pack(err_create_failed);
	}
	
	return array(
		'status' => 1,
		'data' => array(
			'own' => 0,
			'changes' => $changes
		)
	);
});

beat('post', 'get_react', function ($discussion_ref, $id)
{
	if (!has_permission('post_get_react'))
		return error_pack(err_access_denied);
	
	$userid = signedin_uid();

	// a post must belong to a discussion
	$discussion_id = get_discussion_id_by_ref($discussion_ref);
	
	if ($discussion_id <= 0)
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
	
	// verify that the post with id exists, and it is a part of the discussion.
	$where = equ('id', $id);
	$records = read('post', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		if ($records[0]['discussion_id'] != $discussion_id)
			return error_pack(err_read_failed);
	}
	else
		return error_pack(err_read_failed);

	$where = equ('postid', $id) . " AND " . equ('userid', $userid);
	$records = read('postreact', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		foreach ($records as &$r) {
			unset($r['id']);
			unset($r['postid']);
			unset($r['userid']);
		}
	}
	
	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('post', 'count_reacts', function ($discussion_ref, $id, $type)
{
	if (!has_permission('post_count_reacts'))
		return error_pack(err_access_denied);
	
	$type_mask = 0;
	$bitvals = array(1, 2, 4, 8, 16, 32, 64, 128);
	
	foreach ($bitvals as $bv) {
		if (($type & $bv) > 0) {
			$type_mask = $bv;
			break;
		}
	}
	
	if ($type_mask <= 0)
		return error_pack(err_record_missing, "type: @type", array('@type' => $type));
	
	// a post must belong to a discussion
	$discussion_id = get_discussion_id_by_ref($discussion_ref);
	
	if ($discussion_id <= 0)
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
	
	// verify that the post with id exists, and it is a part of the discussion.
	$where = equ('id', $id);
	$records = read('post', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		if ($records[0]['discussion_id'] != $discussion_id)
			return error_pack(err_read_failed);
	}
	else
		return error_pack(err_read_failed);

	$count = 0;
	$where = equ('postid', $id) . " AND `type` & $type_mask = $type_mask";
	$records = read(array('table' => 'postreact', 'where' => $where, 'count' => true));
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		$count = intval($records[0]['count']);
	}
	
	return array(
		'status' => 1,
		'data' => array(
			'count' => $count
		)
	);
});

beat('post', 'list_reacts', function ($restart, $discussion_ref, $id, $type)
{
	if (!has_permission('post_list_reacts'))
		return error_pack(err_access_denied);
	
	$userid = signedin_uid();

	$type_mask = 0;
	$bitvals = array(1, 2, 4, 8, 16, 32, 64, 128);
	
	foreach ($bitvals as $bv) {
		if (($type & $bv) > 0) {
			$type_mask = $bv;
			break;
		}
	}
	
	if ($type_mask <= 0)
		return error_pack(err_record_missing, "type: @type", array('@type' => $type));
	
	$limit = get_page_size();
	$ses_table_offset = 'post_list_reacts_' . $discussion_ref . '_' . $id . '_' . $type;
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);
	
	$where = equ('ref', $discussion_ref, 'string');

	// a post must belong to a discussion
	$discussion_id = get_discussion_id_by_ref($discussion_ref);
	$where = equ('ref', $discussion_ref, 'string');
	
	if ($discussion_id <= 0)
		return error_pack(err_record_missing, "discussion_ref: @discussion_ref", array('@discussion_ref' => $discussion_ref));
	
	// verify that the post with id exists, and it is a part of the discussion.
	$where = equ('id', $id);
	$records = read('post', FALSE, $where);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		if ($records[0]['discussion_id'] != $discussion_id)
			return error_pack(err_read_failed);

		if ($records[0]['posted_by'] != $userid) {	// not the signed-in user post
			return array(							// so return empty record
				'status' => 1,
				'data' => array(
					'records' => array(),
					'count' => 0,
					'limit' => $limit
				)
			);
		}
	}
	else
		return error_pack(err_read_failed);

	$where = equ('postid', $id) . " AND `type` & $type_mask = $type_mask";
	$records = read('postreact', FALSE, $where, 'id', 'ASC', $limit, $offset);
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {
		foreach ($records as &$r) {
			unset($r['id']);
			unset($r['postid']);
			$uid = $r['userid'];
			unset($r['userid']);
			$r['username'] = get_username_by_id($uid);
		}
		
		set_session($ses_table_offset, $offset + $limit);
	}
	else
		set_session($ses_table_offset, 0);
	
	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt,
			'limit' => $limit
		)
	);
});

beat('post', 'attachment', function ($discussion_ref)
{
	if (!has_permission('post_attachment'))
		return error_pack(err_access_denied);
	
	$file_count = count($_FILES['files']['name']);

	$uploaded_files = array();

	for ($index = 0; $index < $file_count; $index++) {

		if (isset($_FILES['files']['name'][$index]) && $_FILES['files']['name'][$index] != '') {
			$filename = $_FILES['files']['name'][$index];

			$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

			$fn = new_frepo();
			
			if (strlen($fn) > 0) {

				$rpath = get_frepo_path($fn);

				if ($rpath !== false && strlen($rpath) > 0) {

					if (move_uploaded_file($_FILES['files']['tmp_name'][$index], $rpath . "/file.dt")){
						$md = array("fname" => $filename, "ext" => $ext, "ref" => $discussion_ref, "ts" => time());
						if (write_frepo_metadata($fn, $md)) {
							unset($md["ext"]);
							unset($md["ref"]);
							unset($md["ts"]);
							$md["repo"] = $fn;
							$uploaded_files[] = $md;
						}
					}
				}
			}
		}
	}
	
	// Keep the attachment info in session so that the new post can catch it
	set_session("post_attachment_" . $discussion_ref, $uploaded_files);

	return array(
		'status' => 1,
		'data' => array(
			'count' => count($uploaded_files),
			'files' => $uploaded_files
		)
	);
});

beat('post', 'get_attachment', function ($discussion_ref, $repo)
{
	if (!has_permission('post_get_attachment'))
		return;
		
	$md = read_frepo_metadata($repo);
	
	if ($md === false || !isset($md['ref']) || $md['ref'] != $discussion_ref)
		return;
	
	if (!isset($md['fname']))
		return;
	
	$fname = $md['fname'];
	
	if (strlen($fname) <= 0)
		return;
		
	$path = get_frepo_path($repo);
	
	if ($path === false || strlen($path) <= 0)
		return;
		
	$fd = $path . "/file.dt";
	
	if (!file_exists($fd))
		return;
		
	ob_get_clean();
	header('Content-Description: File Transfer');
	
	$attachment = false;
	$video_ext = array("mp4", "webm", "ogg");
	$image_ext = array("png", "jpeg", "jpg", "gif");
	$ext = $md['ext'];

	$fname_lwr = strtolower($fname);
	
	if ($fname_lwr == "readme" || $fname_lwr == "read.me")
		$ext = "txt";
	
	if (in_array($ext, $video_ext)) {
		$stream = new VideoStream($fd, $ext);
		$stream->start(); // this will exit
	}
	else if (in_array($ext, $image_ext))
		header('Content-Type: image/' . $ext);
	else if ($ext == 'html')
		header('Content-Type: text/html');
	else if ($ext == 'css')
		header('Content-Type: text/css');
	else if ($ext == 'js' || $ext == 'javascript')
		header('Content-Type: text/javascript');
	else if ($ext == 'txt' || $ext == 'text')
		header('Content-Type: text/plain');
	else if ($ext == 'pdf')
		header('Content-Type: application/pdf');
	else {
		$attachment = true;

		if ($ext == 'json')
			header('Content-Type: application/json');
		else if ($ext == 'geojson')
			header('Content-Type: application/geo+json');
		else
			header('Content-Type: application/octet-stream');
	}
	
	// Allow 30 days cache
    header("Cache-Control: max-age=2592000, public");
    header("Expires: " . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
	
	if ($attachment)
		header('Content-Disposition: attachment; filename="' . $fname . '"');
	else
		header('Content-Disposition: inline');
	
	header('Content-Length: ' . filesize($fd));
	header('Pragma: public');

	flush();

	readfile($fd);

	exit;	
});




