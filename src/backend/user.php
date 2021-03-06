<?php
// Basic User Model
// By Abdullah Daud
// 25 October 2019

/**
* A basic user model for a simple user-based application.
* - Sign up to become a user. Subsequent same user sign up will have to wait for 5 minutes to deter spamming.
* - Sign in as an authenticated user, and have permissions to view and change own records.
* - User with id=1 is the super-user who have all permissions.
*/

// 1 February 2020
// - Added status field. One of the uses is to mark user as cancelled.
// - Added cancel_own action to cancel signed in user account.

// NOTE: To allow for multiple require() of this module
// all function declarations must be done elsewhere,
// and be require_once().
require_once 'user_helper.php';

$sid = session_id();

if (!is_string($sid) || strlen($sid) <= 0)
	session_start();

preset('permission_authenticated_user', function ($model, $action, &$ctrl_args)
{
	if ($model == 'user' && in_array($action, ['signup', 'signin'])) // No need authentication
		return;

	if (get_user_id() <= 0)
		return respond_error(1000, "You have not signed in or your session timed out.");
});

preset('permission_super_user', function ($model, $action, &$ctrl_args)
{
	if ($model == 'user' && in_array($action, ['signup', 'signin', 'signout', 'cancel_own', 'read_own', 'update_own'])) // No need super-user permission
		return;

	if (is_super_user())
		return;

	return respond_error(1001, "Access denied.");
});

preset('permission_change_user', function ($model, $action, &$ctrl_args)
{
	if ($model == 'user' && in_array($action, ['update', 'delete']))
	{
		$name = $ctrl_args[0]; // name is the first argument in update and delete
		$where = equ('name', $name, 'string');
		$records = read(['table' => 'user', 'where' => $where]);

		if (count($records) > 0)
		{
			$uid = $records[0]['id'];

			if ($model == 'user' && $action == 'delete' && $uid == 1)
				return respond_error(1002, "Cannot delete super-user record.");
		}
		else
			return respond_error(1003, "The user record does not exist. ");
	}
});

beat('user', 'signup', function ($name, $password, $email)
{
	// Delay the next same user sign up for 5 minutes to deter spamming
	if (isset($_SESSION['last_reg']) && (time() - $_SESSION['last_reg']) < 300)
		return respond_error(1, "Could not sign up new user now. Please try again later.");

	$where = equ('name', $name, 'string');
	$records = read(['table' => 'user', 'where' => $where]);

	if (count($records) <= 0)
	{
		$now = time();
		$hash = password_hash($password, PASSWORD_BCRYPT);
		$id = create('user', array(
			'name' => $name,
			'password' => $hash,
			'email' => $email,
			'status' => 0,
			'created_utc' => $now,
			'updated_utc' => $now
		));

		if ($id > 0)
		{
			$_SESSION['last_reg'] = $now;

			return respond_ok();
		}

		return respond_error(2, "Could not sign up new user.");
	}

	return respond_error(3, "A user with the given name already exists.");
});

beat('user', 'signin', function ($name, $password)
{
	$where = equ('name', $name, 'string');
	$records = read(['table' => 'user', 'where' => $where]);

	if (count($records) > 0)
	{
		if ($records[0]['status'] == 0 && password_verify($password, $records[0]['password']))
		{
			$id = $records[0]['id'];
			$name = $records[0]['name'];
			$email = $records[0]['email'];
			$created_utc = $records[0]['created_utc'];
			$_SESSION['user'] = [];
			$_SESSION['user']['id'] = $id;
			$_SESSION['user']['name'] = $name;
			$_SESSION['user']['email'] = $email;
			$_SESSION['user']['created_utc'] = $created_utc;

			return respond_ok([
				'admin' => $id == 1 ? 1 : 0,
				'name' => $name,
				'email' => $email,
				'created_utc' => $created_utc
			]);
		}
	}

	return respond_error(1, "Could not sign you in."); // for security reason: should not give more details on why the sign in failed
});

beat('user', 'signout', function ()
{
	$_SESSION['user'] = null;
	unset($_SESSION['user']);

	return respond_ok();
});

beat('user', 'cancel_own', function ($password)
{
	$name = $_SESSION['user']['name'];
	$where = equ('name', $name, 'string');
	$records = read(['table' => 'user', 'where' => $where]);

	if (count($records) > 0)
	{
		if ($records[0]['status'] == 0 && password_verify($password, $records[0]['password']))
		{
			$now = time();

			if (update('user', array('status' => 1, 'updated_utc' => $now), $where))
			{
				$_SESSION['user'] = null; // sign out user
				unset($_SESSION['user']);
				return respond_ok();
			}
		}
	}

	return respond_error(1, "Could not cancel user account.");
});

// Read user own record
beat('user', 'read_own', function ()
{
	return respond_ok([
		'name' => $_SESSION['user']['name'],
		'email' => $_SESSION['user']['email'],
		'created_utc' => $_SESSION['user']['created_utc']
	]);
});

beat('user', 'read', function ($name)
{
	$where = equ('name', $name, 'string');
	$records = read(['table' => 'user', 'where' => $where]);

	if (count($records) > 0)
	{
		return respond_ok([
			'name' => $records[0]['name'],
			'email' => $records[0]['email'],
			'created_utc' => $records[0]['created_utc']
		]);
	}

	return respond_error(1, "Could not read user record.");
});

// Update user own record
beat('user', 'update_own', function ($old_password, $new_password, $email)
{
	$name = $_SESSION['user']['name'];
	$where = equ('name', $name, 'string');
	$password = '';
	$oplen = strlen($old_password);
	$nplen = strlen($new_password);

	if ($oplen > 0 || $nplen > 0) { // expecting to update password
		if ($oplen > 0 && $nplen > 0 && strcmp($old_password, $new_password) != 0)
		{
			$records = read(['table' => 'user', 'where' => $where]);

			if (count($records) > 0 && password_verify($old_password, $records[0]['password']))
				$password = $new_password;
		}

		if (strlen($password) <= 0)
			return respond_error(2, "Could not update user record.");
	}

	return _update_user($password, $email, $where);
});

beat('user', 'update', function ($name, $password, $email)
{
	$where = equ('name', $name, 'string');

	return _update_user($password, $email, $where);
});

beat('user', 'delete', function ($name)
{
	$where = equ('name', $name, 'string');

	if (delete('user', $where))
		return respond_ok();

	return respond_error(1, "Cannot delete user record.");
});

?>
