<?php
// main.php
// Harubi-Front Main
// By Abdullah Daud, chelahmy@gmail.com
// 7 December 2022

$harubi_settings_path = "../../includes/settings.inc";
require_once '../../includes/common.php';

beat('user', 'view_profile', function ($name)
{
	if (!has_permission('user_view_profile'))
		return error_pack(err_access_denied);
	
	$where = equ('name', $name, 'string');
	$records = read('user', array('name', 'created_utc'), $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed);
	
	$rcnt = record_cnt($records);

	return array(
		'status' => 1,
		'data' => array(
			'records' => $records,
			'count' => $rcnt
		)
	);
});

beat('user', 'read_own', function ()
{
	if (!has_permission('user_read_own'))
		return error_pack(err_access_denied);
	
	$name = signedin_uname();
	$where = equ('name', $name, 'string');
	$records = read('user', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed);
	
	$rcnt = record_cnt($records);
	
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

beat('user', 'update_own', function ($password, $email)
{
	if (!has_permission('user_update_own'))
		return error_pack(err_access_denied);
	
	$name = signedin_uname();
	$now = time();
	$where = equ('name', $name, 'string');

	if (strlen($password) > 0) {
		$hash = password_hash($password, PASSWORD_BCRYPT);
		if (!update('user', array(
			'password' => $hash,
			'email' => $email,
			'updated_utc' => $now
			), $where))
			return error_pack(err_update_failed);
	}
	else {
		if (!update('user', array(
			'email' => $email,
			'updated_utc' => $now
			), $where))
			return error_pack(err_update_failed);
	}
	
	return array(
		'status' => 1
	);
});

beat('usergroup', 'list_own', function ($restart, $type = 0, $search = '', $order_by = 'name', $sort = 'ASC')
{
	global $page_size;
	
	if (!has_permission('usergroup_list_own'))
		return error_pack(err_access_denied);
	
	if (isset($page_size) && $page_size > 0)
		$limit = $page_size;
	else
		$limit = 25;
		
	$ses_table_offset = 'usergroup_list_own_offset';
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);
	
	$records = FALSE;
	$rcnt = 0;
	$uid = signedin_uid();
	$order_by = clean($order_by, 'string');
	$sort = clean($sort, 'string');
		
	if ($type == 0) {
		$where = equ('owneruserid', $uid);

		if (strlen($search) > 0) {
			$search = clean($search, 'string');
			$where .= " AND MATCH(name) AGAINST('" . $search . "')";
		}
			
		$records = read('usergroup', FALSE, $where, $order_by, $sort, $limit, $offset);

		if (read_failed($records))
			return error_pack(err_read_failed);
		
		$rcnt = record_cnt($records);
	}
	else {
		$where = "(`usergroup`.`owneruserid` = $uid OR `member`.`userid` = $uid)";

		if (strlen($search) > 0) {
			$search = clean($search, 'string');
			$where .= " AND MATCH(`usergroup`.`name`) AGAINST('" . $search . "')";
		}
			
		$order_by = "`usergroup`.`$order_by`";

		$query = "SELECT `usergroup`.`ref` AS `ref`, `usergroup`.`name` AS `name`, `usergroup`.`owneruserid` AS `owneruserid`, `usergroup`.`created_utc` AS `created_utc`";
		$query .= " FROM `usergroup` LEFT OUTER JOIN `member` ON `usergroup`.`id` = `member`.`usergroupid`";
		$query .= " WHERE $where";
		$query .= " ORDER BY $order_by $sort LIMIT $offset, $limit;";
		
		$db = connect_db();

		if ($db === FALSE)
			return error_pack(err_read_failed);

		$records = array();
		
		if ($result = mysqli_query($db, $query)) {
			while ($row = mysqli_fetch_assoc($result))
				$records[] = $row;
				
			mysqli_free_result($result);
		}
		
		mysqli_close($db);
		$rcnt = count($records);		
	}

	if ($rcnt > 0) {
		foreach ($records as &$r) {
			unset($r['id']);
			$owneruserid = intval($r['owneruserid']);
			unset($r['owneruserid']);
			$r['ownername'] = get_username_by_id($owneruserid);
			unset($r['created_by']);
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

beat('usergroup', 'create_own', function ($name)
{
	if (!has_permission('usergroup_create_own'))
		return error_pack(err_access_denied);
	
	$owneruserid = signedin_uid();

	if ($owneruserid <= 0)
		return error_pack(err_record_missing, "Owner");
	
	$ref = new_table_row_ref('usergroup');
	
	if (strlen($ref) <= 0)
		return error_pack(err_cannot_create_ref);
	
	$now = time();
	$id = create('usergroup', array(
		'name' => $name,
		'ref' => $ref,
		'owneruserid' => $owneruserid,
		'created_by' => $owneruserid,
		'created_utc' => $now,
		'updated_utc' => $now
	));    
	
	if ($id <= 0)
		return error_pack(err_create_failed);
	
	return array(
		'status' => 1
	);
});

beat('usergroup', 'read_own', function ($ref)
{
	if (!has_permission('usergroup_read_own'))
		return error_pack(err_access_denied);
	
	$ginfo = get_group_info($ref);
	
	if (!$ginfo['owner'] && !$ginfo['member'])
		return error_pack(err_access_denied);
	
	$records = $ginfo['group_records'];
	$rcnt = record_cnt($records);

	if ($rcnt > 0) {	
		foreach ($records as &$r) {
			unset($r['id']);
			$owneruserid = intval($r['owneruserid']);
			unset($r['owneruserid']);
			$r['ownername'] = get_username_by_id($owneruserid);
			$created_by = intval($r['created_by']);
			unset($r['created_by']);
			$r['created_by_username'] = get_username_by_id($created_by);
			$r['is_owner'] = $ginfo['owner'];
			$r['is_member'] = $ginfo['member'];
			$r['is_admin'] = $ginfo['admin'];
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

beat('usergroup', 'update_own', function ($ref, $name)
{
	if (!has_permission('usergroup_update_own'))
		return error_pack(err_access_denied);
	
	$ginfo = get_group_info($ref);
	
	if (!$ginfo['owner'] && !$ginfo['admin'])
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

beat('usergroup', 'delete_own', function ($ref)
{
	if (!has_permission('usergroup_delete_own'))
		return error_pack(err_access_denied);
	
	$ginfo = get_group_info($ref);
	
	if (!$ginfo['owner'] && !$ginfo['admin'])
		return error_pack(err_access_denied);
	
	$rid = 0;
	$records = $ginfo['group_records'];
	
	if (record_cnt($records) > 0)
		$rid = intval($records[0]['id']);
	else // record does not exist or already deleted
		return array(
			'status' => 1
		);

	$where = equ('ref', $ref, 'string');

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

beat('member', 'list_own', function ($restart, $groupref, $search = '', $order_by = 'created_utc', $sort = 'ASC')
{
	global $page_size;
	
	if (!has_permission('member_list_own'))
		return error_pack(err_access_denied);
	
	$ginfo = get_group_info($groupref);
	
	if (!$ginfo['owner'] && !$ginfo['member'])
		return error_pack(err_access_denied);
	
	if (isset($page_size) && $page_size > 0)
		$limit = $page_size;
	else
		$limit = 25;
		
	$ses_table_offset = 'member_list_offset';
	
	if ($restart == 1)
		set_session($ses_table_offset, 0);
		
	$offset = get_session($ses_table_offset);
	
	$usergroupid = 0;
	$records = $ginfo['group_records'];
	
	if (record_cnt($records) > 0)
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
	$rcnt = record_cnt($records);

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

beat('member', 'read_own', function ($groupref, $username)
{
	if (!has_permission('member_read_own'))
		return error_pack(err_access_denied);
	
	$ginfo = get_group_info($groupref);
	
	if (!$ginfo['owner'] && !$ginfo['member'])
		return error_pack(err_access_denied);
	
	$userid = get_userid_by_name($username);

	if ($userid <= 0)
		return error_pack(err_record_missing, "member: @username", array('@username' => $username));
	
	$usergroupid = 0;
	$records = $ginfo['group_records'];
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

beat('member', 'add_own', function ($groupref, $username, $rolename = 'member')
{
	if (!has_permission('member_add_own'))
		return error_pack(err_access_denied);
	
	$ginfo = get_group_info($groupref);
	
	if (!$ginfo['owner'] && !$ginfo['admin'])
		return error_pack(err_access_denied);
	
	$userid = get_userid_by_name($username);

	if ($userid <= 0)
		return error_pack(err_record_missing, "member: @username", array('@username' => $username));

	$usergroupid = 0;
	$records = $ginfo['group_records'];
	$rcnt = record_cnt($records);
	
	if ($rcnt > 0)
		$usergroupid = intval($records[0]['id']);
	
	if ($usergroupid <= 0)
		return error_pack(err_record_missing, "groupref: @groupref", array('@groupref' => $groupref));
	
	$where = equ('userid', $userid) . ' AND ' . equ('usergroupid', $usergroupid);
	$records = read('member', FALSE, $where);
	
	if (read_failed($records))
		return error_pack(err_read_failed, "groupref: @groupref, member: @username", array('@groupref' => $groupref, '@username' => $name));
	
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

beat('member', 'update_own', function ($groupref, $username, $rolename)
{
	if (!has_permission('member_update_own'))
		return error_pack(err_access_denied);
	
	$ginfo = get_group_info($groupref);
	
	if (!$ginfo['owner'] && !$ginfo['admin'])
		return error_pack(err_access_denied);
	
	$userid = get_userid_by_name($username);

	if ($userid <= 0)
		return error_pack(err_record_missing, "member: @username", array('@username' => $username));
	
	$usergroupid = 0;
	$records = $ginfo['group_records'];
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

beat('member', 'remove_own', function ($groupref, $username)
{
	if (!has_permission('member_remove_own'))
		return error_pack(err_access_denied);

	$ginfo = get_group_info($groupref);
	
	if (!$ginfo['owner'] && !$ginfo['admin'])
		return error_pack(err_access_denied);
	
	$userid = get_userid_by_name($username);

	if ($userid <= 0)
		return error_pack(err_record_missing, "member: @username", array('@username' => $username));

	$usergroupid = 0;
	$records = $ginfo['group_records'];
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




