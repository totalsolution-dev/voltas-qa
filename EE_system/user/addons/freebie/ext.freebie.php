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


class Freebie_ext
{

    /**
     * Required vars
     */
    public $name = FREEBIE_NAME;
    public $description = FREEBIE_DOCS;
    public $version = FREEBIE_VERSION;
    public $settings_exist = 'y';
    public $docs_url = FREEBIE_DOCS;

    /**
     * Settings
     */
    public $settings = array();
    public $settings_default = array(
        'to_ignore' => 'success|error|preview',
        'ignore_beyond' => '',
        'break_category' => 'no',
        'remove_numbers' => 'no',
        'always_parse' => '',
        'always_parse_pagination' => 'no'
    );

    public function settings()
    {
        $settings['to_ignore'] = array('t', null, $this->settings_default['to_ignore']);
        $settings['ignore_beyond'] = array('t', null, $this->settings_default['ignore_beyond']);
        $settings['break_category'] = array('r', array('yes' => 'yes', 'no' => 'no'), $this->settings_default['break_category']);
        $settings['remove_numbers'] = array('r', array('yes' => 'yes', 'no' => 'no'), $this->settings_default['remove_numbers']);
        $settings['always_parse_pagination'] = array('r', array('yes' => 'yes', 'no' => 'no'), $this->settings_default['always_parse_pagination']);
        $settings['always_parse'] = array('t', null, $this->settings_default['always_parse']);
        return $settings;
    }

    public function __construct($settings = array())
    {
        // get extension settings
        $this->settings = $settings;
    }

    /**
     * Extension constructor
     *   the unaltered URI is 'dirty' â€” it potentially has /segments/ that will break our site
     *   the final URI and segments will be 'clean' â€” EE will use them for routing, and all will be well
     */
    public function sessions_start ($session)
    {

        if ($this->should_execute()) {

            //log
           // ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.FREEBIE_NAME.' debug: Start freebie execution');

            // clear cache if necessary
            $this->clear_cache();

            // remove any url params
            $this->remove_and_store_params();

            /**
             * EE 2.0 relies on an internal array of segments for routing
             *        we'll be 'cleaning' our URI and producing shiny new segments from it,
             *        but we still want to have access to the 'dirty' segments as {segment_1}, etc.
             */
            $this->set_dirty_segments_as_global_vars();

            // prep the user settings to use for cleaning the URI
            $this->settings['to_ignore'] = $this->parse_settings($this->settings['to_ignore']);
            $this->settings['ignore_beyond'] = $this->parse_settings($this->settings['ignore_beyond']);
            ee()->config->_global_vars['freebie_debug_settings_to_ignore'] = $this->settings['to_ignore'];
            ee()->config->_global_vars['freebie_debug_settings_ignore_beyond'] = $this->settings['ignore_beyond'];
            ee()->config->_global_vars['freebie_debug_settings_break_category'] = $this->settings['break_category'];
            ee()->config->_global_vars['freebie_debug_settings_remove_numbers'] = $this->settings['remove_numbers'];
            ee()->config->_global_vars['freebie_debug_settings_always_parse'] = $this->settings['always_parse'];
            ee()->config->_global_vars['freebie_debug_settings_always_parse_pagination'] = $this->settings['always_parse_pagination'];

            // if category breaking is on, retrieve the category url indicator and set it as a break segment
            $this->break_on_category_indicator();

            // determine which segments to ALWAYS parse, and to always parse beyond
            $this->get_always_parse();

            // remove the 'dirty' bits from the URI, which a user has specified in the settings
            $this->clean_uri();

            // re-fill the segment arrays from our new, clean URI
            $this->that_was_a_freebie();

            // re-execute the routing based on clean segments
           // ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.FREEBIE_NAME.' debug: Start parsing the routes agains with the new segments');
            $RTR =& load_class('Router', 'core');
            $RTR->_parse_routes();

            // re-indexing segments (moving 0 to 1, 1 to 2, etc) is required after routing
           // ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.FREEBIE_NAME.' debug: re-indexing segments (moving 0 to 1, 1 to 2, etc)');
            ee()->uri->_reindex_segments();

            // re-add params to url
            $this->restore_params();
        }
    }

    /**
     * Allow the final url from freebe to be the canonical_url of seolite
     * @param $return_data
     * @param $vars
     * @param $tag_prefix
     * @param $tagparams
     * @param $obj
     * @return mixed
     */
    public function seo_lite_template($return_data, $vars, $tag_prefix, $tagparams, $obj)
    {
        $vars['canonical_url'] =  ee()->config->_global_vars['freebie_original_uri'];

        return $return_data;
    }

