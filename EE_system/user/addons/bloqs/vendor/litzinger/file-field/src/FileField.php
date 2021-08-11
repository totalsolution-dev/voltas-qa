<?php

namespace Litzinger\FileField;

use File_ft;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ExpressionEngine FileField Class
 *
 * @package     ExpressionEngine
 * @subpackage  Libraries
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2016 - Brian Litzinger
 * @link        http://boldminded.com
 * @license
 *
 * Copyright (c) 2015. BoldMinded, LLC
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

// Load the EE files that we're using
require_once APPPATH.'fieldtypes/EE_Fieldtype.php';
require_once PATH_ADDONS.'file/ft.file.php';

class FileField
{
    /**
     * @var array
     */
    private $settings;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $fieldValue;

    /**
     * @param $fieldName
     * @param $fieldValue
     * @param array $settings
     */
    public function __construct($fieldName, $fieldValue, $settings = array())
    {
        $this->fieldName = $fieldName;
        $this->fieldValue = $fieldValue;
        $this->settings = $this->configureOptions($settings);
    }

    /**
     * @return string
     */
    public function render()
    {
        $fieldId = url_title($this->fieldName);
        $fileFt = new File_ft;
        $fileFt->settings = $this->settings;
        $fileFt->field_name = $this->fieldName;

        $fieldView = $fileFt->display_field($this->fieldValue);

        // Update some properties so the JS that is added to the page finds what it needs to.
        // This is for fields that contain array data, e.g. field[name]
        $fieldView = preg_replace('/data-input-image=\'(.*?)\'/', 'data-input-image="'. $fieldId .'"', $fieldView);
        $fieldView = preg_replace('/<img class="hidden" id="(.*?)"/', '<img class="hidden" id="'. $fieldId .'"', $fieldView);
        $fieldView = preg_replace('/id="(.*?)"/', 'id="'. $fieldId .'"', $fieldView);

        return $fieldView;
    }

    private function configureOptions(Array $options)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults(array(
                'allowed_directories' => '',
                'field_content_type' => 'all',
                'num_existing' => 0,
                'show_existing' => 'n',
            ))
        ;

        return $resolver->resolve($options);
    }
}
