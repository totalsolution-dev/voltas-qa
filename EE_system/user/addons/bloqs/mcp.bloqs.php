<?php

use Basee\App;
use BoldMinded\Bloqs\Service\Setting;
use EEBlocks\Controller\FieldTypeFilter;
use EEBlocks\Controller\FieldTypeManager;
use EEBlocks\Controller\HookExecutor;
use EEBlocks\Controller\TemplateCodeRenderer;
use EEBlocks\Database\Adapter;
use EEBlocks\Model\AtomDefinition;
use EEBlocks\Model\BlockDefinition;
use EEBlocks\Model\FieldType;
use EllisLab\ExpressionEngine\Service\Sidebar\FolderItem;
use EllisLab\ExpressionEngine\Service\Sidebar\Sidebar;
use Litzinger\FileField\FileField;

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

class Bloqs_mcp extends Bloqs_base
{
    public $vars;

    /**
     * @var HookExecutor
     */
    private $_hookExecutor;

    /**
     * @var FieldTypeManager
     */
    private $_ftManager;

    public function __construct()
    {
        parent::__construct();

        //Initialize hook executor
        $this->_hookExecutor = new HookExecutor(ee());

        //Initialize fieldtype filter
        $filter = new FieldTypeFilter();
        $filter->load(PATH_THIRD . 'bloqs/fieldtypes.xml');

        //Initialize fieldtype manager
        $this->_ftManager = new FieldTypeManager(ee(), $filter, $this->_hookExecutor);
    }

    private function generateSidebar()
    {
        $adapter = new Adapter(ee());
        $blockDefinitions = $adapter->getBlockDefinitions();
        $blockDefinitionId = ee()->input->get('blockdefinition');

        /** @var Sidebar $sidebar */
        $sidebar = ee('CP/Sidebar')->make();
        $license = $sidebar->addHeader(lang('bloqs_license'), $this->make_cp_url('license'));

        if (end(ee()->uri->rsegments) === 'license') {
            $license->isActive();
        }

        $heading = $sidebar->addHeader(lang('bloqs_blockdefinitions_title'), $this->make_cp_url('index'));
        $heading->withButton(lang('new'), $this->make_cp_url('blockdefinition', ['blockdefinition' => 'new']));

        if (in_array(end(ee()->uri->rsegments), ['index', 'blockdefinition', 'bloqs']) && !$blockDefinitionId) {
            $heading->isActive();
        }

        $list = $heading->addBasicList();

        /** @var BlockDefinition $blockDefinition */
        foreach ($blockDefinitions as $blockDefinition) {
            /** @var FolderItem $item */
            $url = ee('CP/URL')->make('addons/settings/bloqs/blockdefinition', ['blockdefinition' => $blockDefinition->id])->compile();
            $item = $list->addItem($blockDefinition->getName(), $url);

            if ($blockDefinition->getId() == $blockDefinitionId) {
                $item->isActive();
            }
        }
    }

