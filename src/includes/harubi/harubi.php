<?php
// harubi.php
// Harubi - A Model-Action framework.
// By Abdullah Daud, chelahmy@gmail.com
// 14 November 2013
// - The project start date.
// 10 December 2018
// - No development record had been logged since the start date. Howerever,
//   it was already applicable as per general description below.
// - This project has been dormant for a long time and it will find a new life.
//   The concept is not new and there are many MVC frameworks available. However,
//   harubi is focusing on model and controller only. Hence, harubi is simpler
//   minus the view baggage.
// 12 December 2018
// - Introduced the *blow* routing to simplify URL rewrite.
// 3 August 2019
// - Added table prefix to cater for multiple systems on a single database.
// 10 October 2019
// - Added checking table name in settings through table_defined() before proceeding
//   with execution in functions that refer to table.
// - Added option to collapse read() parameters into an array.
// - Added option to collapse parameters $type and $op in equ(). See equ().
// - Added more comments on various functions.
// 22 October 2019
// - Deprecate injected method $harubi_permission_controller and attach_permission_controller().
// - Deprecate injected method $harubi_cache_func.
// - Added injection method handlers preset() and toll().
// - Renamed $harubi_do_log_querystring to $harubi_do_log_sql_querystring so that not to
//   confuse sql query string with url query string.
// - Renamed *dump_log to *dump_logs for proper meaning.
// 26 October 2019
// - Added request() function for internal request.
// - Changed router() finishing from exit() to echo to facilitate internal request().
// 28 October 2019
// - Cached the names of the last preset and toll intervened.
// 30 October 2019
// - Applied url-rewriting friendly arguments to beat().
// - Corrected request() changing behavior after beat() update. 
// 21 November 2022
// - Enable/disable logs via settings.
// - Added clear_harubi_logs() function.
// - Added harubi_logs_has_error() function.
// - Added error logs when table settings is missing.
// 7 December 2022
// - Improved settings handling in case of buggy settings file.
// - Updated the respond_*() functions after some experience with the client side.
// - Added record_cnt() function.
// - Added read_failed() function.
// 8 December 2022
// - Removed a bug in harubi_log() that could not find logs setting because of
//   $harubi_settings was not global. It was there for a while without notice.

// Literally, *harubi* is a keris with a golden handle, a Malay traditional hand weapon.
// Beat and blow are offensive hand movements with or without weapon against an opponent.
// There is no negative connotation on the words used when we are focusing on winning.

// This harubi is an application framework similar to the Model-View-Controller (MVC)
// framework minus the view concern. Harubi introduces routing handlers called *beat*
// and *blow*.

// Harubi is designed to serve rich client applications. All viewing concerns are delegated
// to the client side. Harubi handles the models and controllers at the server side.
// Requests are handled using the beat/blow pattern.

// A model in harubi is not just a wrapper to a relational database table. A model can be
// as complex as involving multiple database tables. Harubi introduced a model-action
// pattern. Every request to a harubi service is acting on a model. A request will be
// routed to a controller that handles a model-action. Hence, every controller in harubi
// is handling a model-action.

// A beat/blow is a request routing. A beat/blow is expecting a query to contain at least
// two parameters: a model name and an action to the model. It will invoke the controller
// for the model-action. Arguments for the controller may be passed along with the
// query. A controller is passed as a closure to the beat()/blow() function. The beat/blow
// is expecting the controller to return an associative array which will then be converted
// to JSON before being passed as the response to the request. See the comment on the
// beat()/blow() function implementation. If the controller does not return an array then
// beat()/blow() will return it as-is.

// For the modelling, harubi implements CRUD with object relational mapping (ORM) for MySQL.
// Every object has a unique ID mapped to the primary index of its table. The create()
// function returns the new id. The id handling is simplified in the *where* clause. See
// the where_id() function.

// Harubi is initialized with the database settings. Fields have to be mapped for PHP type
// conversion including integer, float and string. The conversion also protect the database
// from SQL injection attacks. See the harubi() function for details.

$harubi_settings = NULL;
$harubi_mysql_settings = NULL;
$harubi_table_settings = NULL;
$harubi_query = NULL;

$harubi_routing_done = FALSE;

// Injection method handlers
$harubi_presets = array();
$harubi_tolls = array();
$harubi_last_preset_intervened = FALSE;
$harubi_last_toll_intervened = FALSE;

// Log variables
$harubi_logs = array();
$harubi_do_dump_logs = FALSE;
$harubi_do_log_sql_querystring = TRUE;
$harubi_do_log_presets = TRUE;
$harubi_do_log_tolls = TRUE;
$harubi_respond_with_logs = FALSE;

