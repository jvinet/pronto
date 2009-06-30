<?php
/*
 * Project:     SafeSQL: db access library extension
 * File:        SafeSQL.class.php
 * Author:      Monte Ohrt <monte at newdigitalgroup dot com>
 *
 * Version:     2.2
 * Date:        March 27th, 2007
 * Copyright:   2001-2005 New Digital Group, Inc.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

class SafeSQL
{
	
	// values that determine dropping bracketed sections
	var $_drop_values = array('');
	
/*======================================================================*\
    Function: SafeSQL
    Purpose:  constructor
\*======================================================================*/
	function SafeSQL() { }

/*======================================================================*\
    Function: query
    Purpose:  process the query string
\*======================================================================*/
	function query($query_string, $query_vars)
	{		

		if(is_array($query_vars)) {
			
			$_var_count = count($query_vars);
			
			if($_var_count != preg_match_all('!%[sSiIfFcClLqQnN]!', $query_string, $_match)) {
				$this->_error_msg('unmatched number of vars and % placeholders: ' . $query_string);
			}
						
			// get string position for each element
			$_var_pos = array();
			$_curr_pos = 0;
			for( $_x = 0; $_x < $_var_count; $_x++ ) {
				$_var_pos[$_x] = strpos($query_string, $_match[0][$_x], $_curr_pos);
				$_curr_pos = $_var_pos[$_x] + 1;
			}
			// build query from passed in variables, escape them
            // start from end of query and work backwards so string
            // positions are not altered during replacement
            $_last_removed_pos = null;
            $_last_var_pos = null;
			for( $_x = $_var_count-1; $_x >= 0; $_x-- ) {
                if(isset($_last_removed_pos) && $_last_removed_pos < $_var_pos[$_x]) {
                    // already removed, skip
                    continue;
                }
				// escape string
				$query_vars[$_x] = $this->_sql_escape($query_vars[$_x]);
				if(in_array($_match[0][$_x], array('%S','%I','%F','%C','%L','%Q','%N'))) {
					// get positions of [ and ]
                    if(isset($_last_var_pos))
					    $_right_pos = strpos($query_string, ']', $_last_var_pos);
                    else
					    $_right_pos = strpos($query_string, ']', $_var_pos[$_x]);

                    // no way to get strpos from the right side starting in the middle
                    // of the string, so slice the first part out then find it
					$_str_slice = substr($query_string, 0, $_var_pos[$_x]);
					$_left_pos = strrpos($_str_slice, '[');
                    
					if($_right_pos === false || $_left_pos === false) {
						$this->_error_msg('missing or unmatched brackets: ' . $query_string);
					}
					if(in_array($query_vars[$_x], $this->_drop_values, true)) {
                        $_last_removed_pos = $_left_pos;
						// remove entire part of string
						$query_string = substr_replace($query_string, '', $_left_pos, $_right_pos - $_left_pos + 1);
                        $_last_var_pos = null;			
                    } else if ($_x > 0 && $_var_pos[$_x-1] > $_left_pos) {
                        // still variables left in brackets, leave them and just replace var
                        $_convert_var = $this->_convert_var($query_vars[$_x], $_match[0][$_x]);
						$query_string = substr_replace($query_string, $_convert_var, $_var_pos[$_x], 2);
                        $_last_var_pos = $_var_pos[$_x] + strlen($_convert_var);
					} else {
						// remove the brackets only, and replace %S
						$query_string = substr_replace($query_string, '', $_right_pos, 1);											
						$query_string = substr_replace($query_string, $this->_convert_var($query_vars[$_x], $_match[0][$_x]), $_var_pos[$_x], 2);
						$query_string = substr_replace($query_string, '', $_left_pos, 1);
                        $_last_var_pos = null;
					}
				} else {
					$query_string = substr_replace($query_string, $this->_convert_var($query_vars[$_x], $_match[0][$_x]), $_var_pos[$_x], 2);
				}
			}			
		}
		
		return $query_string;
						
	}
	
