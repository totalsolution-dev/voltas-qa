<?php

use Basee\App;
use BoldMinded\Bloqs\Service\FormSecret;
use BoldMinded\Bloqs\Service\Trial;
use EEBlocks\Controller\FieldTypeFilter;
use EEBlocks\Controller\FieldTypeManager;
use EEBlocks\Controller\HookExecutor;
use EEBlocks\Controller\PublishController;
use EEBlocks\Controller\TagController;
use EEBlocks\Database\Adapter;
use EEBlocks\Helper\UrlHelper;

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

class Bloqs_ft extends EE_Fieldtype
{
    public $pkg = 'bloqs';

    var $info = [
        'name' => 'Bloqs',
        'version' => BLOQS_VERSION
    ];

    var $has_array_data = true;
    var $cache = null;

    private $error_fields = [];
    private $error_string = '';
    private $_hookExecutor;
    private $_ftManager;

    function __construct()
    {
        $this->_hookExecutor = new HookExecutor(ee());

        $filter = new FieldTypeFilter();
        $filter->load(PATH_THIRD.'bloqs/fieldtypes.xml');

        $this->_ftManager = new FieldTypeManager(ee(), $filter, $this->_hookExecutor);

        if (isset(ee()->session)) {
            if (!isset(ee()->session->cache[__CLASS__])) {
                ee()->session->cache[__CLASS__] = [];
            }
            $this->cache =& ee()->session->cache[__CLASS__];

            if (!isset($this->cache['includes'])) {
                $this->cache['includes'] = [];
            }
            if (!isset($this->cache['validation'])) {
                $this->cache['validation'] = [];
            }
        }
    }

    protected function includeThemeJS($file)
    {
        if (!in_array($file, $this->cache['includes'])) {
            $this->cache['includes'][] = $file;
            ee()->cp->add_to_foot('<script type="text/javascript" src="'.$this->getThemeURL().$file.'?version='.BLOQS_VERSION.'"></script>');
        }
    }

    protected function includeThemeCSS($file)
    {
        if (!in_array($file, $this->cache['includes'])) {
            $this->cache['includes'][] = $file;
            ee()->cp->add_to_head('<link rel="stylesheet" href="'.$this->getThemeURL().$file.'?version='.BLOQS_VERSION.'">');
        }
    }

    protected function getThemeURL()
    {
        if (!isset($this->cache['theme_url'])) {
            $theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : ee()->config->slash_item('theme_folder_url').'third_party/';
            $this->cache['theme_url'] = $theme_folder_url.'bloqs/';
        }

        return $this->cache['theme_url'];
    }