function harubi_log($file, $function, $line, $type, $message)
{
	global $harubi_settings, $harubi_logs;
	
	$logs = false;
	
	if (isset($harubi_settings) && isset($harubi_settings['logs']))
		$logs = intval($harubi_settings['logs']) > 0;
		
	if ($logs) {
		$harubi_logs[] = array(
			'file' => $file,
			'function' => $function,
			'line' => $line,
			'type' => $type,
			'message' => $message
		);
	}
}

function harubi_log_debug($line, $message)
{
	harubi_log('', '', $line, 'debug', $message);
}

function get_harubi_logs()
{
	global $harubi_logs;
	return $harubi_logs;
}

function print_harubi_logs()
{
	global $harubi_logs;
	
	echo '<pre>';
	print_r($harubi_logs);
	echo '</pre>';
}

function dump_harubi_logs()
{
	global $harubi_logs;
	file_put_contents('harubi.log', json_encode($harubi_logs, JSON_PRETTY_PRINT));
}

function clear_harubi_logs()
{
	global $harubi_logs;
	$harubi_logs = array();
}

function harubi_logs_has_error()
{
	global $harubi_logs;
	
	foreach ($harubi_logs as $log)
	{
		if (strtolower($log['type']) == 'error')
			return TRUE;
	}
	
	return FALSE;
}

/**
* Response Convention
*
* The following are optional message enveloping functions to be used in reponding to a request.
* The requester is expected to evaluate the 'status' field first which may have the following values:
*
* 'status'
*    0: an error occurred while processing the request.
*    1: the request had been successfully processed.
*
* And then the 'data' field.
*
* On status = 0:
* The following 'data' fields have more information about the error:
* 'error': the code representing the error.
* 'error_msg': the fixed textual error message according to the 'error' code.
* 'error_ext': the optional extended textual error message.
*
* On status = 1:
* 'data': the data for the request. 
*
*/

function respond_error($error, $error_msg, $error_ext = '')
{
	global $harubi_respond_with_logs;
	global $harubi_logs;
	
	$respond = array(
		'status' => 0,
		'data' => array(
			'error' => $error, 
			'error_msg' => $error_msg,
			'error_ext' => $error_ext
			)
		);
		
	if ($harubi_respond_with_logs)
		$respond['data']['logs'] = $harubi_logs;
		
	return $respond;
}

function respond_system_error($error = -1)
{
	return respond_error($error, "System error");
}

function respond_ok($data = array())
{
	global $harubi_respond_with_logs;
	global $harubi_logs;
	
	$respond = array(
		'status' => 1,
		'data' => $data
		);
					
	if ($harubi_respond_with_logs)
		$respond['data']['logs'] = $harubi_logs;
		
	return $respond;
}

/**
* Check if the read() function returned an error.
*/
function read_failed($records) {
	return $records === FALSE || !is_array($records);
}

/**
* Proper check on the count of the records returned by the read() function.
* On error, read() returns FALSE.
*/
function record_cnt($records) {
	return $records === FALSE || !is_array($records) ? 0 : count($records);
}

/**
* Harubi initialization function.
* @param $settings can be a filename or a JSON string with the following example
* structure. Default to filename 'settings.inc':
* {
*	"globals" : {
*		"do_dump_logs" : true,
*		"do_log_sql_querystring" : true,
*		"respond_with_logs" : false
*	},
*	
*	"mysql" : {
*		"hostname" : "localhost",
*		"username" : "root",
*		"password" : "****",
*		"database" : "board",
*		"prefix"   : ""
*	},
*	
*	"tables" : {
*		"table_1" : {
*			"user_id" : "int",
*			"message" : "string",
*			"created_utc" : "int"
*		},
*		"table_2" : {
*			"weight" : "float",
*			"created_utc" : "int"
*		}
*	}
* }
*
* The first part is the Harubi global settings.
*
* The second part is the MySQL connection settings.
*
* The third part is the tables declaration with fields mapping. Every table is
* assumed to have the id field that does not have to be declared. There are
* three types of mapping: int, float and string.
* 
* Harubi does not create the tables.
*
* @return nothing
*/
function harubi($settings = 'settings.inc')
{
	global $harubi_settings;
	global $harubi_mysql_settings;
	global $harubi_table_settings;

	if (!is_array($settings))
	{
		if (file_exists($settings))
			$settings = file_get_contents($settings);
		elseif ($settings == 'settings.inc')	
			harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'settings.inc file does not exist');
		
		$settings = json_decode($settings, TRUE);
	}
		
	$harubi_settings = $settings;
	
	if (isset($settings['globals']))
	{
		global $harubi_do_dump_logs;
		global $harubi_do_log_sql_querystring;
		global $harubi_do_log_presets;
		global $harubi_do_log_tolls;
		global $harubi_respond_with_logs;
		
		$globals = $settings['globals'];
		
		if (isset($globals['do_dump_logs']))
			$harubi_do_dump_logs = $globals['do_dump_logs'];
		
		if (isset($globals['do_log_sql_querystring']))
			$harubi_do_log_sql_querystring = $globals['do_log_sql_querystring'];
		
		if (isset($globals['do_log_presets']))
			$harubi_do_log_presets = $globals['do_log_presets'];
		
		if (isset($globals['do_log_tolls']))
			$harubi_do_log_tolls = $globals['do_log_tolls'];
		
		if (isset($globals['respond_with_logs']))
			$harubi_respond_with_logs = $globals['respond_with_logs'];
	}
	
	if (isset($settings['mysql']))
		$harubi_mysql_settings = $settings['mysql'];
	else
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'warning', 'No setting for MySQL');

	if (isset($settings['tables']))
		$harubi_table_settings = $settings['tables'];
	else
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'warning', 'No setting for tables');
}

