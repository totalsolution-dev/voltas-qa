<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package     ExpressionEngine
 * @subpackage  Extensions
 * @category    Bloqs
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2012, 2019 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/bloqs
 * @license
 *
 * Copyright (c) 2019. BoldMinded, LLC
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
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

class Bloqs_base
{
    //Addon specific
    protected $pkg = 'bloqs';
    protected $pkg_url;
    public $pkg_details;

    public $name;
    public $class_name;
    public $version;

    //Extension Settings
    public $settings;

    //Libraries
    public $pkg_libraries = array();

    //Models
    public $pkg_models = array();

    //Helpers
    public $pkg_helpers = array();

    public function __construct()
    {
        //initialize class variables
        $this->_initialize_class_vars();

        //set the pkg url for the addon
        $this->pkg_url = ee('CP/URL')->make('addons/settings/' . $this->pkg);

        //Load up the resources we need to work with
        ee()->load->model($this->pkg_models);
        ee()->load->library($this->pkg_libraries);
        ee()->load->helpers($this->pkg_helpers);
    }

    /**
     * @description - sets default values for class variables
     * @return void
     */
    public function _initialize_class_vars()
    {
        $this->pkg_details = ee('App')->get($this->pkg);

        $this->name = strtolower($this->pkg_details->getName());
        $this->version = $this->pkg_details->getVersion();
        $this->class_name = ucfirst($this->name);
    }

    /**
     * @param $action
     * @param $params
     *
     * @return string
     */
    public function make_cp_url($action = 'index', $params = array())
    {
        return ee('CP/URL')->make('addons/settings/' . $this->pkg . '/' . $action, $params)->compile();
    }
}