    protected function includeGridAssets()
    {
        if ( ! ee()->session->cache(__CLASS__, 'grid_assets_loaded')) {
            ee()->cp->add_js_script('ui', 'sortable');
            ee()->cp->add_js_script('file', 'cp/sort_helper');
            ee()->cp->add_js_script('file', 'cp/grid');
            ee()->cp->add_js_script('plugin', 'nestable');

            ee()->session->set_cache(__CLASS__, 'grid_assets_loaded', TRUE);
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function save_settings($data)
    {
        $strip = ['field_name', 'field_id', 'field_required'];

        //there are a few fields that are passed in with the data array
        //that we don't want to save - so we strip those out of the data array
        foreach( $strip as $field ) {
            if( isset($data[$field]) ) {
                unset($data[$field]);
            }
        }

        return array_merge($data, ['field_wide' => true]);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function display_field($data)
    {
        /** @var Trial $trialService */
        $trialService = ee('bloqs:Trial');
        if ($trialService->isTrialExpired()) {
            return $trialService->showTrialExpiredInline();
        }

        $this->includeGridAssets();
        $this->includeThemeCSS('css/cp.css');
        $this->includeThemeJS('javascript/tree-validation.js');
        $this->includeThemeJS('javascript/cp.js');

        $adapter = new Adapter(ee());
        $entryId = isset($this->content_id) ? $this->content_id : '';
        $blockDefinitions = $adapter->getBlockDefinitionsForField($this->field_id);
        $autoExpand = (isset($this->settings['auto_expand']) && $this->settings['auto_expand'] === 'y');
        $isRevision = ee()->input->get('version') ? true : false;
        $viewData = ['blocks' => []];

        $controller = new PublishController(
            ee(),
            $this->id(),
            $this->name(),
            $adapter,
            $this->_ftManager,
            $this->_hookExecutor
        );

        // Validation failed. Either our validation or another validation,
        // we don't know, but now we need to output the data that was
        // entered instead of getting it from the database.
        if (is_array($data) || isset($this->cache['validation'][$this->id()])) {
            if (!is_array($data)) {
                $data = $this->cache['validation'][$this->id()]['value'];
            }

            $viewData = $controller->displayValidatedField(
                $entryId,
                $blockDefinitions,
                $data,
                $isRevision
            );
        } else if (!is_array($data)) {
            // Let's build these blocks out
            $blocks = $adapter->getBlocks($entryId, $this->field_id);

            $viewData = $controller->displayField(
                $entryId,
                $blockDefinitions,
                $blocks
            );
        }

        // Patch for EE2 to EE3 migrations
        if(isset($viewData['blocks'])) {
            $viewData['bloqs'] = $viewData['blocks'];
            unset($viewData['blocks']);
        }

        if(AJAX_REQUEST || $autoExpand) {
            foreach($viewData['bloqs'] as $i => $b) {
                $viewData['bloqs'][$i]['visibility'] = 'expanded';
            }
        }

        ee()->javascript->set_global([
            // Unsupported hidden config. Use at your own risk.
            'bloqs.collapseOnDrag' => ee()->config->item('bloqs_collapse_on_drag') === 'n' ? false : true,
        ]);

        /** @var FormSecret $secretService */
        $secretService = ee('bloqs:FormSecret');
        $secretService->setSecret($this->field_id);

        $viewData = array_merge($viewData, [
            'eeVersion' => 'ee'.App::majorVersion(),
            'eeVersionNumber' => App::majorVersion(),
            'fieldSettingNestable' => isset($this->settings['nestable']) ? $this->settings['nestable'] : 'n',
            'formSecret' => $secretService->getSecret(),
            'formSecretFieldName' => $secretService->getFieldName(),
            'jsonDefinitions' => htmlspecialchars(json_encode($blockDefinitions), ENT_QUOTES, 'UTF-8'),
            'menuGridDisplay' => (isset($this->settings['menu_grid_display']) && $this->settings['menu_grid_display'] === 'y'),
            'showEmpty' => (empty($viewData['bloqs'])),
        ]);

        return ee('View')->make($this->pkg.':editor')->render($viewData);
    }

    /**
     * @param $data
     * @return bool
     */
    public function validate($data)
    {
        $fieldId = $this->id();
        if (isset($this->cache['validation'][$fieldId])) {
            return $this->cache['validation'][$fieldId];
        }

        ee()->lang->loadfile('bloqs');

        $adapter = new Adapter(ee());
        $entryId = isset($this->settings['entry_id']) ? $this->settings['entry_id'] : ee()->input->get_post('entry_id');

        $controller = new PublishController(
            ee(),
            $this->id(),
            $this->name(),
            $adapter,
            $this->_ftManager,
            $this->_hookExecutor
        );

        $validated = $controller->validate(
            $data,
            $entryId
        );

        $this->cache['validation'][$fieldId] = $validated;

        return $validated;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function validate_settings($data)
    {
        $validator = ee('Validation')->make([
            'field_name' => 'uniqueBlockShortname',
        ]);
        $validator->defineRule('uniqueBlockShortname', [$this, '_validate_shortname']);

        return $validator->validate($data);
    }

    /**
     * @param $field_name
     * @param $field_value
     * @param $params
     * @param $rule
     * @return bool|string
     */
    public function _validate_shortname($field_name, $field_value, $params, $rule)
    {
        ee()->lang->loadfile('bloqs');

        $this->error_fields = [];
        $this->error_string = '';

        // check the shortname of the field type against existing blocks to ensure we don't have duplicates.
        $adapter = new Adapter(ee());
        $isUnique = $adapter->getBlockDefinitionByShortname($field_value);

        if ( !empty($isUnique) ) {
            //if we needed to do so, we could test to see if this was an AJAX_REQUEST and modify
            //our return data accordingly.  But for right now, we have no need to because our return data
            //is the same.
            $this->error_fields[] = $field_name;
            $this->error_string = lang('bloqs_field_shortname_not_unique');
            $rule->stop();

            return $this->error_string;
        }

        return true;
    }

    public function save($data)
    {
        ee()->session->set_cache(__CLASS__, $this->name(), $data);

        return ' ';
    }

    public function post_save($data)
    {
        $data = ee()->session->cache(__CLASS__, $this->name(), false);

        /** @var FormSecret $secretService */
        $secretService = ee('bloqs:FormSecret');
        $secretService->setFieldId($this->field_id);

        if (!$secretService->isSecretValid()) {
            ee()->logger->developer(sprintf(
                'Bloqs secret key was invalid. %s could not save entry #%d. Field: %d Session: %s Post: %s',
                ee()->session->userdata('member_id'),
                $this->content_id,
                $this->id(),
                $secretService->getSessionSecret(),
                $secretService->getPostSecret()
            ));
            // Disabled 12/2019 - Initially added to prevent really fast Save button double clicks, which would
            // generate duplicate POST requests, which messed up Bloqs saving routine. However, while investigating
            // the blocks disappearing because the sessionSecret was blank it would return here, preventing any save
            // action at all, thus making it appear as if new or updated blocks were disappearing... because they
            // were never saved to begin with. Disabling because I can no longer replicate the quick double Save
            // button clicks, no matter how I try. When this was first added it was easy to replicate. I don't know
            // what has changed since.

            // return;
        }

        // Prevent saving if save() was never called, happens in Channel Form if the field is missing from the form
        if ($data !== false && !empty($data))
        {
            try {
                $adapter = new Adapter(ee());
                $entryId = isset($this->content_id) ? $this->content_id : ee()->input->get_post('entry_id');
                // Capture current block IDs before we take any possible action on them.
                $blockIds = $adapter->getBlockIds($entryId, $this->id());

                $controller = new PublishController(
                    ee(),
                    $this->id(),
                    $this->name(),
                    $adapter,
                    $this->_ftManager,
                    $this->_hookExecutor
                );

                // Decode the string that the Nestable plugin creates and pass it along
                if (!isset($data['tree_order']) && isset($_POST['field_id_' . $this->id()]['tree_order'])) {
                    $data['tree_order'] = json_decode($_POST['field_id_' . $this->id()]['tree_order'], true);
                }

                $controller->save(
                    $data,
                    $entryId
                );

                // When viewing a revision the blocks are added to the page as if they were new blocks, however, saving
                // the revision as the new version of the entry will append them to the blocks currently assigned to the
                // entry, thus we delete all the old blocks b/c the revision data will be used to re-create them.
                if (!empty($blockIds) && ee()->input->post('versioning_enabled') === 'y' && ee()->input->post('version_number')) {
                    foreach ($blockIds as $blockId) {
                        $adapter->deleteBlock($blockId);
                    }
                }
            } catch (Exception $exception) {
                ee()->logger->developer('Bloqs save error: '. $exception->getMessage() . $exception->getTraceAsString());

                $alert = ee('CP/Alert');
                $alert
                    ->makeBanner()
                    ->asIssue()
                    ->withTitle('Bloqs Save Error')
                    ->addToBody('An error has occurred while saving this entry. Please ask your site administrator to check the developer logs.')
                    ->defer();
            }
        }
    }

    /**
     * @param $entryId
     * @param $fieldId
     * @return array
     */
    private function getBlocks($entryId, $fieldId)
    {
        $key = "blocks|fetch|entry_id:$entryId;field_id:$fieldId";
        $blocks = ee()->session->cache(__CLASS__, $key, false);

        if ($blocks) {
            ee()->TMPL->log_item('Blocks: retrieved cached blocks for "' . $key . '"');
            return $blocks;
        }

        ee()->TMPL->log_item('Blocks: fetching blocks for "' . $key . '"');

        $adapter = new Adapter(ee());
        $blocks = $adapter->getBlocks(
            $entryId,
            $fieldId
        );

        ee()->session->set_cache(__CLASS__, $key, $blocks);

        return $blocks;
    }

    public function replace_tag($data, $params = [], $tagdata = false)
    {
        if (!$tagdata) {
            return '';
        }

        /** @var Trial $trialService */
        $trialService = ee('bloqs:Trial');
        if ($trialService->isTrialExpired()) {
            return $trialService->showTrialExpiredInline();
        }

        $entryId = $this->row['entry_id'];
        $adapter = new Adapter(ee());

        // Before we attempt to call the ee:LivePreview service lets make sure its available.
        $isLivePreviewAvailable = App::isFeatureAvailable('livePreview');

        if ($isLivePreviewAvailable && ee('LivePreview')->hasEntryData() && isset($_POST['field_id_'.$this->field_id])) {
            // Stupid hack. Why is the RTE package requested when Live Previewing?
            ee()->load->add_package_path(SYSPATH.'ee/EllisLab/Addons/rte/');
            ee()->load->library('rte_lib');

            $blocks = $adapter->getBlocksFromPost($_POST['field_id_'.$this->field_id], $this->field_id);
        } else {
            $blocks = $this->getBlocks($entryId, $this->field_id);
        }

        $controller = new TagController(
            ee(),
            $this->field_id,
            $this->_ftManager,
            $adapter,
            $this->settings
        );

        return $controller->replace($tagdata, $blocks, $this->row);
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @param $modifier
     * @return int|string
     */
    public function replace_tag_catchall($data, $params = [], $tagdata = false, $modifier)
    {
        $entryId = $this->row['entry_id'];

        $blocks = $this->getBlocks($entryId, $this->field_id);

        $adapter = new Adapter(ee());

        $controller = new TagController(
            ee(),
            $this->field_id,
            $this->_ftManager,
            $adapter,
            $this->settings
        );

        switch ($modifier) {
            case 'total_blocks':
            case 'total_rows':
                return $controller->totalBlocks($blocks, $params);
        }

        return '';
    }

    /**
     * @param $data
     * @return array
     */
    public function display_settings($data)
    {
        $urlHelper = new UrlHelper();

        ee()->javascript->set_global('bloqs.ajax_fetch_template_code',
            $urlHelper->getAction('fetch_template_code', [
                'field_id' => $this->field_id,
            ]));

        $this->includeThemeCSS('css/edit-field.css');
        $this->includeThemeJS('javascript/edit-field.js');

        $blockDefinitionMaintenanceUrl = ee('CP/URL')->make('addons/settings/'.$this->pkg);

        ee()->lang->loadfile('bloqs');

        $adapter = new Adapter(ee());
        $selectedBlockDefinitions = $adapter->getBlockDefinitionsForField($this->field_id);
        $allBlockDefinitions = $adapter->getBlockDefinitions();
        $blockDefinitions = $this->sortBlockDefinitions($selectedBlockDefinitions, $allBlockDefinitions);
        $fieldSettings = $data['field_settings'];

        $output = '';

        if (count($blockDefinitions) > 0) {
            $output .= '<ul class="tbl-list blockselectors">';
            $i = 1;

            foreach ($blockDefinitions as $blockDefinition) {
                $prefix = 'blockdefinitions[' . $blockDefinition->id . ']';
                $checked = '';
                if ($blockDefinition->selected) {
                    $checked = 'checked';
                }
                if (App::isEE3()) {
                    $output .= '<li class="nestable-item">';
                } else {
                    $output .= '<li class="tbl-list-item nestable-item"><div class="tbl-row">';
                }

                $output .= '<label class="choice block blockselector">';
                $output .= '<input type="hidden" name="' . $prefix . '[order]" value="' . $i . '" js-order>';
                $output .= '<input type="hidden" name="' . $prefix . '[selected]" value="0">';
                $output .= '<input type="hidden" name="' . $prefix . '[field_name]" value="' . $blockDefinition->shortname . '" js-field-name>';
                $output .= '<div class="list-reorder"></div><input type="checkbox" name="' . $prefix . '[selected]" value="1" ' . $checked . ' js-checkbox> <span>' . $blockDefinition->name . '</span>';
                $output .= '</label>';

                if (App::isEE3()) {
                    $output .= '</li>' . PHP_EOL;
                } else {
                    $output .= '</div></li>' . PHP_EOL;
                }

                $i++;
            }

            $output .= '</ul>';
        } else {
            $output .= '<p class="notice">' . lang('bloqs_fieldsettings_noblocksdefined') . '</p>';
        }

        $output .= "<p><a class='btn action' href='{$blockDefinitionMaintenanceUrl}'>" . lang('bloqs_fieldsettings_manageblockdefinitions') . "</a></p>";

        if ($this->field_id) {
            $templateOutput = '<pre class="bloqs-template-code">Loading...</pre>';
        } else {
            $templateOutput = '<p>You must save this field before a basic template can be provided.</p>';
        }

        $settings = [
            [
                'title' => 'bloqs_fieldsettings_auto_expand',
                'desc' => 'bloqs_fieldsettings_auto_expand_desc',
                'wide' => true,
                'fields' => [
                    'auto_expand' => [
                        'type' => 'yes_no',
                        'value' => (isset($fieldSettings['auto_expand']) ? $fieldSettings['auto_expand'] : 'n'),
                    ]
                ]
            ],
            [
                'title' => 'bloqs_fieldsettings_nestable',
                'desc' => 'bloqs_fieldsettings_nestable_desc',
                'wide' => true,
                'fields' => [
                    'nestable' => [
                        'type' => 'yes_no',
                        'value' => (isset($fieldSettings['nestable']) ? $fieldSettings['nestable'] : 'n'),
                    ]
                ]
            ],
            [
                'title' => 'bloqs_fieldsettings_menu_grid_display',
                'desc' => 'bloqs_fieldsettings_menu_grid_display_desc',
                'wide' => true,
                'fields' => [
                    'menu_grid_display' => [
                        'type' => 'yes_no',
                        'value' => (isset($fieldSettings['menu_grid_display']) ? $fieldSettings['menu_grid_display'] : ''),
                    ]
                ]
            ],
            [
                'title' => 'bloqs_fieldsettings_associateblocks',
                'desc' => 'bloqs_fieldsettings_associateblocks_desc',
                'wide' => true,
                'fields' => [
                    'blockdefinitions' => [
                        'type' => 'html',
                        'content' => $output,
                    ]
                ]
            ],
            [
                'title' => 'bloqs_fieldsettings_template_code',
                'desc' => 'bloqs_fieldsettings_template_code_desc',
                'wide' => true,
                'fields' => [
                    'template' => [
                        'type' => 'html',
                        'content' => $templateOutput,
                    ]
                ]
            ]
        ];

        return [
            'field_options_bloqs' => [
                'label' => 'field_options',
                'group' => 'bloqs',
                'settings' => $settings
            ]
        ];
    }

    /**
     * @param $selected
     * @param $all
     * @return array
     */
    protected function sortBlockDefinitions($selected, $all)
    {
        $return = [];
        $selectedIds = [];

        foreach ($selected as $blockDefinition) {
            $selectedIds[] = $blockDefinition->id;
            $blockDefinition->selected = true;
            $return[] = $blockDefinition;
        }

        foreach ($all as $blockDefinition) {
            if (in_array($blockDefinition->id, $selectedIds)) {
                continue;
            }

            $blockDefinition->selected = false;
            $return[] = $blockDefinition;
        }

        return $return;
    }

    public function post_save_settings($data)
    {
        $fieldId = $data['field_id'];

        $blockDefinitions = ee()->input->post('blockdefinitions');
        $adapter = new Adapter(ee());

        if ($blockDefinitions) {
            foreach ($blockDefinitions as $blockDefinitionId => $values) {
                if ($values['selected'] == '0') {
                    $adapter->disassociateBlockDefinitionWithField(
                        $fieldId,
                        $blockDefinitionId
                    );
                } else if ($values['selected'] == '1') {
                    $order = intval($values['order']);
                    $adapter->associateBlockDefinitionWithField(
                        $fieldId,
                        $blockDefinitionId,
                        $order
                    );
                }
            }
        }
    }
}