/**
* Add to harubi table settings.
*/
function add_table_settings($settings = 'settings.inc') {
	global $harubi_table_settings;

	if (!is_array($settings)) {
		if (file_exists($settings))
			$settings = file_get_contents($settings);
		elseif ($settings == 'settings.inc')	
			harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'settings.inc file does not exist');
		
		$settings = json_decode($settings, TRUE);
	}
	
	if (isset($settings['tables'])) {
		if (count($harubi_table_settings) <= 0)
			$harubi_table_settings = $settings['tables'];
		else
			$harubi_table_settings = array_merge($harubi_table_settings, $settings['tables']);
	}
}

/**
* Get harubi mysql settings
*/
function get_mysql_settings($name) {
	global $harubi_mysql_settings;
	
	if (!isset($harubi_mysql_settings) || !is_array($harubi_mysql_settings))
	{
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'MySQL settings are missing');
		return FALSE;
	}
	
	if (isset($harubi_mysql_settings[$name]))
			return $harubi_mysql_settings[$name];
	else
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'MySQL setting is missing - ' . $name);
			
	return FALSE;
}

/**
* Get harubi table settings
*/
function get_table_settings($name) {
	global $harubi_table_settings;
	
	if (!isset($harubi_table_settings) || !is_array($harubi_table_settings))
	{
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Table settings are missing');
		return FALSE;
	}
	
	if (isset($harubi_table_settings[$name]))
			return $harubi_table_settings[$name];
	else
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Table setting is missing - ' . $name);
			
	return FALSE;
}

/**
* Get harubi table field setting
*/
function get_table_field_settings($name, $field) {
	global $harubi_table_settings;
	
	if (!isset($harubi_table_settings) || !is_array($harubi_table_settings))
	{
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Table settings are missing');
		return FALSE;
	}
	
	if (isset($harubi_table_settings[$name])) {
		if (isset($harubi_table_settings[$name][$field]))
			return $harubi_table_settings[$name][$field];
		else
			harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Table field setting is missing - ' . $name . '->' . $field);
	}
	else
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Table setting is missing - ' . $name);
			
	return FALSE;
}

/**
* Connect to MySQL database.
*/
function connect_db()
{
	$hostname = get_mysql_settings('hostname'); 
	$username = get_mysql_settings('username');
	$password = get_mysql_settings('password'); 
	$database = get_mysql_settings('database');
	
	for ($i = 0; $i < 3; $i++) // retry 3 times
	{
		$dbi = mysqli_connect($hostname, $username, $password, $database);

		if (!$dbi || mysqli_connect_errno() != 0)
		{
			harubi_log(__FILE__,__FUNCTION__, __LINE__, 'warning', 'Tried to connect with MySQL but failed');
			sleep(5); // wait for 5 seconds before retry
			$dbi = FALSE;
		}
	}
	
	if ($dbi === FALSE)
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Failed to connect with MySQL');
	
	return $dbi;	
}

/**
* Apply database string escaping format to $str to prevent SQL injection. 
*/
function esc($db, $str, $like = FALSE)
{
	$str = mysqli_real_escape_string($db, $str);
	
	// Underscore and percent have special meanings in LIKE clause
	if ($like)
		return addcslashes($str, '%_');
		
	return $str;
}

/**
* If defined in settings then prepend table name prefix.
*/
function table_pre($table_name)
{
	$prefix = get_mysql_settings('prefix');
	
	if ($prefix !== FALSE && strlen($prefix) > 0)
		return  $prefix . '_' . $table_name;
		
	return $table_name;
}

/**
* Check if $table is defined in the settings.
*/
function table_defined($table)
{
	if ($table === FALSE)
		return FALSE;
		
	$data = get_table_settings($table);
	
	if ($data === FALSE)
		return FALSE;
		
	return TRUE;
}