    /**
     * check to see if the conditions are in place to run freebie
     */
    public function should_execute()
    {
        // is a URI? (lame test for checking to see if we're viewing the CP or not)
        return isset(ee()->uri->uri_string) &&
            substr(ee()->uri->uri_string, 0) != '?' &&

            // Freebie actually executes twice - but the second time,
            // the "settings" object isn't an array, which breaks it.
            // (No idea why). Checking type fixes this.
            gettype($this->settings) == 'array' &&

            // it should be a page or action to control
            (REQ == 'PAGE' || REQ == 'ACTION');
    }

    /**
     * Remove any variables from the segments
     */
    public function remove_and_store_params()
    {
       // ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.FREEBIE_NAME.' debug: Remove and store current segments');

        // Store URI for debugging
        ee()->config->_global_vars['freebie_original_uri'] = ee()->uri->uri_string;

        $this->param_pattern = '#(';    // begin match group
        $this->param_pattern .= '\?';    // match a '?';
        $this->param_pattern .= '|';   // OR
        $this->param_pattern .= '\&';    // match a '?';
        $this->param_pattern .= ')';    // end match group
        $this->param_pattern .= '.*$';   // continue matching characters until end of string
        $this->param_pattern .= '#';    // end match

        $matches = Array();
        preg_match($this->param_pattern, ee()->uri->uri_string, $matches);
        $this->url_params = (isset($matches[0])) ? $matches[0] : '';
        ee()->uri->uri_string = preg_replace($this->param_pattern, '', ee()->uri->uri_string);

        // Store stripped URI for debugging
        ee()->config->_global_vars['freebie_stripped_uri'] = ee()->uri->uri_string;
    }

    /**
     * Clear the cache on the first (uncached) pageload since saving
     */
    public function clear_cache()
    {
        $results = ee()->db->query("SELECT * FROM exp_extensions WHERE class='Freebie_ext'");
        $db_settings = array();

        if ($results->num_rows() > 0) {
            foreach ($results->result_array() as $row) {
                $db_settings = (unserialize($row['settings']));
            }
        }

        if (!isset($db_settings['cache_cleared'])) {

           // ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.FREEBIE_NAME.' debug: Clear cache if needed');

            // clear the DB cache
            ee()->functions->clear_caching('db');

            // add 'cache_cleared' to the settings
            $db_settings['cache_cleared'] = 'yes';
            $data = array('settings' => serialize($db_settings));

            $sql = ee()->db->update_string('extensions', $data, "class = 'Freebie_ext'");
            ee()->db->query($sql);

        }

    }

    /**
     * convert the original segments from the URI to {segment_n}-type global variables
     */
    public function set_dirty_segments_as_global_vars()
    {
       // ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.FREEBIE_NAME.' debug: Store the old segments');

        $segments = ee()->uri->segments;
        $this->store_last_segment($segments);
        $segments = array_pad($segments, 10, '');
        for ($i = 1; $i <= count($segments); $i++) {
            $segment = $segments[$i - 1];
            $segment = $this->strip_params_from_segment($segment);
            ee()->config->_global_vars['freebie_' . $i] = $segment;
        }

        // Store original segments for debugging
        ee()->config->_global_vars['freebie_debug_segments'] = implode('+', ee()->uri->segments);

    }

    /**
     * remove any parameters from a segment
     */
    public function strip_params_from_segment($segment = '')
    {

        $segment = preg_replace($this->param_pattern, '', $segment);
        return $segment;

    }

    /**
     * translate user settings to stuff we can use in the code
     */
    public function parse_settings($original_str)
    {

        // convert newline- and space-delimited settings to pipe-delimited ones
        $str = preg_replace('/(\n| )/', '|', $original_str, -1);

        // escapes parentheses
        $str = preg_replace('/\(/', '\(', $str, -1);
        $str = preg_replace('/\)/', '\)', $str, -1);

        // turn *s into true regex wildcards
        return preg_replace('/\*/', '.*?', $str, -1);

    }

    /**
     * add the category url indicator to the "break" array
     */
    public function break_on_category_indicator()
    {

        // did user set 'break category' to 'yes'?
        $break_category = isset($this->settings['break_category']) &&
            $this->settings['break_category'] == 'yes' &&
            ee()->config->config['use_category_name'] == 'y';

        if ($break_category) {
           // ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.FREEBIE_NAME.' debug: break on category indicator');
            $this->settings['to_ignore'] .= '|' . ee()->config->config['reserved_category_word'];
            $this->settings['ignore_beyond'] .= '|' . ee()->config->config['reserved_category_word'];
        }

    }

    /**
     * preserve the last segment
     */
    public function store_last_segment($segments)
    {
        ee()->config->_global_vars['freebie_last'] = isset($segments[count($segments)]) ? $segments[count($segments)] : '';
    }

