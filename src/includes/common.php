<?php
// common.php
// Harubi-Front Common
// By Abdullah Daud, chelahmy@gmail.com
// 12 November 2022

require_once 'harubi/harubi.php';

// Application name will be loaded from settings when it is empty.
// It should be unique for every application that uses this framework.
// Otherwise, user session may criss-cross among applications.
$appname = ''; 

session_start();

if (isset($harubi_settings_path) && strlen($harubi_settings_path) > 0)
	harubi($harubi_settings_path);
else
	harubi();
	
define("err_unknown", 0);
define("err_not_signed_in", 1);
define("err_try_signup_later", 2);
define("err_signup_failed", 3);
define("err_user_exists", 4);
define("err_user_update_failed", 5);
define("err_user_restricted", 6);
define("err_signin_failed", 7);
define("err_user_read_failed", 8);
define("err_name_exists", 9);
define("err_access_denied", 10);
define("err_create_failed", 11);
define("err_read_failed", 12);
define("err_update_failed", 13);
define("err_delete_failed", 14);
define("err_record_already_exists", 15);
define("err_parent_record_missing", 16);
define("err_remove_failed", 17);
define("err_cannot_change_password", 18);
define("err_cannot_delete", 19);
define("err_missing", 20);
define("err_cannot_create_ref", 21);

$error_messages = array(
	err_unknown => "Unknown error.",
	err_not_signed_in => "User had not signed in.",
	err_try_signup_later => "Could not sign up new user now. Please try again later.",
	err_signup_failed => "Could not sign up new user.",
	err_user_exists => "User of the given name already exists.",
	err_user_update_failed => "Could not update user record.",
	err_user_restricted => "The user has been restricted.",
	err_signin_failed => "Could not sign in user.", // for security reason: should not give more details on why the sign in failed
	err_user_read_failed => "Could not read user record.",
	err_name_exists => "The name already exists.",
	err_access_denied => "Access denied.",
	err_create_failed => "Record creation failed.",
	err_read_failed => "Record reading failed.",
	err_update_failed => "Record updating failed.",
	err_delete_failed => "Record deletion failed.",
	err_record_already_exists => "Record already exists.",
	err_parent_record_missing => "Parent record missing.",
	err_remove_failed => "Remove failed.",
	err_cannot_change_password => "Cannot change password.",
	err_cannot_delete => "Cannot delete record.",
	err_missing => "Record missing.",
	err_cannot_create_ref => "Cannot create ref.",
);

function error_pack($code, $ext = "")
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
				$error_messages[$code] : "Unknown error code $code.",
			'error_ext' => $ext,
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
	$where = equ('name', $name, 'string');
	$records = read('preference', FALSE, $where);
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		return $records[0]['value'];
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

	return $id;	
}

function get_page_size() {
	// Try to get it from preference
	$ps = intval(get_preference('page_size'));
	
	// Otherwise, get it from settings
	if ($ps <= 0) {
		if (isset($harubi_settings)) {
			if (isset($harubi_settings['page_size'])) {
				$psz = intval($harubi_settings['page_size']);
				if ($psz > 0)
					return $psz;
			}
		}
	}
	
	return 25; // default
}

$page_size = get_page_size();

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

function is_signedin($uid = 0)
{
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
			set_session('uid', 0);
			unset_session('uid');
			set_session('admin_uid', 0);
			unset_session('admin_uid');
			set_session('uname', '');
			unset_session('uname');
			set_session('uroleid', 0);
			unset_session('uroleid');
			unset_session('last_accessed');
			
			return false; 
		}
	}
		
	set_session('last_accessed', time());
	
	return true;
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

	return false;
}

beat('system', 'heartbeat', function ()
{
	set_session('last_accessed', time());

	return array(
		'status' => 1
	);
});

beat('system', 'signedin', function ()
{
	set_session('last_accessed', time());

	return array(
		'status' => 1,
		'data' => array(
			'is_signedin' => is_signedin(),
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

beat('user', 'signup', function ($name, $password, $email)
{
	// Delay next user sign up for 5 minutes to deter spamming
	if (isset_session('last_reg') && (time() - get_session('last_reg')) < 300)
		return error_pack(err_try_signup_later);
	
	$where = equ('name', $name, 'string');
	$records = read('user', FALSE, $where);
	
	if ($records === FALSE || !is_array($records))
		return error_pack(err_read_failed);
	
	$rcnt = record_cnt($records);

	if ($rcnt <= 0) // no $name user exist, so create a new one
	{
		$now = time();
		$hash = password_hash($password, PASSWORD_BCRYPT);
		$id = create('user', array(
			'name' => $name,
			'password' => $hash,
			'email' => $email,
			'roleid' => get_new_user_roleid(),
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
				
			return array(
				'status' => 1
			);
		}
				
		return error_pack(err_signup_failed);
	}

	return error_pack(err_user_exists);
});

beat('user', 'signin', function ($name, $password)
{    
	$now = time();
	$where = equ('name', $name, 'string');
	$records = read('user', FALSE, $where);
	
	if ($records === FALSE || !is_array($records))
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
				set_session('last_accessed', time());
				
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
	set_session('uid', 0);
	unset_session('uid');
	set_session('uname', '');
	unset_session('uname');
	set_session('admin_uid', 0);
	unset_session('admin_uid');
	set_session('uroleid', 0);
	unset_session('uroleid');
	unset_session('last_accessed');
			
	return array(
		'status' => 1,
	);
});



