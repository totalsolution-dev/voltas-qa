<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		http://addons.reinos.nl
 * @copyright 	Copyright (c) 2019 Reinos.nl Internet Media
 *
 * Copyright (c) 2018. Reinos.nl Internet Media
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Rein de Vries and
 * Reinos.nl Internet Media) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

/**
 * Include the config file
 */
require_once PATH_THIRD . 'freebie/config.php';
$plugin_info = array(
	'pi_name' => FREEBIE_NAME,
	'pi_version' => FREEBIE_VERSION,
	'pi_author' => FREEBIE_AUTHOR,
	'pi_author_url' => FREEBIE_DOCS,
	'pi_description' => FREEBIE_DESCRIPTION,
	'pi_usage' => Freebie::usage()
);

/**
 * Freebie
 *
 * @package        Freebie
 * @author        Rein de Vries <info@reinos.nl
 * @license  http://www.gnu.org/licenses/gpl-3.0.html
 */
class Freebie
{

	function __construct()
	{

	}

	function any()
	{
		$match = 'false';
		$name = ee()->TMPL->fetch_param('name');

		for ($i = 1; $i <= 11; $i++) {
			if (isset(ee()->config->_global_vars['freebie_' . $i])) {
				if (ee()->config->_global_vars['freebie_' . $i] == $name) {
					$match = 'true';
				}
			}
		}

		return $match;
	}

	function is_number()
	{
		$match = 'false';
		$i = ee()->TMPL->fetch_param('segment');
		$freebie_seg = ee()->config->_global_vars['freebie_' . $i];

		if (is_numeric($freebie_seg)) {
			$match = 'true';
		}

		return $match;
	}

	function category_match($cat_key)
	{
		$match = '';
		$segment = ee()->TMPL->fetch_param('segment');
		$group_id = ee()->TMPL->fetch_param('group_id');
		$site_id = ee()->TMPL->fetch_param('site_id');
		$category_url = ee()->config->_global_vars['freebie_' . $segment];
		$query_string = "SELECT cat_id, cat_name, cat_description, cat_image FROM exp_categories WHERE cat_url_title = ?";
		$values = array($category_url);
		if ($group_id != '') {
			$query_string .= " AND group_id = ?";
			$values[] = $group_id;
		}
		if ($site_id != '') {
			$query_string .= " AND site_id = ?";
			$values[] = $site_id;
		}

		$query = ee()->db->query($query_string, $values);

		foreach ($query->result_array() as $row) {
			$match = $row[$cat_key];
		}

		return $match;
	}

	function category_name()
	{
		return $this->category_match('cat_name');
	}

	function category_id()
	{
		return $this->category_match('cat_id');
	}

	function category_description()
	{
		return $this->category_match('cat_description');
	}

	function category_image()
	{
		return $this->category_match('cat_image');
	}

	function debug()
	{
		if (isset(ee()->config->_global_vars['freebie_debug_settings_to_ignore'])) {
			echo('<br />To ignore: ' .
				ee()->config->_global_vars['freebie_debug_settings_to_ignore']);
		}
		if (isset(ee()->config->_global_vars['freebie_debug_settings_ignore_beyond'])) {
			echo('<br />Ignore beyond: ' .
				ee()->config->_global_vars['freebie_debug_settings_ignore_beyond']);
		}
		if (isset(ee()->config->_global_vars['freebie_debug_settings_break_category'])) {
			echo('<br />Break category: ' .
				ee()->config->_global_vars['freebie_debug_settings_break_category']);
		}
		if (isset(ee()->config->_global_vars['freebie_debug_settings_remove_numbers'])) {
			echo('<br />Remove numbers: ' .
				ee()->config->_global_vars['freebie_debug_settings_remove_numbers']);
		}
		if (isset(ee()->config->_global_vars['freebie_debug_settings_always_parse'])) {
			echo('<br />Always parse: ' .
				ee()->config->_global_vars['freebie_debug_settings_always_parse']);
		}
		if (isset(ee()->config->_global_vars['freebie_debug_uri'])) {
			echo('<br />URI: ' .
				ee()->config->_global_vars['freebie_debug_uri']);
		}
		if (isset(ee()->config->_global_vars['freebie_debug_uri_stripped'])) {
			echo('<br />URI stripped: ' .
				ee()->config->_global_vars['freebie_debug_uri_stripped']);
		}
		if (isset(ee()->config->_global_vars['freebie_debug_segments'])) {
			echo('<br />Segments: ' .
				ee()->config->_global_vars['freebie_debug_segments']);
		}
		if (isset(ee()->config->_global_vars['freebie_debug_uri_cleaned'])) {
			echo('<br />URI cleaned: ' .
				ee()->config->_global_vars['freebie_debug_uri_cleaned']);
		}
	}

	/**
	 * Compares the freebie original uri to the pagination_url in EE pagination, and returns an updated
	 * url with "hidden" freebie segments
	 * @return string updated pagination url
	 */
	function adjust_pagination_url()
	{
		$pagination_url = ee()->TMPL->tagdata;

		if (isset(ee()->config->_global_vars["freebie_original_uri"])) {

			$freebie_url = ee()->config->_global_vars["freebie_original_uri"];

			$pagination_segments = explode("/", $pagination_url);
			$freebie_segments = explode("/", $freebie_url);

			// first, checking to see if our paginated_url segment has a pagination flag in the last segment
			if (substr($pagination_segments[count($pagination_segments) - 1], 0, 1) === "P") {

				// next, check to see if the freebie_url has a pagination flag -- if so, replace it -- otherwise concat.
				if (substr($freebie_segments[count($freebie_segments) - 1], 0, 1) === "P") {
					$freebie_segments[count($freebie_segments) - 1] = $pagination_segments[count($pagination_segments) - 1];
				} else {
					$freebie_segments[] = $pagination_segments[count($pagination_segments) - 1];
				}

				// lastly, re-stringify our newly adjusted url
				$adjusted_pagination_url = "/" . implode("/", $freebie_segments);

				return $adjusted_pagination_url;

			}

		}

		return $pagination_url;

	}


	// --------------------------------------------------------------------
	/**
	 * Usage
	 *
	 * This function describes how the plugin is used.
	 *
	 * @access    public
	 * @return    string
	 */
	public static function usage()
	{
		ob_start();
		?>

		See http://docs.reinos.nl/freebie

		<?php
		$buffer = ob_get_contents();

		ob_end_clean();

		return $buffer;
	}
	// END

}
/* End of file pi.freebie.php */

/* Location: ./system/expressionengine/third_party/freebie/pi.freebie.php */
