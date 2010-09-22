<?php
/*********************************************************************
*	Thanks Stephen
*********************************************************************/


/*********************************************************************
*  Constants
*********************************************************************/
define('OBJECT','OBJECT',true);
define('ARRAY_A','ARRAY_A',true);
define('ARRAY_N','ARRAY_N',true);


class db {
	
	/**********************************************************************
	*  Properties
	**********************************************************************/
	var $dbuser				= 'upp';
	var $dbpassword			= '';
	var $dbname				= 'upp';
	var $dbhost				= 'localhost';
	
	var $trace				= true;  // same as $debug_all
	var $debug_all			= true;  // same as $trace
	var $debug_echo_is_on	= true;
	var $show_errors		= true;
	var $debug_called		= true;
	var $vardump_called		= true;	
	var $last_error			= null;
	var $captured_errors	= array();
	
	var $num_queries		= 0;
	var $last_query			= null;
	var $col_info			= null;
	
	var $cache_dir			= false;
	var $cache_queries		= false;
	var $cache_inserts      = false;
	var $use_disk_cache		= false;
	var $cache_timeout		= 4; // hours

	var $error_labels = array
	(
		1 => '$dbuser and $dbpassword are required to connect to a database server',
		2 => '$dbname is required to select a database',
		3 => 'Error establishing database connection',
		4 => 'Database connection is not active',
		5 => 'Unexpected error while trying to select database'
	);
	
	
	/**********************************************************************
	*  Constructor - Allow quick set of connection properties 
	*  during instantiation
	**********************************************************************/
	function __construct($dbuser='', $dbpassword='', $dbname='', $dbhost='')
	{
		//use default connection params unless they are supplied
		if (!empty($dbuser) || !empty($dbpassword) || !empty($dbname) || !empty($dbhost))
		{
			$this->dbuser = $dbuser;
			$this->dbpassword = $dbpassword;
			$this->dbname = $dbname;
			$this->dbhost = $dbhost;
		}
	}


	/**********************************************************************
	*  Connect to database server and select a database
	**********************************************************************/
	function connect()
	{
		$return_val = false;
		
		// Must have a user and a password
		if (empty($this->dbuser) || empty($this->dbpassword))
		{
			$this->register_error($this->error_labels[1].' in '.__FILE__.' on line '.__LINE__);
			$this->show_errors ? trigger_error($this->error_labels[1],E_USER_WARNING) : null;
		}
		// Try to establish the server database handle
		else if (!$this->dbh = mysql_connect($this->dbhost,$this->dbuser,$this->dbpassword,true))
		{
			$this->register_error($this->error_labels[3].' in '.__FILE__.' on line '.__LINE__);
			$this->show_errors ? trigger_error($this->error_labels[3],E_USER_WARNING) : null;
		}
		// Connected.  Now select the database
		else
		{
			$return_val = true;
			
			!empty($this->dbname) ? $this->select($this->dbname) : null;
		}
		
		return $return_val;
	}


	
	/**********************************************************************
	*  Select a database
	**********************************************************************/
	function select()
	{
		$return_val = false;

		// Must have a database name
		if (empty($this->dbname))
		{
			$this->register_error($this->error_labels[3].' in '.__FILE__.' on line '.__LINE__);
			$this->show_errors ? trigger_error($this->error_labels[3],E_USER_WARNING) : null;
		}
		// Must have an active database connection
		else if (!$this->dbh)
		{
			$this->register_error($this->error_labels[4].' in '.__FILE__.' on line '.__LINE__);
			$this->show_errors ? trigger_error($this->error_labels[4],E_USER_WARNING) : null;
		}
		// Try to connect to the database
		else if (!mysql_select_db($this->dbname,$this->dbh))
		{
			// Try to get error supplied by mysql if not use our own
			if (!$str = mysql_error($this->dbh))
			{
				$str = $this->error_labels[5];
			}
			$this->register_error($str.' in '.__FILE__.' on line '.__LINE__);
			$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
		}
		else
		{
			$return_val = true;
		}

		return $return_val;
	}

	