    /**
     * @return array
     */
    public function index()
    {
        ee()->view->header = ['title' => lang('bloqs_blockdefinitions_title')];

        $this->generateSidebar();

        $adapter = new Adapter(ee());
        $blockDefinitions = $adapter->getBlockDefinitions();

        $vars['blockDefinitions'] = $blockDefinitions;
        $vars['blockdefinition_url'] = $this->make_cp_url('blockdefinition', ['blockdefinition' => 'new']);
        $vars['confirmdelete_url'] = $this->make_cp_url('confirmdelete', ['blockdefinition' => '']);
        $vars['copyblock_url'] = $this->make_cp_url('copyblock', ['blockdefinition' => '']);

        // Handle the delete functionality in the Add-on Manager view.
        ee()->javascript->output('
             $("a.m-link").click(function (e) {
                var modalIs = $("." + $(this).attr("rel"));
                $(".checklist", modalIs)
                  .html("") // Reset it
                  .append("<li>" + $(this).data("confirm") + "</li>");
                $("input[name=\'blockdefinition\']", modalIs).val($(this).data("blockdefinition"));
                e.preventDefault();
              });
            ');

        $viewFolder = App::viewFolder();

        return $this->render_view($viewFolder.'cp-blockdefinitions', $vars);
    }

    /**
     * @return array
     */
    public function blockdefinition()
    {
        // Load native fields language files
        ee()->lang->loadfile('fieldtypes');
        ee()->lang->loadfile('admin_content');
        ee()->lang->loadfile('channel');
        ee()->load->library('form_validation');

        $adapter = new Adapter(ee());

        $blockDefinitionId = ee()->input->get_post('blockdefinition');

        if ($blockDefinitionId == 'new') {
            $blockDefinitionId = null;
            $blockDefinition = new BlockDefinition();
            $blockDefinition
                ->setId(null)
                ->setName('')
                ->setShortName('')
            ;
        } else {
            $blockDefinitionId = intval($blockDefinitionId);
            $blockDefinition = $adapter->getBlockDefinitionById($blockDefinitionId);
        }

        $currentBlocksCollection = [];
        $currentBlocks = $adapter->getBlockDefinitions();
        /** @var BlockDefinition $block */
        foreach ($currentBlocks as $block) {
            $currentBlocksCollection[$block->getId()] = $block->getName();
        }

        $sections = [
            [
                [
                    'title' => 'bloqs_blockdefinition_name',
                    'desc' => lang('bloqs_blockdefinition_name_info'),
                    'fields' => [
                        'blockdefinition_name' => [
                            'required' => true,
                            'type' => 'text',
                            'value' => $blockDefinition->getName(),
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_shortname',
                    'desc' => lang('bloqs_blockdefinition_shortname_info'),
                    'fields' => [
                        'blockdefinition_shortname' => [
                            'required' => true,
                            'type' => 'text',
                            'value' => $blockDefinition->getShortName(),
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_instructions',
                    'desc' => lang('bloqs_blockdefinition_instructions_info'),
                    'fields' => [
                        'blockdefinition_instructions' => [
                            'required' => false,
                            'type' => 'textarea',
                            'value' => $blockDefinition->getInstructions(),
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_preview_image',
                    'desc' => lang('bloqs_blockdefinition_preview_image_info'),
                    'fields' => [
                        'blockdefinition_preview_image' => [
                            'required' => false,
                            'type' => 'html',
                            'content' =>  $fileField = (new FileField('blockdefinition_preview_image', $blockDefinition->getPreviewImage(), []))->render(),
                        ]
                    ]
                ],
            ],
            lang('bloqs_blockdefinition_nestable_section') => [
                [
                    'desc' => lang('bloqs_blockdefinition_nesting_description'),
                    'fields' => []
                ],
                [
                    'title' => 'bloqs_blockdefinition_nesting_root',
                    'desc' => lang('bloqs_blockdefinition_nesting_root_info'),
                    'fields' => [
                        'blockdefinition_nesting[root]' => [
                            'required' => false,
                            'type' => 'radio',
                            'value' => $blockDefinition->getNestingRule('root'),
                            'choices' => [
                                'any' => 'Can be nested at any level',
                                'root_only' => 'Can only be root',
                                'no_root' => 'Can\'t be root and must be a child of another block',
                            ],
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_nesting_child_of',
                    'desc' => lang('bloqs_blockdefinition_nesting_child_of_info'),
                    'fields' => [
                        'blockdefinition_nesting[child_of]' => [
                            'required' => false,
                            'type' => 'checkbox',
                            'value' => $blockDefinition->getNestingRule('child_of'),
                            'choices' => $currentBlocksCollection,
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_nesting_no_children',
                    'desc' => lang('bloqs_blockdefinition_nesting_no_children_info'),
                    'fields' => [
                        'blockdefinition_nesting[no_children]' => [
                            'required' => false,
                            'type' => 'yes_no',
                            'value' => ($blockDefinition->getNestingRule('no_children') ?: 'n'),
                        ],
                    ]
                ],
            ]
        ];

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ee()->form_validation->setCallbackObject($blockDefinition);
            ee()->form_validation->set_rules('blockdefinition_name', 'Name', 'trim|required');
            ee()->form_validation->set_rules('blockdefinition_shortname', 'Short Name', 'trim|required|callback_hasUniqueShortname[' . $blockDefinitionId . ']');
            $is_valid = ee()->form_validation->run();

            if ($is_valid === FALSE) {
                $this->_add_alert(false, 'blocks_settings_alert', lang('bloqs_blockdefinition_alert_title'), lang('bloqs_blockdefinition_alert_message'));
            } else {
                $name = ee()->input->post('blockdefinition_name');
                $shortName = ee()->input->post('blockdefinition_shortname');
                $instructions = ee()->input->post('blockdefinition_instructions');
                $previewImage = ee()->input->post('blockdefinition_preview_image');
                $settings['nesting'] = $this->getNestingSettings();

                $atomSettings = ee()->input->post('grid');
                $errors = array_merge($errors, $this->validateAtomSettings($atomSettings));

                if (empty($errors)) {
                    $blockDefinition->setName($name);
                    $blockDefinition->setShortName($shortName);
                    $blockDefinition->setInstructions($instructions);
                    $blockDefinition->setPreviewImage($previewImage);
                    $blockDefinition->setSettings($settings);

                    if ($blockDefinitionId == null) {
                        $adapter->createBlockDefinition($blockDefinition);
                    } else {
                        $adapter->updateBlockDefinition($blockDefinition);
                    }

                    $this->applyAtomSettings($blockDefinition, $atomSettings, $adapter);

                    ee()->functions->redirect($this->pkg_url, false, 302);
                    return;
                }
            }
        }

        $atomDefinitionsView = $this->getAtomDefinitionsView($blockDefinition, $errors);

        //Page Specific Resources
        ee()->cp->add_js_script('plugin', 'ee_url_title');
        ee()->cp->add_js_script('ui', 'sortable');
        ee()->cp->add_js_script('file', 'cp/sort_helper');
        ee()->cp->add_js_script('file', 'cp/grid');
        ee()->cp->add_js_script(array('file' => array('cp/confirm_remove')));

        //Title
        $vars['cp_page_title'] = lang('bloqs_module_name');
        ee()->view->header = ($blockDefinition->name == '') ? array('title' => 'New Block') : array('title' => $blockDefinition->name);

        $this->generateSidebar();

        //Build out the fields...
        $vars['sections'] = $sections;
        $vars['base_url'] = $this->pkg_url;
        $vars['blockDefinition'] = $blockDefinition;
        $vars['hiddenValues'] = array('blockdefinition' => is_null($blockDefinitionId) ? 'new' : $blockDefinitionId);
        $vars['atomDefinitionsView'] = $atomDefinitionsView;
        $vars['post_url'] = $this->make_cp_url('blockdefinition', array('blockdefinition' => $blockDefinitionId));
        $vars['save_btn_text'] = 'save';
        $vars['save_btn_text_working'] = 'saving';
        $vars['eeVersion'] = 'ee'.App::majorVersion();
        $vars['eeVersionNumber'] = App::majorVersion();

        ee()->javascript->output('EE.grid_settings();');

        // If this is a new block definition, turn on the EE feature where the
        // shortname gets autopopulated when the name gets entered.
        if ($blockDefinition->name == '') {
            ee()->javascript->output('
                $("input[name=blockdefinition_name]").bind("keyup keydown", function() {
                  $(this).ee_url_title("input[name=blockdefinition_shortname]", true);
                });
              ');
        }

        $viewFolder = App::viewFolder();

        return $this->render_view($viewFolder.'cp-blockdefinition', $vars);
    }

    /**
     * @return array
     */
    private function getNestingSettings()
    {
        $post = ee()->input->post('blockdefinition_nesting');

        // Cleanup empty values that the multi-select field in EE likes to add :/
        $post['child_of'] = isset($post['child_of']) ? array_filter($post['child_of']) : null;

        return $post;
    }

    /**
     * Generate the cp-atomdefinitions view
     *
     * @param BlockDefinition $blockDefinition
     * @param array $atom_errors
     * @return string
     */
    private function getAtomDefinitionsView(BlockDefinition $blockDefinition, $atom_errors = array())
    {
        $vars = array();
        $vars['columns'] = array();

        foreach ($blockDefinition->getAtomDefinitions() as $atomDefinition) {
            $field_errors = (!empty($atom_errors['col_id_' . $atomDefinition->id])) ? $atom_errors['col_id_' . $atomDefinition->id] : array();
            $atomDefinitionView = $this->getAtomDefinitionView($atomDefinition, NULL, $field_errors);
            $vars['columns'][] = $atomDefinitionView;
        }

        // Fresh settings forms ready to be used for added columns
        $vars['settings_forms'] = [];
        /** @var FieldType  $fieldType */
        foreach ($this->_ftManager->getFieldTypes() as $fieldType) {
            $fieldName = $fieldType->getType();
            $vars['settings_forms'][$fieldName] = $this->getAtomDefinitionSettingsForm(null, $fieldName);
        }

        // Will be our template for newly-created columns
        $vars['blank_col'] = $this->getAtomDefinitionView(null);
        $vars['eeVersion'] = 'ee'.App::majorVersion();
        $vars['eeVersionNumber'] = App::majorVersion();

        if (empty($vars['columns'])) {
            $vars['columns'][] = $vars['blank_col'];
        }

        $viewFolder = App::viewFolder();

        return $this->render_view($viewFolder.'cp-atomdefinitions', $vars);
    }

    /**
     * create the single view for each atom 'block'
     *
     * @param AtomDefinition $atomDefinition
     * @param $column
     * @param $field_errors
     *
     * @return  string  Rendered column view for settings page
     */
    public function getAtomDefinitionView($atomDefinition, $column = NULL, $field_errors = array())
    {
        $fieldtypes = $this->_ftManager->getFieldTypes();

        // Create a dropdown-friendly array of available fieldtypes
        $fieldtypesLookup = array();
        /** @var FieldType $fieldType */
        foreach ($fieldtypes as $fieldType) {
            $fieldtypesLookup[$fieldType->getType()] = $fieldType->getName();
        }

        $field_name = (is_null($atomDefinition)) ? 'new_0' : 'col_id_' . $atomDefinition->getId();

        $settingsForm = (is_null($atomDefinition))
            ? $this->getAtomDefinitionSettingsForm(null, 'text')
            : $this->getAtomDefinitionSettingsForm($atomDefinition, $atomDefinition->getType());

        $vars = array(
            'atomDefinition' => $atomDefinition,
            'field_name' => $field_name,
            'settingsForm' => $settingsForm,
            'fieldtypes' => $fieldtypesLookup,
            'field_errors' => $field_errors
        );

        $viewFolder = App::viewFolder();

        $ret = $this->render_view($viewFolder.'cp-atomdefinition', $vars);

        return $ret['body'];
    }

    /**
     * Returns rendered HTML for the custom settings form of a grid column type
     *
     * @param AtomDefinition $atomDefinition
     * @param string $type
     * @return string Rendered HTML settings form for given fieldtype and column data
     */
    public function getAtomDefinitionSettingsForm($atomDefinition, $type)
    {
        /** @var Api_channel_fields $ft_api */
        $ft_api = ee()->api_channel_fields;
        $settings = null;

        // Returns blank settings form for a specific fieldtype
        if (is_null($atomDefinition)) {
            $ft = $ft_api->setup_handler($type, true);

            $_default_grid_ct = ['text', 'textarea', 'rte'];
            $ft->_init(['content_type' => 'grid']);

            if ($ft_api->check_method_exists('grid_display_settings')) {
                if ($ft->accepts_content_type('blocks/1')) {
                    $ft->_init(['content_type' => 'blocks/1']);
                } elseif ($ft->accepts_content_type('grid')) {
                    $ft->_init(['content_type' => 'grid']);
                }
                $settings = $ft_api->apply('grid_display_settings', [[]]);

            } elseif ($ft_api->check_method_exists('display_settings')) {
                if ($ft->accepts_content_type('grid')) {
                    $ft->_init(['content_type' => 'grid']);
                }
                $settings = $ft_api->apply('display_settings', [[]]);
            }
            return $this->_view_for_col_settings($atomDefinition, $type, $settings);
        }

        $fieldtype = $this->_ftManager->instantiateFieldtype(
            $atomDefinition,
            null,
            null,
            0, // Field ID? At this point, we don't have one.
            0
        );

        $settings = $fieldtype->displaySettings($atomDefinition->getSettings());

        // Otherwise, return the pre-populated settings form based on column settings
        return $this->_view_for_col_settings($atomDefinition, $type, $settings);
    }

    /**
     * Allow a user to copy a block definition and its atoms
     */
    public function copyblock()
    {
        $adapter = new Adapter(ee());
        $blockDefinitionId = ee()->input->get_post('blockdefinition');
        $blockDefinitionName = ee()->input->get_post('blockdefinition_name');
        $blockDefinitionShortName = ee()->input->get_post('blockdefinition_shortname');
        $blockDefinitionId = intval($blockDefinitionId);
        $blockDefinition = $adapter->getBlockDefinitionById($blockDefinitionId);

        if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
            !is_null($blockDefinition) &&
            $blockDefinitionName &&
            $blockDefinitionShortName
        ) {
            $adapter->copyBlockDefinition($blockDefinitionId, $blockDefinitionName, $blockDefinitionShortName);
            ee()->functions->redirect($this->pkg_url, false, 302);
        }

        return;
    }

    /**
     * Delete a block
     *
     * @return void
     */
    public function confirmdelete()
    {
        $adapter = new Adapter(ee());
        $blockDefinitionId = ee()->input->get_post('blockdefinition');
        $blockDefinitionId = intval($blockDefinitionId);
        $blockDefinition = $adapter->getBlockDefinitionById($blockDefinitionId);

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !is_null($blockDefinition)) {
            $adapter->deleteBlockDefinition($blockDefinitionId);
            ee()->functions->redirect($this->pkg_url, false, 302);
        }

        return;
    }

    /**
     * Generates the mcp view for the controller action
     *
     * @param name - string - name of view file
     * @param vars - array
     *
     * @return array
     */
    public function render_view($name, $vars)
    {
        return [
            'breadcrumb' => [],
            'body' => ee('View')->make($this->pkg . ':' . $name)->render($vars)
        ];
    }

    /**
     * Returns rendered HTML for the custom settings form of a grid column type,
     * helper method for Grid_lib::get_settings_form
     *
     * @param string  Name of fieldtype to get settings form for
     * @param array   Column data from database to populate settings form
     * @param int     Column ID for field naming
     * @return string Rendered HTML settings form for given fieldtype and
     *                column data
     */
    protected function _view_for_col_settings($atomDefinition, $type, $settings)
    {
        $viewFolder = App::viewFolder();

        $settings_view = $this->render_view(
            $viewFolder.'cp-atomdefinitionsettings',
            array(
                'atomDefinition' => $atomDefinition,
                'col_type' => $type,
                'col_settings' => (empty($settings)) ? array() : $settings
            )
        );

        $col_id = (is_null($atomDefinition)) ? 'new_0' : 'col_id_' . $atomDefinition->id;

        $body = $settings_view['body'];
        // Because several new fields are React fields with encoded settings which are used to render the
        // final html output instead of having access to the html here like all other basic fieldtypes.
        if (App::isGteEE4() && preg_match_all('/data-select-react="(.*?)" data-input-value="(.*?)"/', $body, $matches)) {
            foreach ($matches[1] as $index => $settingsMatch) {
                $settings = json_decode(base64_decode($settingsMatch));
                $optionName = $matches[2][$index];
                $newOptionName = 'grid[cols][' . $col_id . '][col_settings][' . $optionName . ']';

                $settings->name = $newOptionName;
                $settings = base64_encode(json_encode($settings));

                $body = str_replace($settingsMatch, $settings, $body);
                $body = preg_replace('/data-input-value="'. $optionName .'"/', 'data-input-value="' . $newOptionName . '"', $body);
            }
        }

        // Namespace form field names
        return $this->_namespace_inputs(
            $body,
            '$1name="grid[cols][' . $col_id . '][col_settings][$2]$3"'
        );

        //return StringHelper::namespaceInputs(
        //    'name',
        //    $settings_view['body'],
        //    '$1name="grid[cols][' . $col_id . '][col_settings][$2]$3"'
        //);
    }

    /**
     * Performs find and replace for input names in order to namespace them
     * for a POST array
     *
     * @param string  String to search
     * @param string  String to use for replacement
     * @return string  String with namespaced inputs
     */
    protected function _namespace_inputs($search, $replace)
    {
        return preg_replace(
            '/(<[input|select|textarea][^>]*)name=["\']([^"\'\[\]]+)([^"\']*)["\']/',
            $replace,
            $search
        );
    }

    /**
     * @param $validate
     * @return array
     */
    protected function prepareErrors($validate)
    {
        $errors = array();
        $field_names = array();

        // Gather error messages and fields with errors so that we can
        // display the error messages and highlight the fields that have errors
        foreach ($validate as $column => $fields) {
            foreach ($fields as $field => $error) {
                $errors[] = $error;
                $field_names[] = 'grid[cols][' . $column . '][' . $field . ']';
            }
        }

        // Make error messages unique and convert to a string to pass to form validaiton library
        $errors = array_unique($errors);
        $error_string = '';
        foreach ($errors as $error) {
            $error_string .= lang($error) . '<br>';
        }

        return array(
            'field_names' => $field_names,
            'error_string' => $error_string
        );
    }


    /**
     * @param $settings
     * @return array
     */
    private function validateAtomSettings($settings)
    {
        $errors = array();
        $col_names = array();

        // Create an array of column names for counting to see if there are
        // duplicate column names; they should be unique
        foreach ($settings['cols'] as $col_field => $column) {
            $col_names[] = $column['col_name'];
        }

        $col_name_count = array_count_values($col_names);

        foreach ($settings['cols'] as $col_field => $column) {
            // Column labels are required
            if (empty($column['col_label'])) {
                $errors[$col_field]['col_label'] = 'grid_col_label_required';
            }

            // Column names are required
            if (empty($column['col_name'])) {
                $errors[$col_field]['col_name'] = 'grid_col_name_required';
            }
            // Columns cannot be the same name as our protected modifiers
            /*
            elseif (in_array($column['col_name'], ee()->grid_parser->reserved_names))
            {
              $errors[$col_field]['col_name'] = 'grid_col_name_reserved';
            }
            */
            // There cannot be duplicate column names
            elseif ($col_name_count[$column['col_name']] > 1) {
                $errors[$col_field]['col_name'] = 'grid_duplicate_col_name';
            }

            // Column names must contain only alpha-numeric characters and no spaces
            if (preg_match('/[^a-z0-9\-\_]/i', $column['col_name'])) {
                $errors[$col_field]['col_name'] = 'grid_invalid_column_name';
            }

            $column['col_id'] = (strpos($col_field, 'new_') === FALSE)
                ? str_replace('col_id_', '', $col_field) : FALSE;
            $column['col_required'] = isset($column['col_required']) ? 'y' : 'n';
            $column['col_settings']['col_required'] = $column['col_required'];

            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId(intval($column['col_id']))
                ->setShortName($column['col_name'])
                ->setName($column['col_label'])
                ->setInstructions($column['col_instructions'])
                ->setOrder(1)
                ->setType($column['col_type'])
                ->setSettings($column['col_settings'])
            ;

            $fieldtype = $this->_ftManager->instantiateFieldtype($atomDefinition, null, null, 0, 0);

            // Let fieldtypes validate their Grid column settings; we'll
            // specifically call grid_validate_settings() because validate_settings
            // works differently and we don't want to call that on accident
            $ft_validate = $fieldtype->validateSettings($column['col_settings']);

            if (is_string($ft_validate)) {
                $errors[$col_field]['custom'] = $ft_validate;
            }
        }

        if (!empty($errors)) {
            $this->_add_alert(false, 'blocks_block_alert', lang('bloqs_blockdefinition_atomdefinition_alert_title'), lang('bloqs_blockdefinition_atomdefinition_alert_message'));
        }

        return $errors;
    }

    /**
     * @param $blockDefinition
     * @param $settings
     * @param Adapter $adapter
     */
    private function applyAtomSettings(BlockDefinition $blockDefinition, $settings, Adapter $adapter)
    {
        //$new_field = ee()->grid_model->create_field($settings['field_id'], $this->content_type);

        // Keep track of column IDs that exist so we can compare it against
        // other columns in the DB to see which we should delete
        $col_ids = array();

        // Determine the order of each atom definition.
        $order = 0;

        // Go through ALL posted columns for this field
        foreach ($settings['cols'] as $col_field => $column) {
            $order++;
            // Attempt to get the column ID; if the field name contains 'new_',
            // it's a new field, otherwise extract column ID
            $column['col_id'] = (strpos($col_field, 'new_') === FALSE)
                ? str_replace('col_id_', '', $col_field) : FALSE;

            $id = $column['col_id'] ? intval($column['col_id']) : null;

            $column['col_required'] = (isset($column['col_required']) && $column['col_required'] == 'y') ? 'y' : 'n';
            $column['col_settings']['col_required'] = $column['col_required'];

            // When creating a new block in EE4 that only has a text field, and the user has not clicked on the
            // field type option (a React field) the following 3 fields do not save default values even though
            // they have the .act class and have checked="checked" in the html.
            // https://boldminded.com/support/ticket/1637
            if ($column['col_type'] === 'text') {
                if (!isset($column['col_settings']['field_text_direction'])) {
                    $column['col_settings']['field_text_direction'] = 'ltr';
                }
                if (!isset($column['col_settings']['field_fmt'])) {
                    $column['col_settings']['field_fmt'] = 'none';
                }
                if (!isset($column['col_settings']['field_content_type'])) {
                    $column['col_settings']['field_content_type'] = 'all';
                }
            }

            // We could find the correct atom definition in the block definition, but we'd end up overwriting all of
            // it's properties anyway, so we may as well make a new model object that represents the same atom definition.
            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId($id)
                ->setShortName($column['col_name'])
                ->setName($column['col_label'])
                ->setInstructions($column['col_instructions'])
                ->setOrder($order)
                ->setType($column['col_type'])
                ->setSettings($column['col_settings'])
            ;

            $atomDefinition->settings = $this->_save_settings($atomDefinition);
            $atomDefinition->settings['col_required'] = $column['col_required'];
            $atomDefinition->settings['col_search'] = isset($column['col_search']) ? $column['col_search'] : 'n';

            if (is_null($atomDefinition->id)) {
                $adapter->createAtomDefinition($blockDefinition->id, $atomDefinition);
            } else {
                $adapter->updateAtomDefinition($atomDefinition);
            }

            $col_ids[] = $atomDefinition->id;
        }

        // Delete existing atoms that were not included.
        foreach ($blockDefinition->getAtomDefinitions() as $atomDefinition) {
            if (!in_array($atomDefinition->id, $col_ids)) {
                $adapter->deleteAtomDefinition($atomDefinition->id);
            }
        }
    }

    /**
     * @param $atomDefinition
     * @return array|mixed|null
     */
    protected function _save_settings($atomDefinition)
    {
        if (!isset($atomDefinition->settings)) {
            $atomDefinition->settings = array();
        }

        $fieldtype = $this->_ftManager->instantiateFieldtype(
            $atomDefinition,
            null,
            null,
            0,
            0
        );

        if (!($settings = $fieldtype->saveSettings($atomDefinition->settings))) {
            return $atomDefinition->settings;
        }

        return $settings;
    }

    /**
     * handles any sort of response actions required by the module
     *
     * @param type             - bool - false = issue/error || true = success
     * @param source           - string - where response is coming from - used to generate response message from lang
     * @param redirect_success - string - url to redirect to on success
     * @param redirect_fail    - string - url to redirect to on failure
     *
     * @return  void
     */
    private function _add_alert($type, $name, $title, $msg)
    {
        // prep up a response message for the user
        if ($type === true) {
            ee('CP/Alert')->makeInline($name)
                ->asSuccess()
                ->withTitle($title)
                ->addToBody($msg)
                ->defer();
        } else {
            ee('CP/Alert')->makeInline($name)
                ->asIssue()
                ->withTitle($title)
                ->addToBody($msg)
                ->now();
        }
    }

    public function fetch_template_code()
    {
        // Ugh :(
        require_once SYSPATH.'ee/legacy/fieldtypes/EE_Fieldtype.php';

        ee()->load->library('addons');
        $adapter = new Adapter(ee());
        $template = new TemplateCodeRenderer($adapter, ee()->addons->get_installed('fieldtypes'));

        $fieldName = ee()->input->get('field_name');
        $fieldId = ee()->input->get('field_id');
        $includeBlocks = ee()->input->get('include_blocks');

        $field = ee('Model')->get('ChannelField', $fieldId)->first();

        $nestable = isset($field->field_settings['nestable']) ? $field->field_settings['nestable'] : 'n';
        $isNestable = $nestable === 'y' ? true : false;

        $str = $template->renderFieldTemplate($fieldName, $fieldId, $includeBlocks, $isNestable);

		echo $str . PHP_EOL; die;
    }

    public function license()
    {
        $this->generateSidebar();

        /** @var Setting $setting */
        $setting = ee('bloqs:Setting');

        if ($license = ee('Request')->post('license')) {
            $setting->save([
                'license' => $license,
            ]);

            ee('CP/Alert')
                ->makeInline('shared-form')
                ->asSuccess()
                ->withTitle('Success')
                ->addToBody('License updated!')
                ->now();
        }

        $sections = [
            [
                [
                    'title' => 'bloqs_license_name',
                    'desc' => lang('bloqs_license_desc'),
                    'fields' => [
                        'license' => [
                            'required' => true,
                            'type' => 'text',
                            'value' => $setting->get('license'),
                        ]
                    ]
                ],
            ],
        ];

        $vars['sections'] = $sections;
        $vars['base_url'] = $this->make_cp_url('license');
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = lang('saving');
        $vars['cp_page_title'] = '';

        return $this->render_view('license', $vars);
    }
}