/**
* Check if $table is defined in the settings and
* exists in the database. 
*/
function table_exists($table)
{
	if (!table_defined($table))
		return FALSE;
	
	$db = connect_db();
	
	if ($db === FALSE)
		return FALSE;
		
	$exist = FALSE;
	$table = table_pre($table);
	$table = esc($db, $table);
	
	if ($result = mysqli_query($db, "SHOW TABLES LIKE '" . $table . "'"))
	{
		if (mysqli_num_rows($result) == 1)
			$exist = TRUE;
			
		mysqli_free_result($result);
	}
	
	mysqli_close($db);
	
	return $exist;
}

/**
* Quote string $value and apply database string escaping format
* to $value to prevent SQL injection. 
*/
function sql_val($db, $table_name, $field_name, $value)
{
	$value = esc($db, $value);	
	$fields = get_table_settings($table_name);
	
	if ($fields !== FALSE && isset($fields[$field_name]))
	{
		$field = strtolower($fields[$field_name]);
		
		if ($field == 'str' || $field == 'string')
			return "'" . $value . "'";
			
		if ($field == 'int'  || $field == 'integer' )
			return intval($value);
			
		if ($field == 'float')
			return floatval($value);

		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Table field type setting is unknown: ' . $table_name . '->' . $field_name . ' (' . $field . ')' );
	}
	else
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Table field setting is missing: ' . $table_name . '->' . $field_name);
	
	return $value;
}

/**
* Apply database string escaping format to $value to prevent SQL injection. 
*/
function clean($value, $type = 'int', $like = FALSE)
{
	if ($type == 'int')
		return intval($value); 
	elseif ($type == 'float')
		return floatval($value); 
	
	$db = connect_db();

	if ($db === FALSE)
		return FALSE;

	return esc($db, $value, $like);
}

/**
* Construct a proper equation string with database string escaping
* format to prevent SQL injection attack.
* If parameter $type is not 'int', 'float', or 'string' then
* it is representing $op and the actual $type is default to 'int'.
*/
function equ($name, $value, $type = 'int', $op = '=')
{
	if ($type != 'int' && $type != 'float' && $type != 'string')
	{
		// $type is used for $op
		$op = $type;
		$type = 'int'; // default
	}
	
	$db = connect_db();

	if ($db === FALSE)
		return FALSE;

	$lwrop = strtolower($op);
	$name = esc($db, $name);
	
	if ($type == 'int')
		$value = intval($value); 
	elseif ($type == 'float')
		$value = floatval($value); 
	else
	{
		if ($lwrop == 'like')
			$value = esc($db, $value, TRUE);
		else
			$value = esc($db, $value);
	}
	
	if ($type == 'string' || $lwrop == 'like')
		return "`$name` $op '$value'";
		
	return "`$name` $op $value"; 
}

/**
* Insert a record into a database table.
* Return
*   >= 0: record id
*     -1: system error
*     -2: database error
*/
function create($table, $fields)
{
	if (!table_defined($table))
		return -1;
	
	$db = connect_db();

	if ($db === FALSE)
		return -1;

	if (!is_array($fields))
		$fields = json_decode($fields, TRUE);
		
	$table = table_pre($table);
	$table = esc($db, $table);

	$index = 0;
	$colnames = '';
	$colvals = '';
	
	foreach ($fields as $colname => $colval)
	{
		if ($index > 0)
		{
			$colnames .= ',';
			$colvals .= ',';
		}
			
		$colname = esc($db, $colname);
		$colnames .= "`$colname`"; 
		
		$sval = sql_val($db, $table, $colname, $colval);
		
		if ($sval === FALSE)
			return -2;
			
		$colvals .= $sval;
			
		++$index;
	}
	
	$query = "INSERT INTO `$table` ($colnames) " . 
		"VALUES ($colvals);";

	global $harubi_do_log_sql_querystring;
	
	if (isset($harubi_do_log_sql_querystring) && $harubi_do_log_sql_querystring)
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'notice', 'Inserting a record into MySQL using query: ' . $query);
		
	$wait = 0;
	$id = -3;
	
	for ($t = 0; $t < 5; $t++) // With retrials
	{
		if ($wait > 0)
			sleep($wait); // delay
			
		++$wait; // incremental delay for the next retrial
		
		if (mysqli_query($db, $query) === TRUE)
		{
			$id = mysqli_insert_id($db);
			harubi_log(__FILE__,__FUNCTION__, __LINE__, 'notice', "Inserted a record with id = $id into MySQL using query:  $query");
			
			break;
		}
		else
			harubi_log(__FILE__,__FUNCTION__, __LINE__, 'warning', 'Tried to insert a record into MySQL but failed using query: ' . $query);
	}

	mysqli_close($db);

	if ($id == -3)
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Failed to insert a record into MySQL using query: ' . $query);

	return intval($id);	
}

