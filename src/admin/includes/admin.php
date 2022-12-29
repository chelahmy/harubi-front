<?php
// admin.php
// Harubi-Front Admininistration
// By Abdullah Daud, chelahmy@gmail.com
// 13 November 2022

$harubi_settings_path = "../../includes/settings.inc";
require_once "../../includes/common.php";

beat('user', 'list', function ($restart, $search = '', $premium = 0, $order_by = 'name', $sort = 'ASC')
{
	global $page_size;
	
	if (!has_permission('user_list'))
		return error_pack(err_access_denied);
	
	if (isset($page_size) && $page_size > 0)
		$limit = $page_size;
	else
		$limit = 25;
		
	$ses_table_offset = 'user_list_offset';
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);
	
	if (strlen($search) > 0) {
		$search = clean($search, 'string');
		$where = "MATCH(name, email) AGAINST('" . $search . "' IN BOOLEAN MODE)";
	}
	else
		$where = FALSE;
		
	if ($premium == 1) {
		$now = time();
		$valid_thru = '(valid_thru >= ' . $now . ' OR id = 1'; 
		$prids = get_premium_role_ids();
		
		if (count($prids) > 0) {
			$prids_list = "('" . implode("','", $prids) . "')";
			$valid_thru .= ' OR roleid IN ' . $prids_list;
		}
		
		$valid_thru .= ')';
		
		if ($where === FALSE)
			$where = $valid_thru;
		else
			$where = ' AND ' . $valid_thru; 
	}
	
	$order_by = clean($order_by, 'string');
	$sort = clean($sort, 'string');
	
	$records = read('user', FALSE, $where, $order_by, $sort, $limit, $offset);
	$rcnt = count($records);

	if ($rcnt > 0) {
	
		foreach ($records as &$r) {
			unset($r['id']);
			unset($r['password']);
			$roleid = intval($r['roleid']);
			unset($r['roleid']);
			$r['rolename'] = get_rolename_by_id($roleid);
		}
		
		set_session($ses_table_offset, $offset + $limit);
	}
	else
		set_session($ses_table_offset, 0);

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('user', 'create', function ($name, $password, $email, $rolename)
{
	if (!has_permission('user_create'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('user', FALSE, $where);
	
	if (count($records) > 0)
		return error_pack(err_record_already_exists);
	else {
		$now = time();
		$hash = password_hash($password, PASSWORD_BCRYPT);
		$id = create('user', array(
			'name' => $name,
			'password' => $hash,
			'email' => $email,
			'roleid' => get_roleid_by_name($rolename),
			'language' => get_language(),
			'valid_thru' => 0,
			'signins' => 0,
			'last_signedin_utc' => 0,
			'signin_note' => 0,
			'created_utc' => $now,
			'updated_utc' => $now
		));    
		
		if ($id <= 0)
			return error_pack(err_create_failed);
	}
	
	return array(
		'status' => 1
	);
});

beat('user', 'read', function ($name)
{
	if (!has_permission('user_read'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('user', FALSE, $where);
	$rcnt = count($records);

	if ($rcnt > 0) {	
		foreach ($records as &$r) {
			unset($r['id']);
			unset($r['password']);
			$roleid = intval($r['roleid']);
			unset($r['roleid']);
			$r['rolename'] = get_rolename_by_id($roleid);
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

beat('user', 'update', function ($name, $password, $email, $rolename)
{
	if (!has_permission('user_update'))
		return error_pack(err_access_denied);
	
	$now = time();
	$where = equ('name', $name, 'string');

	if (strlen($password) > 0) {
		$uid = get_userid_by_name($name);
		if ($uid == 1) { // only superadmin can change his own password
			if (signedin_uid() != 1)
				return error_pack(err_cannot_change_password);
		}
		$hash = password_hash($password, PASSWORD_BCRYPT);
		if (!update('user', array(
			'password' => $hash,
			'email' => $email,
			'roleid' => get_roleid_by_name($rolename),
			'updated_utc' => $now
			), $where))
			return error_pack(err_update_failed);
	}
	else {
		if (!update('user', array(
			'email' => $email,
			'roleid' => get_roleid_by_name($rolename),
			'updated_utc' => $now
			), $where))
			return error_pack(err_update_failed);
	}
	
	return array(
		'status' => 1
	);
});

beat('user', 'delete', function ($name)
{
	if (!has_permission('user_delete'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');

	$rid = 0;
	$records = read('user', FALSE, $where);
	
	if (count($records) > 0)
		$rid = intval($records[0]['id']);
	else // record does not exist or already deleted
		return array(
			'status' => 1
		);

	if ($rid == 1) // Cannot delete superadmin
		return error_pack(err_cannot_delete);
	
	if (!delete('user', $where))
		return error_pack(err_delete_failed);

	// Try to read back the record
	$records = read('user', FALSE, $where);
	
	if (count($records) > 0)
		return error_pack(err_delete_failed);
		
	if ($rid > 0) {
		// TODO: delete all records that refer to this user
	}
	
	return array(
		'status' => 1
	);
});

beat('usergroup', 'list', function ($restart, $search = '', $order_by = 'name', $sort = 'ASC')
{
	global $page_size;
	
	if (!has_permission('usergroup_list'))
		return error_pack(err_access_denied);
	
	if (isset($page_size) && $page_size > 0)
		$limit = $page_size;
	else
		$limit = 25;
		
	$ses_table_offset = 'usergroup_list_offset';
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);
	
	if (strlen($search) > 0) {
		$search = clean($search, 'string');
		$where = "MATCH(name) AGAINST('" . $search . "')";
	}
	else
		$where = FALSE;
		
	$order_by = clean($order_by, 'string');
	$sort = clean($sort, 'string');
	
	$records = read('usergroup', FALSE, $where, $order_by, $sort, $limit, $offset);
	$rcnt = $records === FALSE && !is_array($records) ? 0 : count($records);

	if ($rcnt > 0) {
	
		foreach ($records as &$r) {
			unset($r['id']);
			$owneruserid = intval($r['owneruserid']);
			unset($r['owneruserid']);
			$r['ownername'] = get_username_by_id($owneruserid);
			$created_by = intval($r['created_by']);
			unset($r['created_by']);
			$r['created_by_username'] = get_username_by_id($created_by);
		}
		
		set_session($ses_table_offset, $offset + $limit);
	}
	else
		set_session($ses_table_offset, 0);

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('usergroup', 'create', function ($ownername, $name)
{
	if (!has_permission('usergroup_create'))
		return error_pack(err_access_denied);
	
	$owneruserid = get_userid_by_name($ownername);

	if ($owneruserid <= 0)
		return error_pack(err_record_missing, "Owner: @ownername", array('@ownername' => $ownername));
	
	$ref = new_table_row_ref('usergroup');
	
	if (strlen($ref) <= 0)
		return error_pack(err_cannot_create_ref);
	
	$now = time();
	$id = create('usergroup', array(
		'name' => $name,
		'ref' => $ref,
		'owneruserid' => $owneruserid,
		'created_by' => signedin_uid(),
		'created_utc' => $now,
		'updated_utc' => $now
	));    
	
	if ($id <= 0)
		return error_pack(err_create_failed);
	
	return array(
		'status' => 1,
		'data' => array(
			'ref' => $ref
		)
	);
});

beat('usergroup', 'read', function ($ref)
{
	if (!has_permission('usergroup_read'))
		return error_pack(err_access_denied);
	
	$where = equ('ref', $ref, 'string');
	$records = read('usergroup', FALSE, $where);
	$rcnt = count($records);

	if ($rcnt > 0) {	

		$post_parent_ref = get_post_parent_ref('table_usergroup_' . $ref);
		
		foreach ($records as &$r) {
			unset($r['id']);
			$owneruserid = intval($r['owneruserid']);
			unset($r['owneruserid']);
			$r['ownername'] = get_username_by_id($owneruserid);
			$created_by = intval($r['created_by']);
			unset($r['created_by']);
			$r['created_by_username'] = get_username_by_id($created_by);
			$r['post_parent_ref'] = $post_parent_ref;
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

beat('usergroup', 'update', function ($ref, $name)
{
	if (!has_permission('usergroup_update'))
		return error_pack(err_access_denied);
	
	$now = time();
	$where = equ('ref', $ref, 'string');

	if (!update('usergroup', array(
		'name' => $name,
		'updated_utc' => $now
		), $where))
		return error_pack(err_update_failed);
	
	return array(
		'status' => 1
	);
});

beat('usergroup', 'delete', function ($ref)
{
	if (!has_permission('usergroup_delete'))
		return error_pack(err_access_denied);
	
	$where = equ('ref', $ref, 'string');

	$rid = 0;
	$records = read('usergroup', FALSE, $where);
	
	if (record_cnt($records) > 0)
		$rid = intval($records[0]['id']);
	else // record does not exist or already deleted
		return array(
			'status' => 1
		);

	if (!delete('usergroup', $where))
		return error_pack(err_delete_failed);

	// Try to read back the record
	$records = read('usergroup', FALSE, $where);
	
	if (record_cnt($records) > 0)
		return error_pack(err_delete_failed);
		
	if ($rid > 0) {
		// Delete all records that refer to this usergroup
		$where = equ('usergroupid', $rid);

		if (!delete('member', $where))
			return error_pack(err_delete_failed, "Members");
	}
	
	return array(
		'status' => 1
	);
});

beat('member', 'list', function ($restart, $groupref, $search = '', $order_by = 'created_utc', $sort = 'ASC')
{
	global $page_size;
	
	if (!has_permission('member_list'))
		return error_pack(err_access_denied);
	
	if (isset($page_size) && $page_size > 0)
		$limit = $page_size;
	else
		$limit = 25;
		
	$ses_table_offset = 'member_list_offset';
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);
	
	$where = equ('ref', $groupref, 'string');

	$usergroupid = 0;
	$records = read('usergroup', FALSE, $where);
	
	if (count($records) > 0)
		$usergroupid = intval($records[0]['id']);
	else // record does not exist
		return error_pack(err_record_missing, "groupref: @groupref", array('@groupref' => $groupref));
		
	$where = equ('usergroupid', $usergroupid);
	
	if (strlen($search) > 0) {
		$userid = get_userid_by_name($search);
		$where .= " AND " . equ('userid', $userid);
	}
		
	$order_by = clean($order_by, 'string');
	$sort = clean($sort, 'string');
	
	$records = read('member', FALSE, $where, $order_by, $sort, $limit, $offset);
	$rcnt = count($records);

	if ($rcnt > 0) {
	
		foreach ($records as &$r) {
			unset($r['id']);
			$userid = intval($r['userid']);
			unset($r['userid']);
			$r['username'] = get_username_by_id($userid);
			$roleid = intval($r['roleid']);
			unset($r['usergroupid']);
			unset($r['roleid']);
			$r['rolename'] = get_rolename_by_id($roleid);
			$added_by = intval($r['added_by']);
			unset($r['added_by']);
			$r['added_by_username'] = get_username_by_id($added_by);
		}
		
		set_session($ses_table_offset, $offset + $limit);
	}
	else
		set_session($ses_table_offset, 0);

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('member', 'read', function ($groupref, $username)
{
	if (!has_permission('member_read'))
		return error_pack(err_access_denied);
	
	$userid = get_userid_by_name($username);

	if ($userid <= 0)
		return error_pack(err_record_missing, "member: @username", array('@username' => $username));
	
	$usergroupid = 0;
	$where = equ('ref', $groupref, 'string');
	$records = read('usergroup', FALSE, $where);

	if (read_failed($records))
		return error_pack(err_read_failed, "groupref: @groupref", array('@groupref' => $groupref));
	
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0)
		$usergroupid = intval($records[0]['id']);
		
	if ($usergroupid <= 0)
		return error_pack(err_record_missing, "groupref: @groupref", array('@groupref' => $groupref));
	
	$where = equ('userid', $userid) . ' AND ' . equ('usergroupid', $usergroupid);
	$records = read('member', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed, "groupref: @groupref, member: @username", array('@groupref' => $groupref, '@username' => $username));
	
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0) {
		foreach ($records as &$r) {
			unset($r['id']);
			unset($r['userid']);
			unset($r['usergroupid']);
			$roleid = $r['roleid'];
			unset($r['roleid']);
			$r['rolename'] = get_rolename_by_id($roleid);
			$added_by = intval($r['added_by']);
			unset($r['added_by']);
			$r['added_by_username'] = get_username_by_id($added_by);
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

beat('member', 'add', function ($groupref, $username, $rolename = 'member')
{
	if (!has_permission('member_add'))
		return error_pack(err_access_denied);
	
	$userid = get_userid_by_name($username);

	if ($userid <= 0)
		return error_pack(err_record_missing, "member: @username", array('@username' => $username));

	$usergroupid = 0;
	$where = equ('ref', $groupref, 'string');
	$records = read('usergroup', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed, "groupref: @groupref", array('@groupref' => $groupref));
	
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0)
		$usergroupid = intval($records[0]['id']);
	
	if ($usergroupid <= 0)
		return error_pack(err_record_missing, "groupref: @groupref", array('@groupref' => $groupref));
	
	$where = equ('userid', $userid) . ' AND ' . equ('usergroupid', $usergroupid);
	$records = read('member', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed, "groupref: @groupref, member: @username", array('@groupref' => $groupref, '@username' => $username));
	
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0)
		return error_pack(err_record_already_exists);
		
	$now = time();
	$id = create('member', array(
		'userid' => $userid,
		'usergroupid' => $usergroupid,
		'roleid' => get_roleid_by_name($rolename),
		'unread' => 0,
		'added_by' => signedin_uid(),
		'created_utc' => $now,
		'updated_utc' => $now
	));    
	
	if ($id <= 0)
		return error_pack(err_create_failed);
	
	return array(
		'status' => 1
	);
});

beat('member', 'update', function ($groupref, $username, $rolename)
{
	if (!has_permission('member_update'))
		return error_pack(err_access_denied);
	
	$userid = get_userid_by_name($username);

	if ($userid <= 0)
		return error_pack(err_record_missing, "member: @username", array('@username' => $username));
	
	$usergroupid = 0;
	$where = equ('ref', $groupref, 'string');
	$records = read('usergroup', FALSE, $where);

	if (read_failed($records))
		return error_pack(err_read_failed, "groupref: @groupref", array('@groupref' => $groupref));
	
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0)
		$usergroupid = intval($records[0]['id']);
		
	if ($usergroupid <= 0)
		return error_pack(err_record_missing, "groupref: @groupref", array('@groupref' => $groupref));
	
	$now = time();
	$where = equ('userid', $userid) . ' AND ' . equ('usergroupid', $usergroupid);

	if (!update('member', array(
		'roleid' => get_roleid_by_name($rolename),
		'updated_utc' => $now
		), $where))
		return error_pack(err_update_failed);
	
	return array(
		'status' => 1
	);
});

beat('member', 'remove', function ($groupref, $username)
{
	if (!has_permission('member_remove'))
		return error_pack(err_access_denied);

	$userid = get_userid_by_name($username);

	if ($userid <= 0)
		return error_pack(err_record_missing, "member: @username", array('@username' => $username));

	$usergroupid = 0;
	$where = equ('ref', $groupref, 'string');
	$records = read('usergroup', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed, "groupref: @groupref", array('@groupref' => $groupref));
	
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0)
		$usergroupid = intval($records[0]['id']);
	
	if ($usergroupid <= 0)
		return error_pack(err_record_missing, "groupref: @groupref", array('@groupref' => $groupref));
	
	$where = equ('userid', $userid) . ' AND ' . equ('usergroupid', $usergroupid);
	$records = read('member', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed, "groupref: @groupref, member: @username", array('@groupref' => $groupref, '@username' => $username));
	
	$rcnt = record_cnt($records);
	
	if ($rcnt <= 0) // record does not exist
		return array(
			'status' => 1
		);
		
	$rid = $records[0]['id'];
	
	if (!delete('member', $where))
		return error_pack(err_delete_failed);

	// Try to read back the record
	$records = read('member', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed, "groupref: @groupref, member: @username", array('@groupref' => $groupref, '@username' => $username));
	
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0)
		return error_pack(err_delete_failed);
		
	if ($rid > 0) {
		// TODO: delete all records that refer to this member
	}
	
	return array(
		'status' => 1
	);
});

beat('role', 'list', function ($restart)
{
	global $page_size;
	
	if (!has_permission('role_list'))
		return error_pack(err_access_denied);
	
	if (isset($page_size) && $page_size > 0)
		$limit = $page_size;
	else
		$limit = 25;
		
	$ses_table_offset = 'role_list_offset';
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);
	
	$records = read('role', FALSE, FALSE, 'name', FALSE, $limit, $offset);
	$rcnt = count($records);

	if ($rcnt > 0) {
	
		foreach ($records as &$r) {
			unset($r['id']);
		}
		
		set_session($ses_table_offset, $offset + $limit);
	}
	else
		set_session($ses_table_offset, 0);

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('role', 'listall', function ()
{
	if (!has_permission('role_listall'))
		return error_pack(err_access_denied);
	
	$records = read('role', FALSE, FALSE, 'name');
	$rcnt = count($records);

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

beat('role', 'create', function ($name, $premium)
{
	if (!has_permission('role_create'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('role', FALSE, $where);
	
	if (count($records) > 0)
		return error_pack(err_record_already_exists);
	else {
		$id = create('role', array(
			'name' => $name,
			'premium' => $premium
		));
		
		if ($id <= 0)
			return error_pack(err_create_failed);
	}
	
	return array(
		'status' => 1
	);
});

beat('role', 'read', function ($name)
{
	if (!has_permission('role_read'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('role', FALSE, $where);
	$rcnt = count($records);

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

beat('role', 'update', function ($name, $newname, $premium)
{
	if (!has_permission('role_update'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');

	if (!update('role', array('name' => $newname, 'premium' => $premium), $where))
		return error_pack(err_update_failed);

	return array(
		'status' => 1
	);
});

beat('role', 'delete', function ($name)
{
	if (!has_permission('role_delete'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');

	$rid = 0;
	$records = read('role', FALSE, $where);
	
	if (count($records) > 0)
		$rid = $records[0]['id'];
	else // record does not exist or already deleted
		return array(
			'status' => 1
		);

	if (!delete('role', $where))
		return error_pack(err_delete_failed);

	// Try to read back the record
	$records = read('role', FALSE, $where);
	
	if (count($records) > 0)
		return error_pack(err_delete_failed);
		
	if ($rid > 0) {
		// TODO: delete all records that refer to this role
	}
	
	return array(
		'status' => 1
	);
});

beat('permission', 'list', function ($restart, $search = '')
{
	global $page_size;
	
	if (!has_permission('permission_list'))
		return error_pack(err_access_denied);
	
	if (isset($page_size) && $page_size > 0)
		$limit = $page_size;
	else
		$limit = 25;
		
	$ses_table_offset = 'permission_list_offset';
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);

	if (strlen($search) > 0) {
		$search = clean($search, 'string');
		$where = "name LIKE '%" . $search . "%'";
	}
	else
		$where = FALSE;
	
	$records = read('permission', FALSE, $where, 'name', FALSE, $limit, $offset);
	$rcnt = count($records);

	if ($rcnt > 0) {
	
		foreach ($records as &$r) {
			$roles = array();
			$where = equ('permissionid', $r['id']);
			$pr_records = read('permrole', FALSE, $where);
						
			if (count($pr_records) > 0) {
				foreach ($pr_records as $pr_r) {
					$where = equ('id', $pr_r['roleid']);
					$r_records = read('role', FALSE, $where);
				
					if (count($r_records) > 0) {
						foreach ($r_records as $r_r) {
							$roles[] = $r_r['name'];
						}
					}
				}
			}

			unset($r['id']);
			$r['roles'] = $roles;		
		}
		
		set_session($ses_table_offset, $offset + $limit);
	}
	else
		set_session($ses_table_offset, 0);

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('permission', 'create', function ($name)
{
	if (!has_permission('permission_create'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('permission', FALSE, $where);
	
	if (count($records) > 0)
		return error_pack(err_record_already_exists);
	else {
		$id = create('permission', array(
			'name' => $name
		));
		
		if ($id <= 0)
			return error_pack(err_create_failed);
	}
	
	return array(
		'status' => 1
	);
});

beat('permission', 'read', function ($name)
{
	if (!has_permission('permission_read'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('permission', FALSE, $where);
	$rcnt = count($records);

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

beat('permission', 'update', function ($name, $newname)
{
	if (!has_permission('permission_update'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');

	if (!update('permission', array('name' => $newname), $where))
		return error_pack(err_update_failed);

	return array(
		'status' => 1
	);
});

beat('permission', 'delete', function ($name)
{
	if (!has_permission('permission_delete'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');

	$rid = 0;
	$records = read('permission', FALSE, $where);
	
	if (count($records) > 0)
		$rid = $records[0]['id'];
	else // record does not exist or already deleted
		return array(
			'status' => 1
		);

	if (!delete('permission', $where))
		return error_pack(err_delete_failed);

	// Try to read back the record
	$records = read('permission', FALSE, $where);
	
	if (count($records) > 0)
		return error_pack(err_delete_failed);
		
	if ($rid > 0) {
		// delete all records that refer to this permission
		$where = equ('permissionid', $rid);
		$records = read('permrole', FALSE, $where);
		
		if (count($records) > 0) {
			if (!delete('permrole', $where))
				return error_pack(err_delete_failed, "Permission roles");	
		}
	}
	
	return array(
		'status' => 1
	);
});

beat('permrole', 'listall', function ($permname)
{
	if (!has_permission('permrole_listall'))
		return error_pack(err_access_denied);
	
	$permid = 0;
	$where = equ('name', $permname, 'string');
	$records = read('permission', FALSE, $where);
	
	if (count($records) > 0)
		$permid = $records[0]['id'];
	else // record does not exist or already deleted
		return array(
			'status' => 1,
			'data' => array(
				'records' => array(),
				'count' => 0
			)
		);
	
	$role_records = array();
	$where = equ('permissionid', $permid);
	$records = read('permrole', FALSE, $where);
	$rcnt = count($records);

	if ($rcnt > 0) {
	
		foreach ($records as $r) {

			$where = equ('id', intval($r['roleid']));
			$records = read('role', FALSE, $where);

			if (count($records) > 0)
				$role_records[] = array('name' => $records[0]['name']);
		}
		
		sort($role_records);
	}

	return array(
		'status' => 1,
		'data' => array(
			'records' => $role_records,
			'count' => count($role_records)
		)
	);
});

beat('permrole', 'add', function ($permname, $rolename)
{
	if (!has_permission('permrole_add'))
		return error_pack(err_access_denied);
	
	$permid = 0;
	$where = equ('name', $permname, 'string');
	$records = read('permission', FALSE, $where);
	
	if (count($records) > 0)
		$permid = $records[0]['id'];
	else // record does not exist
		return array(
			'status' => 1
		);

	$roleid = 0;
	$where = equ('name', $rolename, 'string');
	$records = read('role', FALSE, $where);
	
	if (count($records) > 0)
		$roleid = $records[0]['id'];
	else // record does not exist
		return array(
			'status' => 1
		);

	$where = equ('permissionid', $permid) . ' AND ' . equ('roleid', $roleid);
	$records = read('permrole', FALSE, $where);
	
	if (count($records) > 0)
		return array(
			'status' => 1
		);	

	$id = create('permrole', array(
		'permissionid' => $permid,
		'roleid' => $roleid
	));
	
	if ($id <= 0)
		return error_pack(err_create_failed);
	
	return array(
		'status' => 1
	);
});

beat('permrole', 'remove', function ($permname, $rolename)
{
	if (!has_permission('permrole_remove'))
		return error_pack(err_access_denied);
	
	$permid = 0;
	$where = equ('name', $permname, 'string');
	$records = read('permission', FALSE, $where);
	
	if (count($records) > 0)
		$permid = $records[0]['id'];
	else // record does not exist
		return array(
			'status' => 1
		);

	$roleid = 0;
	$where = equ('name', $rolename, 'string');
	$records = read('role', FALSE, $where);
	
	if (count($records) > 0)
		$roleid = $records[0]['id'];
	else // record does not exist
		return array(
			'status' => 1
		);

	$where = equ('permissionid', $permid) . ' AND ' . equ('roleid', $roleid);
	$records = read('permrole', FALSE, $where);
	
	if (count($records) > 0) {
		if (!delete('permrole', $where))
			return error_pack(err_delete_failed);	
	}
	
	return array(
		'status' => 1
	);
});

beat('preference', 'list', function ($restart)
{
	global $page_size;
	
	if (!has_permission('preference_list'))
		return error_pack(err_access_denied);
	
	if (isset($page_size) && $page_size > 0)
		$limit = $page_size;
	else
		$limit = 25;
		
	$ses_table_offset = 'preference_list_offset';
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);
	
	$records = read('preference', FALSE, FALSE, 'name', FALSE, $limit, $offset);
	$rcnt = count($records);

	if ($rcnt > 0) {
	
		foreach ($records as &$r) {
			unset($r['id']);
		}
		
		set_session($ses_table_offset, $offset + $limit);
	}
	else
		set_session($ses_table_offset, 0);

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('preference', 'create', function ($name, $value)
{
	if (!has_permission('preference_create'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('preference', FALSE, $where);
	
	if (count($records) > 0)
		return error_pack(err_record_already_exists);
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

beat('preference', 'read', function ($name)
{
	if (!has_permission('preference_read'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('preference', FALSE, $where);
	$rcnt = count($records);

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

beat('preference', 'update', function ($name, $value)
{
	if (!has_permission('preference_update'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');

	if (!update('preference', array('value' => $value), $where))
		return error_pack(err_update_failed);

	return array(
		'status' => 1
	);
});

beat('preference', 'delete', function ($name)
{
	if (!has_permission('preference_delete'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');

	$rid = 0;
	$records = read('preference', FALSE, $where);
	
	if (count($records) > 0)
		$rid = $records[0]['id'];
	else // record does not exist or already deleted
		return array(
			'status' => 1
		);

	if (!delete('preference', $where))
		return error_pack(err_delete_failed);

	// Try to read back the record
	$records = read('preference', FALSE, $where);
	
	if (count($records) > 0)
		return error_pack(err_delete_failed);
		
	if ($rid > 0) {
		// TODO: delete all records that refer to this preference
	}
	
	return array(
		'status' => 1
	);
});




