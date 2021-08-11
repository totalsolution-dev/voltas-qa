<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include base class
if ( ! class_exists('Low_variables_base'))
{
	require_once(PATH_THIRD.'low_variables/base.low_variables.php');
}

/**
 * Low Variables Module Class
 *
 * Class to be used in templates
 *
 * @package        low_variables
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-variables
 * @copyright      Copyright (c) 2009-2019, Low
 */

class Low_variables extends Low_variables_base {

	// --------------------------------------------------------------------
	//  PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Variables placeholder
	 *
	 * @access     private
	 * @var        array
	 */
	private $vars = array();

	// --------------------------------------------------------------------
	//  METHODS
	// --------------------------------------------------------------------

	/**
	 * Parse global template variables, alias for single use
	 *
	 * @access     public
	 * @return     string
	 * @see        parse()
	 */
	public function single()
	{
		return $this->parse('');
	}

	/**
	 * Parse global template variables, alias for pair use
	 *
	 * @access     public
	 * @return     string
	 * @see        parse()
	 */
	public function pair()
	{
		return $this->parse(ee()->TMPL->tagdata);
	}

	/**
	 * Parse global template variables, call type class if necessary
	 *
	 * @access     public
	 * @param      string
	 * @return     string
	 */
	public function parse($tagdata = NULL)
	{
		// -------------------------------------
		//  Set tagdata
		// -------------------------------------

		if (is_null($tagdata)) $tagdata = ee()->TMPL->tagdata;

		// -------------------------------------
		//  Get site id and var name from var param
		// -------------------------------------

		list($var, $site_id) = $this->get_var_param();

		// -------------------------------------
		//  Store vars in $this->vars
		// -------------------------------------

		$this->get_vars($site_id);
		$this->get_types();

		// -------------------------------------
		//  Parse variables inside tagdata if no (valid) var is given
		// -------------------------------------

		if (empty($var))
		{
			$this->_log('Replacing all variables inside tag pair with their data');

			// Initiate data array
			$data = array();

			// Loop through each of the vars and fill data array
			foreach ($this->vars as $key => $row)
			{
				// {my_var} {my_var:data} and {my_var:label}
				$data[$key] = $data[$key.':data'] = $row['variable_data'];
				$data[$key.':label'] = $row['variable_label'];
			}

			// Parse vars based on data array
			$it = ee()->TMPL->parse_variables_row($tagdata, $data);
		}
		elseif (array_key_exists($var, $this->vars))
		{
			//  We have a single var. Focus on it. Get object from it.
			$row = $this->vars[$var];
			$obj = ee()->low_variables_types->get($row);

			$this->_log('Generating output for '.$var);

			// Call display output
			$it = $obj->replace_tag($tagdata);
		}
		else
		{
			$this->_log("Var {$var} not found -- returning no results");
			$it = ee()->TMPL->no_results();
		}

		// Please
		return $it;
	}

	/**
	 * Return the label for a given var
	 *
	 * Usage: {exp:low_variables:label var="my_variable_name"}
	 *
	 * @access     public
	 * @return     string
	 */
	public function label()
	{
		// -------------------------------------
		//  Get site id and var name from var param
		// -------------------------------------

		list($var, $site_id) = $this->get_var_param();

		// -------------------------------------
		//  Store vars in $this->vars
		// -------------------------------------

		$this->get_vars($site_id);

		// -------------------------------------
		//  Return the label, if present
		// -------------------------------------

		return isset($this->vars[$var]) ? $this->vars[$var]['variable_label'] : '';
	}

