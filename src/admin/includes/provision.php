<?php
// provision.php
// Harubi-Front Provisioning
// By Abdullah Daud, chelahmy@gmail.com
// 3 December 2022

$harubi_settings_path = "../../includes/settings.inc";
require_once "../../includes/common.php";

$superuser_name = 'admin';
$superuser_password = 'Admin1234!';

$preferences = array(
	'new_user_role' => 'member',
	'page_size' => '25',
	'session_max_signins_per_week' => '30',
	'session_timeout' => '7200',
);

$roles = array(
	'administrator' => array('premium' => true),
	'member' => array('premium' => false),
);


$permissions = array(
	'edit_members' => array('administrator'),
	'edit_own_usergroups' =>  array('administrator', 'member'),
	'edit_own_members' => array('administrator', 'member'),
	'edit_own_profile' => array('administrator', 'member'),
	'edit_permission_roles' => array('administrator'),
	'edit_permissions' => array('administrator'),
	'edit_preferences' => array('administrator'),
	'edit_roles' => array('administrator'),
	'edit_users' => array('administrator'),
	'list_groups' => array('administrator'),
	'list_members' => array('administrator'),
	'list_own_groups' => array('administrator', 'member'),
	'list_own_members' => array('administrator', 'member'),
	'list_permissions' => array('administrator'),
	'list_preferences' => array('administrator'),
	'list_roles' => array('administrator'),
	'list_users' => array('administrator'),
	'member_add' => array('administrator'),
	'member_add_own' => array('administrator', 'member'),
	'member_list' => array('administrator'),
	'member_list_own' => array('administrator', 'member'),
	'member_read' => array('administrator'),
	'member_read_own' => array('administrator', 'member'),
	'member_remove' => array('administrator'),
	'member_remove_own' => array('administrator', 'member'),
	'member_update' => array('administrator'),
	'member_update_own' => array('administrator', 'member'),
	'permission_create' => array('administrator'),
	'permission_delete' => array('administrator'),
	'permission_list' => array('administrator'),
	'permission_read' => array('administrator'),
	'permission_update' => array('administrator'),
	'permrole_add' => array('administrator'),
	'permrole_listall' => array('administrator'),
	'permrole_remove' => array('administrator'),
	'preference_create' => array('administrator'),
	'preference_delete' => array('administrator'),
	'preference_list' => array('administrator'),
	'preference_read' => array('administrator'),
	'preference_update' => array('administrator'),
	'role_create' => array('administrator'),
	'role_delete' => array('administrator'),
	'role_list' => array('administrator'),
	'role_listall' => array('administrator'),
	'role_read' => array('administrator'),
	'role_update' => array('administrator'),
	'system_provision' => array('administrator'),
	'user_create' => array('administrator'),
	'user_delete' => array('administrator'),
	'user_list' => array('administrator'),
	'user_read' => array('administrator'),
	'user_read_own' => array('administrator', 'member'),
	'user_update' => array('administrator'),
	'user_update_own' => array('administrator', 'member'),
	'user_view_profile' => array('administrator', 'member'),
	'usergroup_create' => array('administrator'),
	'usergroup_create_own' => array('administrator', 'member'),
	'usergroup_delete' => array('administrator'),
	'usergroup_delete_own' => array('administrator', 'member'),
	'usergroup_list' => array('administrator'),
	'usergroup_list_own' => array('administrator', 'member'),
	'usergroup_read' => array('administrator'),
	'usergroup_read_own' => array('administrator', 'member'),
	'usergroup_update' => array('administrator'),
	'view_administration' => array('administrator'),
	'view_user_profile' => array('administrator', 'member')
);

function create_superadmin() {
	global $superuser_name;
	global $superuser_password;

	if (get_user_count() > 0) // user already created
		return TRUE;
		
	$now = time();
	$hash = password_hash($superuser_password, PASSWORD_BCRYPT);
	$id = create('user', array(
		'name' => $superuser_name,
		'password' => $hash,
		'email' => '',
		'roleid' => get_roleid_by_name("administrator"),
		'valid_thru' => 0,
		'signins' => 0,
		'last_signedin_utc' => 0,
		'signin_note' => 0,
		'created_utc' => $now,
		'updated_utc' => $now
	));    
	
	if ($id > 0)
		return TRUE;
		
	return FALSE;
}

beat('system', 'provision', function ()
{
	global $preferences, $roles, $permissions;
	
	if (!has_permission('system_provision'))
		return error_pack(err_access_denied);
	
	$err_ext = array();
	
	foreach ($preferences as $preference => $value) {
		$prefid = set_preference($preference, $value);
		
		if ($prefid <= 0)
			$err_ext[] = "preference '$preference'";
	}
	
	foreach ($roles as $role => &$data) {
		$where = equ('name', $role, 'string');
		$records = read('role', FALSE, $where);
		
		if (count($records) > 0) {
			$data['id'] = intval($records[0]['id']);
		}
		else {
			$data['id'] = create('role', array(
				'name' => $role,
				'premium' => $data['premium']
			));
		}
		
		if ($data['id'] <= 0)
			$err_ext[] = "role '$role'";
	}

	if (!create_superadmin())
		$err_ext[] = "superadmin";
	
	foreach ($permissions as $permission => $permroles) {
		$where = equ('name', $permission, 'string');
		$records = read('permission', FALSE, $where);
		
		if (count($records) > 0) {
			$permid = intval($records[0]['id']);
		}
		else {
			$permid = create('permission', array(
				'name' => $permission,
			));
		}
		
		if ($permid > 0) {
			foreach ($permroles as $permrole) {
				if (isset($roles[$permrole]) && $roles[$permrole]['id'] > 0) {
					$roleid = $roles[$permrole]['id'];

					$where = equ('permissionid', $permid) . ' AND ' . equ('roleid', $roleid);
					$records = read('permrole', FALSE, $where);
					
					if (count($records) > 0)
						$id = intval($records[0]['id']);
					else {
						$id = create('permrole', array(
							'permissionid' => $permid,
							'roleid' => $roleid
						));	
					}
					
					if ($id <= 0)
						$err_ext[] = "permission '$permission' -> role '$permrole'";
				}
			}
		}
		else
			$err_ext[] = "permission '$permission'";
	}
	
	if (count($err_ext) > 0)
		error_pack(err_missing, $err_ext);
	
	return array(
		'status' => 1
	);
});