/**
* Expands a short form 'where' with sugar coated 'id' field:
* - Numeric $where will be prepended with 'id='.
* - String $where started with an operator will be prepended with 'id'. 
* For both cases the value after the operator will be casted to integer
* to prevent SQL injection.
* 
* Otherwise, return $where as is.
* 
* @param mixed $where
* 
* @return proper 'where' or FALSE
*/
function where_id($where)
{
	$len = strlen($where);
	
	if ($where === FALSE || $len <= 0)
		return FALSE;
		
	if (in_array($where[0], array('=', '>', '<', '!')))
	{
		$eq = '';
		$val = '';
		
		for ($i = 0; $i < $len; $i++)
		{
			$chr = $where[$i];
			
			if (in_array($chr, array('=', '>', '<', '!')))
			{
				if (strlen($val) > 0)
					break;
					
				$eq .= $chr;				
			}
			else
				$val .= $chr;
		}
		
		if (strlen($val) <= 0 || strlen($eq) <= 0 
			|| !in_array($eq, array('=', '>', '<', '>=', '<=', '!=', '<>', '<=>')))
			return FALSE;
			
		$where = 'id' . $eq . intval($val);
	}
	elseif (is_numeric($where))
		$where = 'id=' . intval($where);
	
	return $where;
}

/**
* Read records from a database table.
* If the parameter $table is an array then all parameters including $table are defined in the array.
* Omitted parameters will be defined as FALSE.
*/
function read($table, $fields = FALSE, $where = FALSE, $order_by = FALSE, $sort = FALSE, $limit = FALSE, $offset = FALSE, $count = FALSE)
{
	if (is_array($table))
	{
		$params = $table;
		$table = FALSE;
		
		if (array_key_exists('table', $params))
			$table = $params['table'];
		
		if (array_key_exists('fields', $params))
			$fields = $params['fields'];
		
		if (array_key_exists('where', $params))
			$where = $params['where'];
		
		if (array_key_exists('order_by', $params))
			$order_by = $params['order_by'];
		
		if (array_key_exists('sort', $params))
			$sort = $params['sort'];
		
		if (array_key_exists('limit', $params))
			$limit = $params['limit'];
		
		if (array_key_exists('offset', $params))
			$offset = $params['offset'];
		
		if (array_key_exists('count', $params))
			$count = $params['count'];			
	}

	if (!table_defined($table))
		return FALSE;
	
	$db = connect_db();

	if ($db === FALSE)
		return FALSE;

	$table = table_pre($table);
	$table = esc($db, $table);
	
	if ($count)
		$query = "SELECT COUNT(*) AS `count` FROM ";
	else
	{
		if ($fields === FALSE)
			$cols = "*";
		else
		{
			if (is_array($fields))
			{
				$cols = "";
			
				foreach ($fields as $field)
				{
					if (strlen($cols) > 0)
						$cols .= ", ";
					
					$cols .= esc($db, $field);
				}
			}
			else
				$cols = "`" . esc($db, $fields) . "`";
		}
		
		$query = "SELECT " . $cols . " FROM ";
	}
	
	$query .= "`$table`";
	
	if ($where !== FALSE)
	{
		$where = where_id($where);
		$query .= " WHERE " . $where;
	}
	
	if ($order_by !== FALSE)
	{
		$order_by = esc($db, $order_by);
		$query .= " ORDER BY `$order_by`";
	}
	
	if ($sort !== FALSE && strlen($sort) > 0)
	{
		$sort = strtoupper($sort);
		
		if ($sort == "ASC" || $sort == "DESC")
			$query .= " " . $sort;
	}

	if ($limit !== FALSE)
	{
		$limit = intval($limit);
		
		if ($offset !== FALSE)
		{
			$offset = intval($offset);
			$query .= " LIMIT " . $offset . "," . $limit;
		}
		else
			$query .= " LIMIT " . $limit;
	}
	elseif ($offset !== FALSE)
	{
		$offset = intval($offset);
		$query .= " LIMIT " . $offset . ",18446744073709551615";
	}
		
	$query .= ";";

	global $harubi_do_log_sql_querystring;
	
	if (isset($harubi_do_log_sql_querystring) && $harubi_do_log_sql_querystring)
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'notice', 'Selecting a record from MySQL using query: ' . $query);
		
	$records = array();
	
	if ($result = mysqli_query($db, $query))
	{
		while ($row = mysqli_fetch_assoc($result))
		{
			$records[] = $row;
		}
			
		mysqli_free_result($result);
	}
	else
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Failed to select a record from MySQL using query: ' . $query);
		
	mysqli_close($db);
	
	return $records;
}

