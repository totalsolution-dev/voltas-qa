<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include base class
if ( ! class_exists('Low_variables_base'))
{
	require_once(PATH_THIRD.'low_variables/base.low_variables.php');
}

/**
 * Low Variables Extension class
 *
 * @package        low_variables
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-variables
 * @copyright      Copyright (c) 2009-2019, Low
 */
class Low_variables_ext extends Low_variables_base {

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access      public
	 * @param       array
	 * @return      void
	 */
	public function __construct($settings = array())
	{
		// Call Base constructor
		parent::__construct();

		// Set the Settings object
		ee()->low_variables_settings->set($settings);

		// Assign current settings
		$this->settings = ee()->low_variables_settings->get();
	}

	// --------------------------------------------------------------------
	// HOOKS
	// --------------------------------------------------------------------

	/**
	 * Optionally sync vars from files
	 *
	 * @access     public
	 * @param      object
	 * @return     object
	 */
	public function sessions_end($SESS)
	{
		//  Do we have to sync files?
		// if ($this->settings['save_as_files'] == 'y')
		// {
		// 	// Only if we're displaying the site or the module in the CP
		// 	if (REQ == 'PAGE' || (REQ == 'CP' && ee()->uri->segment(4) == $this->package))
		// 	{
		// 		ee()->low_variables_sync->files();
		// 	}
		// }

		return $SESS;
	}

	/**
	 * Add early parsed variables to config->_global_vars() array
	 *
	 * @access     public
	 * @param      array
	 * @return     array
	 */
	public function template_fetch_template($row)
	{
		// -------------------------------------------
		// Get the latest version of $row
		// -------------------------------------------

		if (ee()->extensions->last_call !== FALSE)
		{
			$row = ee()->extensions->last_call;
		}

		// -------------------------------------------
		// Call add_vars method
		// -------------------------------------------

		if ($this->settings['register_globals'] != 'n')
		{
			$this->_add_vars();
		}

		// Play nice, return it
		return $row;
	}

	/**
	 * Add early parsed variables to config->_global_vars() array
	 *
	 * @access     private
	 * @return     void
	 */
	private function _add_vars()
	{
		// -------------------------------------
		//  Define static var to keep track of
		//  whether we've added vars already...
		// -------------------------------------

		static $added;

		// ...if so, just bail out
		if ($added) return;

		// -------------------------------------
		//  Initiate data array
		// -------------------------------------

		$early = array();

		// -------------------------------------
		//  Get global variables to parse early, ordered the way they're displayed in the CP
		// -------------------------------------

		$early = ee()->low_variables_variable_model->get_early();
		$early = low_flatten_results($early, 'variable_data', 'variable_name');

		// -------------------------------------
		//  Add variables to early parsed global vars
		// -------------------------------------

		if ($early)
		{
			ee()->config->_global_vars
				= ($this->settings['register_globals'] == 'y')
				? array_merge($early, ee()->config->_global_vars)
				: array_merge(ee()->config->_global_vars, $early);
		}

		// Remember that we've added the vars so we don't do it again
		$added = TRUE;
	}

	// --------------------------------------------------------------------

} // End Class low_variables_ext

/* End of file ext.low_variables.php */