/*======================================================================*\
    Function: _convert_var
    Purpose:  convert a variable to the given type
		Input:    $var - the variable
			  $type - the type to convert to:
		  	%i, %I - cast to integer
				%f, %F - cast to float
				%c, %C - comma separate, cast each element to integer
				%l, %L - comma separate, no quotes, no casting
				%q, %Q - quote/comma separate
				%n, %N - wrap value in single quotes unless NULL
\*======================================================================*/
	function _convert_var($var, $type) {
		switch($type) {
			case '%i':
			case '%I':
				// cast to integer
				settype($var, 'integer');
				break;
			case '%f':
			case '%F':
				// cast to float
				settype($var, 'float');
				break;
			case '%c':
			case '%C':
				// comma separate
				settype($var, 'array');
				for($_x = 0 , $_y = count($var); $_x < $_y; $_x++) {
					// cast to integers
					settype($var[$_x], 'integer');
				}
				$var = implode(',', $var);
				if($var == '') {
					// force 0, keep syntax from breaking
					$var = '0';
				}
				break;
			case '%l':
			case '%L':
				// comma separate
				settype($var, 'array');
				$var = implode(',', $var);
				break;
			case '%q':
			case '%Q':
				settype($var, 'array');
				// quote comma separate
				$var = "'" . implode("','", $var) . "'";
				break;
            case '%n':
            case '%N':
                if($var != 'NULL')
                    $var = "'" . $var . "'";
                break;
		}
		return $var;
	}	

/*======================================================================*\
    Function: error
    Purpose:  handle error messages
\*======================================================================*/
	function _error_msg($error_msg) {
		trigger_error('SafeSQL: ' . $error_msg);	
	}

/*======================================================================*\
    Function: SetDropValues
    Purpose:  
\*======================================================================*/
	function set_drop_values($drop_values) {
		if(is_array($drop_values)) {
			$this->_drop_values = $drop_values;
		} else {
			$this->_error_msg('drop values must be an array');			
		}
	}

/*======================================================================*\
    Function: GetDropValues
    Purpose:  
\*======================================================================*/
	function get_drop_values() {
		return $this->_drop_values;
	}
				
		
/*======================================================================*\
    Function: _sql_escape
    Purpose:  method overridden by subclass
\*======================================================================*/
	function _sql_escape() { }
	
}
		
class SafeSQL_MySQL extends SafeSQL {
	
	var $_link_id;	
	
/*======================================================================*\
    Function: SafeSQL_MySQL
    Purpose:  constructor
\*======================================================================*/
	function SafeSQL_MySQL($link_id = null) {
		$this->_link_id = $link_id;
	}

/*======================================================================*\
    Function: _sql_escape
    Purpose:  recursively escape variables/arrays for SQL use
\*======================================================================*/
	function _sql_escape($var) {
		if(is_array($var)) {
			foreach($var as $_element) {
				$_newvar[] = $this->_sql_escape($_element);
			}
			return $_newvar;
		}
		if(function_exists('mysql_real_escape_string')) {
			if(!isset($this->_link_id)) {
				return mysql_real_escape_string($var);
			} else {
				return mysql_real_escape_string($var, $this->_link_id);
			}
		} else {
			return addslashes($var);
		}	
		break;
	}	
}

class SafeSQL_ANSI extends SafeSQL {
	
	
/*======================================================================*\
    Function: SafeSQL_ANSI
    Purpose:  constructor
\*======================================================================*/
	function SafeSQL_ANSI() { }

/*======================================================================*\
    Function: _sql_escape
    Purpose:  recursively escape variables/arrays for SQL use
\*======================================================================*/
	function _sql_escape($var) {
		if(is_array($var)) {
			foreach($var as $_element) {
				$_newvar[] = $this->_sql_escape($_element);
			}
			return $_newvar;
		}
		return str_replace("'", "''", $var);
		break;
	}	
}

?>
