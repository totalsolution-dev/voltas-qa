<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Low Variables helper functions
 *
 * @package        low_variables
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-variables
 * @copyright      Copyright (c) 2009-2019, Low
 */

// --------------------------------------------------------------------

/**
 * Is this EE4 (including dev previews)?
 */
if ( ! function_exists('ee4'))
{
	function ee4()
	{
		return version_compare(APP_VER, '3.9.0', '>');
	}
}

// --------------------------------------------------------------------

/**
 * Flatten results
 *
 * Given a DB result set, this will return an (associative) array
 * based on the keys given
 *
 * @param      array
 * @param      string    key of array to use as value
 * @param      string    key of array to use as key (optional)
 * @return     array
 */
if ( ! function_exists('low_flatten_results'))
{
	function low_flatten_results($resultset, $val, $key = FALSE)
	{
		$array = array();

		foreach ($resultset as $row)
		{
			if ($key !== FALSE)
			{
				$array[$row[$key]] = $row[$val];
			}
			else
			{
				$array[] = $row[$val];
			}
		}

		return $array;
	}
}

// --------------------------------------------------------------------

/**
 * Associate results
 *
 * Given a DB result set, this will return an (associative) array
 * based on the keys given
 *
 * @param      array
 * @param      string    key of array to use as key
 * @param      bool      sort by key or not
 * @return     array
 */
if ( ! function_exists('low_associate_results'))
{
	function low_associate_results($resultset, $key, $sort = FALSE)
	{
		$array = array();

		foreach ($resultset as $row)
		{
			if (array_key_exists($key, $row) && ! array_key_exists($row[$key], $array))
			{
				$array[$row[$key]] = $row;
			}
		}

		if ($sort === TRUE)
		{
			ksort($array);
		}

		return $array;
	}
}

// --------------------------------------------------------------

/**
 * Get cache value, either using the cache method (EE2.2+) or directly from cache array
 *
 * @param       string
 * @param       string
 * @return      mixed
 */
if ( ! function_exists('low_get_cache'))
{
	function low_get_cache($a, $b)
	{
		if (method_exists(ee()->session, 'cache'))
		{
			return ee()->session->cache($a, $b);
		}
		else
		{
			return (isset(ee()->session->cache[$a][$b]) ? ee()->session->cache[$a][$b] : FALSE);
		}
	}
}

// --------------------------------------------------------------

/**
 * Set cache value, either using the set_cache method (EE2.2+) or directly to cache array
 *
 * @param       string
 * @param       string
 * @param       mixed
 * @return      void
 */
if ( ! function_exists('low_set_cache'))
{
	function low_set_cache($a, $b, $c)
	{
		if (method_exists(ee()->session, 'set_cache'))
		{
			ee()->session->set_cache($a, $b, $c);
		}
		else
		{
			ee()->session->cache[$a][$b] = $c;
		}
	}
}

// --------------------------------------------------------------

/**
 * Debug
 *
 * @param       mixed
 * @param       bool
 * @return      void
 */
if ( ! function_exists('low_dump'))
{
	function low_dump($var, $exit = TRUE)
	{
		echo '<pre>'.htmlentities(print_r($var, TRUE)).'</pre>';
		if ($exit) exit;
	}
}

// --------------------------------------------------------------

/* End of file low_variables_helper.php */