/**
* Update records on a database table.
*/
function update($table, $fields, $where)
{
	$db = connect_db();
	
	if ($db === FALSE)
		return FALSE;
		
	if (!is_array($fields))
		$fields = json_decode($fields, TRUE);
		
	$table = table_pre($table);
	$table = esc($db, $table);
	$where = where_id($where);

	$set = "";

	foreach ($fields as $colname => $colval)
	{
		if (strlen($set) > 0)
			$set .= ', ';
			
		$colname = esc($db, $colname);
		$set .= "`$colname` = " . sql_val($db, $table, $colname, $colval);
	}
	
	$query = "UPDATE `$table` SET $set WHERE $where;";
	
	global $harubi_do_log_sql_querystring;
	
	if (isset($harubi_do_log_sql_querystring) && $harubi_do_log_sql_querystring)
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'notice', 'Updating a record into MySQL using query: ' . $query);
		
	$status = FALSE;
	$wait = 0;
	
	for ($t = 0; $t < 5; $t++) // With retrials
	{
		if ($wait > 0)
			sleep($wait); // delay
			
		++$wait; // incremental delay for the next retrial
		
		if (mysqli_query($db, $query) === TRUE)
		{
			$status = TRUE;
			break;
		}
		else
			harubi_log(__FILE__,__FUNCTION__, __LINE__, 'warning', 'Tried to update a record in MySQL but failed using query: ' . $query);
	}

	mysqli_close($db);

	if (!$status)
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Failed to update a record in MySQL using query: ' . $query);

	return $status;	
}

/**
* Delete records from a database table.
*/
function delete($table, $where)
{
	$db = connect_db();
	
	if ($db === FALSE)
		return FALSE;
		
	$table = table_pre($table);
	$table = esc($db, $table);
	
	if ($where !== FALSE)
	{
		$where = where_id($where);	
		$query = "DELETE FROM `$table` WHERE $where;";
	}
	else
		$query = "DELETE FROM `$table`;";
	
	global $harubi_do_log_sql_querystring;
	
	if (isset($harubi_do_log_sql_querystring) && $harubi_do_log_sql_querystring)
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'notice', 'Deleting a record from MySQL using query: ' . $query);
		
	$status = FALSE;
	$wait = 0;
	
	for ($t = 0; $t < 5; $t++) // With retrials
	{
		if ($wait > 0)
			sleep($wait); // delay
			
		++$wait; // incremental delay for the next retrial
		
		if (mysqli_query($db, $query) === TRUE)
		{
			$status = TRUE;
			break;
		}
		else
			harubi_log(__FILE__,__FUNCTION__, __LINE__, 'warning', 'Tried to delete a record from MySQL but failed using query: ' . $query);
	}

	mysqli_close($db);

	if (!$status)
		harubi_log(__FILE__,__FUNCTION__, __LINE__, 'error', 'Failed to delete a record from MySQL using query: ' . $query);

	return $status;	
}

/**
* Inject a preset function to request routers (beat() and blow()).
* Prior to calling a controller, a router will invoke all presets
* with arguments $model, $action and $ctrl_args (controller arguments).
* A preset may alter $ctrl_args. A preset may return, and the return values
* will be taken as the response to the request, skipping the controller.
* There could be a chain of preset functions. Each may alter the $ctrl_args.
* And the last one may return.
*
* mixed preset_func(string $model, string $action, array &$ctrl_args)
*
*/
function preset($name, $func)
{
	global $harubi_presets;
	
	if (is_callable($func))
		$harubi_presets[$name] = $func;
}

function invoke_presets($model, $action, &$ctrl_args)
{
	global $harubi_presets;
	global $harubi_last_preset_intervened;
	global $harubi_do_log_presets;
	
	$harubi_last_preset_intervened = FALSE;
	
	if (!is_array($harubi_presets) || count($harubi_presets) <= 0)
		return;
	
	foreach ($harubi_presets as $preset_name => $preset_func)
	{
		if (is_callable($preset_func))
		{
			$preset = new ReflectionFunction($preset_func);
			$status = $preset->invokeArgs(array($model, $action, &$ctrl_args));
			
			if (isset($harubi_do_log_presets) && $harubi_do_log_presets)
				harubi_log(__FILE__,__FUNCTION__, __LINE__, 'notice', "Invoked preset '$preset_name' on model = '$model' and action = '$action'");
			
			if ($status !== NULL)
			{
				$harubi_last_preset_intervened = $preset_name;
				
				if (isset($harubi_do_log_presets) && $harubi_do_log_presets)
					harubi_log(__FILE__,__FUNCTION__, __LINE__, 'notice', "Preset '$preset_name' intervened on model = '$model' and action = '$action'");
				
				return $status;
			}
		}
	}
}