	/**********************************************************************
	*  Perform SQL query and try to determine result value
	**********************************************************************/
	function query($query)
	{
		// Initialise return
		$return_val = 0;

		// Flush cached values
		$this->flush();

		// For reg expressions
		$query = trim($query);

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug
		$this->last_query = $query;

		// Count how many queries there have been
		$this->num_queries++;

		// Use cache
		if ($cache = $this->get_cache($query))
		{
			return $cache;
		}

		// If there is no existing database connection then try to connect
		if (!isset($this->dbh) || !$this->dbh)
		{
			$this->connect();
		}

		// Perform the query via std mysql_query function..
		$this->result = mysql_query($query,$this->dbh);

		// If there is an error then take note of it..
		if ($str = mysql_error($this->dbh))
		{
			$this->register_error($str);
			$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
			return false;
		}

		// Query was an insert, delete, update, replace
		$is_insert = false;
		if ( preg_match("/^(insert|delete|update|replace)\s+/i",$query) )
		{
			$this->rows_affected = mysql_affected_rows();

			// Take note of the insert_id
			if ( preg_match("/^(insert|replace)\s+/i",$query) )
			{
				$this->insert_id = mysql_insert_id($this->dbh);
			}

			// Return number of rows affected
			$return_val = $this->rows_affected;
		}
		// Query was a select
		else
		{
			// Take note of column info
			$i=0;
			while ($i < mysql_num_fields($this->result))
			{
				$this->col_info[$i] = mysql_fetch_field($this->result);
				$i++;
			}

			// Store Query Results
			$num_rows=0;
			while ($row = mysql_fetch_object($this->result))
			{
				// Store relults as an objects within main array
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			mysql_free_result($this->result);

			// Log number of rows the query returned
			$this->num_rows = $num_rows;

			// Return number of rows selected
			$return_val = $this->num_rows;
		}

		// disk caching of queries
		$this->store_cache($query,$is_insert);

		// If debug ALL queries
		$this->trace || $this->debug_all ? $this->debug() : null;

		return $return_val;

	}



	/**********************************************************************
	*  Return a single row
	*  @param int $y - row offset
	**********************************************************************/
	function get_row($query=null,$output=OBJECT,$y=0)
	{

		// Log how the function was called
		$this->func_call = "\$db->get_row(\"$query\",$output,$y)";

		// If there is a query then perform it if not then use cached results..
		if ( $query )
		{
			$this->query($query);
		}

		// If the output is an object then return object using the row offset..
		if ( $output == OBJECT )
		{
			return $this->last_result[$y]?$this->last_result[$y]:null;
		}
		// If the output is an associative array then return row as such..
		elseif ( $output == ARRAY_A )
		{
			return $this->last_result[$y]?get_object_vars($this->last_result[$y]):null;
		}
		// If the output is an numerical array then return row as such..
		elseif ( $output == ARRAY_N )
		{
			return $this->last_result[$y]?array_values(get_object_vars($this->last_result[$y])):null;
		}
		// If invalid output type was specified..
		else
		{
			$str = "\$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N";			
			$this->register_error($str);
			$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
		}

	}



	/**********************************************************************
	*  Return the the query as a result set
	**********************************************************************/
	function get_results($query=null, $output = OBJECT)
	{

		// Log how the function was called
		$this->func_call = "\$db->get_results(\"$query\", $output)";

		// If there is a query then perform it if not then use cached results..
		if ($query)
		{
			$this->query($query);
		}

		// Send back array of objects. Each row is an object
		if ( $output == OBJECT )
		{
			return $this->last_result;
		}
		elseif ( $output == ARRAY_A || $output == ARRAY_N )
		{
			if ( $this->last_result )
			{
				$i=0;
				foreach( $this->last_result as $row )
				{

					$new_array[$i] = get_object_vars($row);

					if ( $output == ARRAY_N )
					{
						$new_array[$i] = array_values($new_array[$i]);
					}

					$i++;
				}

				return $new_array;
			}
			else
			{
				return null;
			}
		}
	}


	
	/**********************************************************************
	*  Function to get column meta data info pertaining to the last query
	**********************************************************************/
	function get_col_info($info_type="name",$col_offset=-1)
	{

		if ( $this->col_info )
		{
			if ( $col_offset == -1 )
			{
				$i=0;
				foreach($this->col_info as $col )
				{
					$new_array[$i] = $col->{$info_type};
					$i++;
				}
				return $new_array;
			}
			else
			{
				return $this->col_info[$col_offset]->{$info_type};
			}
		}
	}



	/**********************************************************************
	*  Kill cached query results
	**********************************************************************/
	function flush()
	{
		// Get rid of these
		$this->last_result = null;
		$this->col_info = null;
		$this->last_query = null;
		$this->from_disk_cache = false;
	}



	/**********************************************************************
	*  Cache the query
	**********************************************************************/
	function store_cache($query,$is_insert)
	{
		// The would be cache file for this query
		$cache_file = $this->cache_dir.'/'.md5($query);

		// disk caching of queries
		if ( $this->use_disk_cache && ( $this->cache_queries && ! $is_insert ) || ( $this->cache_inserts && $is_insert ))
		{
			if (!is_dir($this->cache_dir))
			{
				$this->register_error("Could not open cache dir: $this->cache_dir");
				$this->show_errors ? trigger_error("Could not open cache dir: $this->cache_dir",E_USER_WARNING) : null;
			}
			else
			{
				// Cache all result values
				$result_cache = array
				(
					'col_info' => $this->col_info,
					'last_result' => $this->last_result,
					'num_rows' => $this->num_rows,
					'return_value' => $this->num_rows,
				);
				error_log(serialize($result_cache), 3, $cache_file);
			}
		}

	}

	/**********************************************************************
	*  Get cached query
	**********************************************************************/
	function get_cache($query)
	{
		// The would be cache file for this query
		$cache_file = $this->cache_dir.'/'.md5($query);

		// Try to get previously cached version
		if ( $this->use_disk_cache && file_exists($cache_file) )
		{
			// Only use this cache file if less than 'cache_timeout' (hours)
			if ((time() - filemtime($cache_file)) > ($this->cache_timeout*3600))
			{
				unlink($cache_file);
			}
			else
			{
				$result_cache = unserialize(file_get_contents($cache_file));

				$this->col_info = $result_cache['col_info'];
				$this->last_result = $result_cache['last_result'];
				$this->num_rows = $result_cache['num_rows'];

				$this->from_disk_cache = true;

				// If debug ALL queries
				$this->trace || $this->debug_all ? $this->debug() : null ;

				return $result_cache['return_value'];
			}
		}

	}



	/**********************************************************************
	*  Register SQL/DB error
	**********************************************************************/
	function register_error($err_str)
	{
		// Keep track of last error
		$this->last_error = $err_str;

		// Capture all errors to an error array no matter what happens
		$this->captured_errors[] = array
		(
			'error_str' => $err_str,
			'query'     => $this->last_query
		);
	}



	/**********************************************************************
	*  Dumps the contents of any input variable to screen in a nicely
	*  formatted and easy to understand way - any data type
	**********************************************************************/
	function vardump($mixed='')
	{
		// Start outup buffering
		ob_start();

		echo "<p><table><tr><td bgcolor='ffffff'><blockquote><font color='000090'>";
		echo "<pre><font>";

		if (!$this->vardump_called)
		{
			echo "<font color='800080'><b>DAO: Variable Dump</b></font>\n\n";
		}

		$var_type = gettype ($mixed);
		print_r(($mixed?$mixed:"<font color='red'>No Value / False</font>"));
		echo "\n\n<b>Type:</b> " . ucfirst($var_type) . "\n";
		echo "<b>Last Query [$this->num_queries]:</b> ".($this->last_query?$this->last_query:"NULL")."\n";
		echo "<b>Last Function Call:</b> " . ($this->func_call?$this->func_call:"None")."\n";
		echo "<b>Last Rows Returned:</b> ".count($this->last_result)."\n";
		echo "</font></pre></font></blockquote></td></tr></table>";
		echo "\n<hr size='1' noshade color='dddddd'>";

		// Stop output buffering and capture debug HTML
		$html = ob_get_contents();
		ob_end_clean();

		// Only echo output if it is turned on
		if ( $this->debug_echo_is_on )
		{
			echo $html;
		}

		$this->vardump_called = true;

		return $html;

	}



	/**********************************************************************
	* Displays the last query string that was sent to the database and 
	* a table listing results (if there were any).
	**********************************************************************/
	function debug()
	{
		// Start output buffering
		ob_start();

		echo "<blockquote>";

		// Only show heading once
		if ( ! $this->debug_called )
		{
			echo "<font color='800080' size='2'><b>DAO: debug()</b></font><p>\n";
		}

		if ( $this->last_error )
		{
			echo "<font size='2' color='000099'><b>Last Error --</b> [<font color='000000'><b>$this->last_error</b></font>]<p>";
		}

		if ( $this->from_disk_cache )
		{
			echo "<font size='2' color='000099'><b>Results retrieved from disk cache</b></font><p>";
		}

		echo "<font size='2' color='000099'><b>Query</b> [$this->num_queries] <b>--</b> ";
		echo "[<font color='000000'><b>$this->last_query</b></font>]</font><p>";

			echo "<font size='2' color='000099'><b>Query Result..</b></font>";
			echo "<blockquote>";

		if ( $this->col_info )
		{

			// =====================================================
			// Results top rows
			echo "<table cellpadding='5' cellspacing='1' bgcolor='555555'>";
			echo "<tr bgcolor='eeeeee'><td nowrap valign='bottom'><font color='555599' size='2'><b>(row)</b></font></td>";


			for ( $i=0; $i < count($this->col_info); $i++ )
			{
				echo "<td nowrap align='left' valign='top'><font size='1' color='555599'>{$this->col_info[$i]->type} {$this->col_info[$i]->max_length}</font><br><span style='font-size: 10pt; font-weight: bold;'>{$this->col_info[$i]->name}</span></td>";
			}

			echo "</tr>";

		// ======================================================
		// print main results
		if ( $this->last_result )
		{

			$i=0;
			foreach ( $this->get_results(null,ARRAY_N) as $one_row )
			{
				$i++;
				echo "<tr bgcolor='ffffff'><td bgcolor='eeeeee' nowrap align='middle'><font size='2' color='555599'>$i</font></td>";

				foreach ( $one_row as $item )
				{
					echo "<td nowrap><font size='2'>$item</font></td>";
				}

				echo "</tr>";
			}

		} // if last result
		else
		{
			echo "<tr bgcolor='ffffff'><td colspan=".(count($this->col_info)+1)."><font size='2'>No Results</font></td></tr>";
		}

		echo "</table>";

		} // if col_info
		else
		{
			echo "<font size='2'>No Results</font>";
		}

		// Stop output buffering and capture debug HTML
		$html = ob_get_contents();
		ob_end_clean();

		// Only echo output if it is turned on
		if ( $this->debug_echo_is_on )
		{
			echo $html;
		}

		$this->debug_called = true;

		return $html;

	}



	/**********************************************************************
	*  Format a SQL string correctly for safe SQL insert
	**********************************************************************/
	function escape($str)
	{
		return mysql_escape_string(stripslashes($str));
	}



	/**********************************************************************
	*  Return mySQL specific system date syntax
	**********************************************************************/
	function sysdate()
	{
		return 'NOW()';
	}

}
?>