    /**
     * get specific segments that we ALWAYS want to parse, and to parse beyond
     */
    public function get_always_parse()
    {

        if ($this->settings['always_parse_pagination'] == 'yes') {

           // ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.FREEBIE_NAME.' debug: Always parse paging');

            $dirty_array = explode('/', ee()->uri->uri_string);
            $clean_array = array();

            foreach ($dirty_array as $segment) {
                if (preg_match("#^P(\d+)$#", $segment)) {
                    $this->settings['always_parse'] .= '|' . $segment;
                }
            }
        }

        $this->settings['always_parse'] .= '|' . $this->parse_settings(ee()->config->config['profile_trigger']);

    }

    /**
     * remove segments, based on the user's settings
     */
    public function clean_uri()
    {
       // ee()->TMPL->log_item('&nbsp;&nbsp;***&nbsp;&nbsp;'.FREEBIE_NAME.' debug: Clean the url');

        // make an array full of "original" segments,
        // and a blank array to move the good ones too
        $dirty_array = explode('/', ee()->uri->uri_string);
        $clean_array = array();

        // did user set 'remove numbers' to 'yes'?
        $remove_numbers = isset($this->settings['remove_numbers']) &&
            $this->settings['remove_numbers'] == 'yes';

        $break = false;
        $parse_all_remaining = false;
        $count = 0;

        // move any segments that don't match patterns to clean array
        foreach ($dirty_array as $segment) {

            $is_not_a_always_parse_segment =
                preg_match('#^(' . $this->settings['always_parse'] . ')$#', $segment) == false;

            if ($is_not_a_always_parse_segment && $parse_all_remaining == false) {

                $should_be_ignored = preg_match('#^(' . $this->settings['to_ignore'] . ')$#', $segment) == false;

                if ($should_be_ignored && $break == false) {

                    // if this segment isn't killed by the "no numbers" setting,
                    // move it to the new array
                    if (!$remove_numbers || !is_numeric($segment)) {
                        array_push($clean_array, $segment);
                    }

                }

                // if this segment is one of the breakers, stop looping
                if (preg_match('#^(' . $this->settings['ignore_beyond'] . ')$#', $segment)) {
                    $break = true;
                    $this->set_remaining_segments_as_postbreaks($count, $dirty_array);
                }

            } else {

                array_push($clean_array, $segment);
                $parse_all_remaining = true;

            }

            $count++;

        }

        if (count($clean_array) != 0) {
            ee()->uri->uri_string = implode('/', $clean_array);
        } else {
            ee()->uri->uri_string = '';
        }

        // Store 'cleaned' uri_string for debugging
        ee()->config->_global_vars['freebie_debug_uri_cleaned'] = ee()->uri->uri_string;

    }

    /**
     * Sets all segments after a break as postbreak segments
     */
    public function set_remaining_segments_as_postbreaks($count, $segments)
    {

        $segments = array_slice($segments, $count + 1);
        $segments = array_pad($segments, 10, '');

        for ($i = 1; $i <= count($segments); $i++) {
            $segment = $segments[$i - 1];
            $segment = $this->strip_params_from_segment($segment);
            ee()->config->_global_vars['freebie_break_' . $i] = $segment;
        }

    }

    /**
     * Unset existing internal segment arrays,
     *   fetch new ones from the clean URI
     */
    public function that_was_a_freebie()
    {
        ee()->uri->segments = array();
        ee()->uri->rsegments = array();
        ee()->uri->_explode_segments();
    }

    /**
     * Re-add params to the uri
     */
    public function restore_params()
    {
        ee()->uri->uri_string .= $this->url_params;
        ee()->config->_global_vars['freebie_final_uri'] = ee()->uri->uri_string;
    }

    /**
     * Activate Extension
     */
    public function activate_extension()
    {

        $data = array(
            'class' => 'Freebie_ext',
            'hook' => 'sessions_start',
            'method' => 'sessions_start',
            'settings' => serialize($this->settings_default),
            'priority' => 10,
            'version' => $this->version,
            'enabled' => 'y'
        );

        // insert in database
        ee()->db->insert('extensions', $data);

         $data = array(
            'class' => 'Freebie_ext',
            'hook' => 'seo_lite_template',
            'method' => 'seo_lite_template',
            'settings' => serialize($this->settings_default),
            'priority' => 10,
            'version' => $this->version,
            'enabled' => 'y'
        );

        // insert in database
        ee()->db->insert('extensions', $data);

    }

    /**
     * Update Extension
     * @param string $current
     * @return bool
     */
    public function update_extension($current = '')
    {
        if ($current == '' OR $current == $this->version)
        {
            return FALSE;
        }

        $this->disable_extension();

        $this->activate_extension();

        ee()->db->update(
            'extensions',
            array('settings' => serialize($this->settings)),
            array('class' => 'Freebie_ext')
        );
    }

    /**
     * Delete extension
     */
    public function disable_extension()
    {
        ee()->functions->clear_caching('db');
        ee()->db->where('class', 'Freebie_ext');
        ee()->db->delete('extensions');
    }

}