/**
* Inject a toll function to request routers (beat() and blow()).
* After calling a controller, the router will invoke all tolls
* with arguments $model, $action, $ctrl_args (controller arguments)
* and $ctrl_results (controller results). A toll may alter $ctrl_results.
* A toll may return, and the return values will be taken as the response
* to the request, ignoring the controller results. There could be a chain
* of toll functions. Each may alter the $ctrl_results. And the last one may
* return.
*
* mixed toll_func(string $model, string $action, array $ctrl_args, array &$ctrl_results)
*
*/
function toll($name, $func)
{
	global $harubi_tolls;
	
	if (is_callable($func))
		$harubi_tolls[$name] = $func;
}

function invoke_tolls($model, $action, $ctrl_args, &$ctrl_results)
{
	global $harubi_tolls;
	global $harubi_last_toll_intervened;
	global $harubi_do_log_tolls;
	
	$harubi_last_toll_intervened = FALSE;
	
	if (!is_array($harubi_tolls) || count($harubi_tolls) <= 0)
		return;
	
	foreach ($harubi_tolls as $toll_name => $toll_func)
	{
		if (is_callable($toll_func))
		{
			$toll = new ReflectionFunction($toll_func);
			$status = $toll->invokeArgs(array($model, $action, $ctrl_args, &$ctrl_results));
			
			if (isset($harubi_do_log_tolls) && $harubi_do_log_tolls)
				harubi_log(__FILE__,__FUNCTION__, __LINE__, 'notice', "Invoked toll '$toll_name' on model = '$model' and action = '$action'");
			
			if ($status !== NULL)
			{
				$harubi_last_toll_intervened = $toll_name;
				
				if (isset($harubi_do_log_tolls) && $harubi_do_log_tolls)
					harubi_log(__FILE__,__FUNCTION__, __LINE__, 'notice', "Toll '$toll_name' intervened on model = '$model' and action = '$action'");
				
				return $status;
			}
		}
	}
}

/**
* route() is called by beat() and blow().
* See descriptions on those functions.
*/
function route($model, $action, $controller, $use_q = FALSE)
{
	global $harubi_routing_done;

	if ($harubi_routing_done)
		return;
		
	if (!is_callable($controller))
		return;
	
	global $harubi_query;
	
	$ctrl = new ReflectionFunction($controller);
	$args = array();
	$i = 2; // after model and action
	
	foreach ($ctrl->getParameters() as $param)
	{
		$has_val = FALSE;
		
		if ($use_q)
		{
			if (is_array($harubi_query) && isset($harubi_query[$i]))
			{
				$args[] = $harubi_query[$i];
				$has_val = TRUE;
			}
		}
		elseif (isset($_REQUEST[$param->name]))
		{
			$args[] = $_REQUEST[$param->name];
			$has_val = TRUE;
		}
		
		if (!$has_val) {
			if ($param->isDefaultValueAvailable())
				$args[] = $param->getDefaultValue();
			else
				$args[] = NULL;
		}
		
		++$i;
	}

	$ret = invoke_presets($model, $action, $args);
	
	if ($ret === NULL)
	{
		$ret = $ctrl->invokeArgs($args);
		$tret = invoke_tolls($model, $action, $args, $ret);
		
		if ($tret !== NULL)
			$ret = $tret;
	}
	
	if (is_array($ret))
		$result = json_encode($ret);
	else
		$result = $ret;
	
	global $harubi_do_dump_logs;
	
	if (isset($harubi_do_dump_logs) && $harubi_do_dump_logs)
		dump_harubi_logs();

	echo $result;
	$harubi_routing_done = TRUE;
}

/**
* Pass query string `q` for model, action, and controller arguments.
* Format: 'q=model/action/controller-arg1/...'.
* Return an array of the passed arguments, or FALSE.
*/
function q($str = '')
{
	global $harubi_query;
	
	if (strlen($str) > 0)
		$harubi_query = explode("/", $str);	
	elseif (isset($_REQUEST['q']))
		$harubi_query = explode("/", $_REQUEST['q']);
	else
	{
		$harubi_query = [];
		return FALSE;
	}

	if (isset($harubi_query[0]))
		$model = trim($harubi_query[0]);
	else
		return FALSE;

	if (isset($harubi_query[1]))
		$action = trim($harubi_query[1]);
	else
		return FALSE;
		
	if (count($harubi_query) > 2)
		$ctrl_args = array_slice($harubi_query, 2);
	else
		$ctrl_args = [];
		
	return ['model' => $model, 'action' => $action, 'ctrl_args' => $ctrl_args];	
}