	/**
	 * Fetch and return all options from var settings
	 *
	 * @access     public
	 * @return     string
	 */
	public function options()
	{
		// -------------------------------------
		//  Get site id and var name from var param
		// -------------------------------------

		list($var, $site_id) = $this->get_var_param();

		// -------------------------------------
		//  Store vars in $this->vars
		// -------------------------------------

		$this->get_vars($site_id);
		$this->get_types();

		// -------------------------------------
		//  Get parameter
		// -------------------------------------

		if ( ! $var || ! isset($this->vars[$var]))
		{
			$this->_log('No valid var-parameter found, returning no results');
			return ee()->TMPL->no_results();
		}

		// -------------------------------------
		//  Focus on given var, get object from it
		// -------------------------------------

		$row = $this->vars[$var];
		$obj = ee()->low_variables_types->get($row);

		// -------------------------------------
		//  Get the options from its settings
		// -------------------------------------

		$options = $obj->settings('options', FALSE);

		// -------------------------------------
		//  If given var is a fieldtype or no options exist, then don't bother
		// -------------------------------------

		if ($options === FALSE)
		{
			$this->_log("Variable {$var} doesn't support the Options tag");
			return ee()->TMPL->no_results();
		}

		// -------------------------------------
		//  No options? Bail out
		// -------------------------------------

		if ( ! ($options = LVUI::choices($options)))
		{
			$this->_log('No options found, returning no results');
			return ee()->TMPL->no_results();
		}

		// -------------------------------------
		//  Get active items
		// -------------------------------------

		$active = ($sep = $obj->settings('separator'))
			? LVUI::explode($sep, $row['variable_data'])
			: array($row['variable_data']);

		// -------------------------------------
		//  Initiate vars
		// -------------------------------------

		$data  = array();
		$total = count($options);
		$i     = 0;

		// loop through options, populate variables array
		foreach ($options as $key => $val)
		{
			$data[] = array(
				$var.':data'          => $key,
				$var.':label'         => $row['variable_label'],
				$var.':data_label'    => $val,
				$var.':count'         => ++$i,
				$var.':total_results' => $total,

				'active'   => (in_array($key, $active) ? 'y' : ''),
				'checked'  => (in_array($key, $active) ? ' checked="checked"' : ''),
				'selected' => (in_array($key, $active) ? ' selected="selected"' : '')
			);
		}

		// Parse template
		return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $data);
	}

	// --------------------------------------------------------------------
	//  ACT METHODS
	// --------------------------------------------------------------------

	/**
	 * Sync vars by ACT
	 *
	 * @access     public
	 * @return     void
	 */
	public function sync()
	{
		// Only ACTions are allowed
		if (REQ != 'ACTION') return;

		// Get the settings
		$key1 = ee()->low_variables_settings->get('license_key');
		$key2 = ee('Request')->get('key');

		// Nope
		if ($key1 !== $key2)
		{
			show_error('Action not allowed');
		}

		// Or else, sync it
		// ee()->low_variables_sync->files();

		// Clear cache too?
		if (ee('Request')->get('clear_cache') == 'yes')
		{
			ee()->functions->clear_caching('all', '', TRUE);
		}
	}

	// --------------------------------------------------------------------
	//  PRIVATE METHODS
	// --------------------------------------------------------------------

	/**
	 * Get the site id and cleaned var from a var="" parameter value
	 *
	 * @access     private
	 * @return     array
	 */
	private function get_var_param()
	{
		// -------------------------------------
		//  Get the var parameter value
		// -------------------------------------

		$var = ee()->TMPL->fetch_param('var', FALSE);

		// -------------------------------------
		//  Default site id to current site id
		// -------------------------------------

		$site_id = $this->site_id;

		// -------------------------------------
		//  Get site id based on site_name:var_name value
		// -------------------------------------

		if ( ! empty($var) && ($pos = strpos($var, ':')) !== FALSE)
		{
			// Get the part before the :
			$prefix = substr($var, 0, $pos);

			// If MSM is enabled and prefix is a valid site name
			if (ee()->config->item('multiple_sites_enabled') == 'y' && in_array($prefix, ee()->TMPL->sites))
			{
				// Strip prefix from var name
				$var = substr($var, $pos + 1);

				// Get the correct site ID
				$site_id = array_search($prefix, ee()->TMPL->sites);

				// And make note of it in the log
				$this->_log("Found var {$var} in site {$prefix}");
			}
		}

		// -------------------------------------
		//  Return the site id and cleaned var name
		// ------------------------------------

		return array($var, $site_id);
	}

	/**
	 * Get variables for given site from cache or DB
	 *
	 * @access     private
	 * @param      int
	 * @return     array
	 */
	private function get_vars($site_id = FALSE)
	{
		// -------------------------------------
		//  Reset
		// -------------------------------------

		$this->vars = array();

		// -------------------------------------
		//  If no site id is given, use current
		// -------------------------------------

		if ($site_id == FALSE)
		{
			$site_id = $this->site_id;
		}

		// -------------------------------------
		//  Get cached vars
		// -------------------------------------

		$var_cache = low_get_cache($this->package, 'vars');

		if (isset($var_cache[$site_id]))
		{
			$this->_log('Getting variables from Session Cache');

			$this->vars = $var_cache[$site_id];
		}
		else
		{
			$this->_log('Getting variables from Database');

			// Get vars for the site
			$this->vars = ee()->low_variables_variable_model->get_by_site($site_id);
			$this->vars = low_associate_results($this->vars, 'variable_name');

			// Register to cache
			$var_cache[$site_id] = $this->vars;

			low_set_cache($this->package, 'vars', $var_cache);
		}

		return $this->vars;
	}

	/**
	 * Get variables types from cache or settings
	 *
	 * @access     private
	 * @return     array
	 */
	private function get_types()
	{
		static $types;

		if ( ! $types)
		{
			$types = ee()->low_variables_types->load_enabled();
		}

		return $types;
	}

	// --------------------------------------------------------------------

	/**
	 * Log template item
	 */
	private function _log($msg)
	{
		ee()->TMPL->log_item('Low Variables: '.$msg);
	}

} // End class

/* End of file mod.low_variables.php */