/**
* beat() passes a request to the $controller which will echo back the
* response string and cause other routing calls to be skipped. It will
* do nothing if the $model and $action do not match. If $model is NULL
* then it will take any value, so does $action.
*  
* Expecting request arguments 'model', 'action' and those
* matching with the $controller parameters.
* 
* The arguments will be taken from matching query string arguments or
* a form post arguments, or from the `q` argument which is url-rewriting
* friendly with the following format:
*   'q=model/action/controller-arg1/...'.
* The `q` argument will take precedence.
*
* The $controller will be invoked if both $model and $action matched
* with the request. Matching request arguments for the $controller
* will also be passed to the $controller. 
* 
* The $controller is expected to return with an assoc array
* which will then be converted to a json string as the response
* to the request. Or as-is if the return is not an array.
* 
* @param string $model
* @param string $action
* @param closure $controller
* 
* @return nothing or response with json data
*/
function beat($model, $action, $controller)  
{
	global $harubi_routing_done;

	if ($harubi_routing_done)
		return;
	
	$args = q(); // pass query string `q` which is url-rewriting friendly
	$use_q = FALSE;
	
	if ($model !== NULL)
	{
		if ($args !== FALSE && is_array($args))
		{
			if (!isset($args['model']) || $model != $args['model'])
				return;
				
			$use_q = TRUE;
		}
		elseif (!isset($_REQUEST['model']) || $model != $_REQUEST['model'])
			return;
	}
		
	if ($action !== NULL)
	{
		if ($use_q && $args !== FALSE && is_array($args))
		{
			if (!isset($args['action']) || $action != $args['action'])
				return;
		}
		elseif ($use_q || !isset($_REQUEST['action']) || $action != $_REQUEST['action'])
			return;
	}
	
	route($model, $action, $controller, $use_q);
}

/**
* blow() is similar to beat() except that it only takes $_REQUEST['q']
* instead of $_REQUEST['model'] and $_REQUEST['action'].
* The 'q' argument is expected to be a string with the following syntax:
* 'model/action/controller-arg1/...'.
*/
function blow($model, $action, $controller)
{
	global $harubi_routing_done;

	if ($harubi_routing_done)
		return;
		
	$args = q(); // pass query string `q` which is url-rewriting friendly
		
	if ($args === FALSE || !is_array($args))
		return;
		
	if ($model != NULL)
	{
		if (!isset($args['model']) || $model != $args['model'])
			return;
	}
		
	if ($action != NULL)
	{
		if (!isset($args['action']) || $action != $args['action'])
			return;
	}
	
	route($model, $action, $controller, TRUE);
}

/**
* Make a harubi internal request to a module through require().
* It will inject temporary request arguments into $_REQUEST
* and reinstate before exit. It will require() the module and
* capture the output buffer which is expected to contain the
* response string to the request.
*
* NOTE: The module MUST NOT define any function since the module
*       will be requested multiple times, which will cause the
*       function to be redefined which is not allowed in PHP.
*       Define any function in another module, and use require_once().
* 
* Params:
*	string $module	: PHP module/file name.
*   string $model	: model name.
*   string $action	: action name.
*	array $ctrl_args: controller arguments in the form of [$name => $value].
* Return:
*	The response string.
*/
function request($module, $model, $action, $ctrl_args = [], $apply_q = FALSE)
{
	ob_start();
	
	$xmodel = null;
	$xaction = null;
	$xq = null;
	
	if (isset($_REQUEST['model']))
		$xmodel = $_REQUEST['model'];
	
	if (isset($_REQUEST['action']))
		$xaction = $_REQUEST['action'];
	 
	$_REQUEST['model'] = $model;
	$_REQUEST['action'] = $action;
	
	if ($apply_q)
		$q = $model . '/' . $action;
	
	$xargs = [];
	
	foreach ($ctrl_args as $name => $val)
	{
		if (isset($_REQUEST[$name]))
			$xargs[$name] = $_REQUEST[$name];
		else
			$xargs[$name] = null;
	
		$_REQUEST[$name] = $val;
		
		if ($apply_q)
			$q .= '/' . $val; 
	}
	
	if ($apply_q)
	{
		if (isset($_REQUEST['q']))
			$xq = $_REQUEST['q'];
	
		$_REQUEST['q'] = $q;
	}
	
	global $harubi_routing_done;
	$xrd = $harubi_routing_done;
	$harubi_routing_done = FALSE;
	require $module;
	$harubi_routing_done = $xrd;
	
	$contents = ob_get_contents();
	ob_end_clean();
	
	$_REQUEST['model'] = $xmodel;
	$_REQUEST['action'] = $xaction;
	
	if ($apply_q)
		$_REQUEST['q'] = $xq;
	
	foreach ($xargs as $name => $val)
		$_REQUEST[$name] = $val;
		
	return $contents;
}

?>
