<?php

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

namespace EEBlocks\Controller;

use Basee\App;
use EEBlocks\Helper\StringHelper;
use EEBlocks\Helper\TreeHelper;
use EEBlocks\Model\Atom;
use EEBlocks\Model\AtomDefinition;
use EEBlocks\Model\Block;
use EEBlocks\Model\BlockDefinition;
use \stdClass as stdClass;

class PublishController {

    private $EE;
    private $_fieldId;
    private $_fieldName;
    private $_adapter;
    private $_ftManager;
    private $_hookExecutor;
    private $_prefix;

    /**
     * Create the controller
     *
     * @param object $ee                                              The ExpressionEngine instance.
     * @param int $fieldId                                            The database ID for the EE field itself.
     * @param string $fieldName                                       The ExpressionEngine name for the field.
     * @param \EEBlocks\Database\Adapter $adapter                     The database adapter used
     *                                                                for querying from and saving to the database.
     * @param \EEBlocks\Controller\FieldTypeManager $fieldTypeManager The
     *                                                                object responsible for creating and loading field types.
     * @param HookExecutor $hookExecutor
     */
    public function __construct($ee, $fieldId, $fieldName, $adapter, $fieldTypeManager, $hookExecutor)
    {
        $this->EE = $ee;
        $this->_fieldId = $fieldId;
        $this->_fieldName = $fieldName;
        $this->_adapter = $adapter;
        $this->_ftManager = $fieldTypeManager;
        $this->_hookExecutor = $hookExecutor;
        $this->_prefix = 'blocks';
    }

    /**
     * Generate publish field HTML
     *
     * @param int $entryId            The Entry ID.
     * @param array $blockDefinitions The definitions for blocks associated with the field type.
     * @param array $blocks           The blocks for this specific entry/field.
     * @return array
     */
    public function displayField($entryId, $blockDefinitions, $blocks)
    {
        $vars = [];
        $vars['blocks'] = [];

        $treeHelper = new TreeHelper();
        $tree = $treeHelper->buildBlockTree($blocks);

        /** @var Block $block */
        foreach ($tree as $block) {
            $vars['blocks'][] = $this->renderBlock($entryId, $block);
        }

        $vars['blockdefinitions'] = $this->createBlockDefinitionsVars(
            $entryId,
            $blockDefinitions
        );

        $vars['fieldid'] = $this->_fieldId;

        return $vars;
    }

    /**
     * @param $control
     * @param string $class ('EEBlocks\Model\Atom'|'EEBlocks\Model\AtomDefinition')
     * @return bool
     */
    private function isValidAtomControl($control, $class = null)
    {
        if ($control !== false &&
            isset($control['html']) &&
            isset($control['atom']) &&
            ($control['atom'] instanceof $class)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param $entryId
     * @param Block $block
     * @return array
     */
    private function renderBlock($entryId, Block $block)
    {
        $prefix = $block->getPrefix();

        if (!$prefix) {
            $prefix = 'blocks_block_id_';
        }

        // Prevent duplicate prefixing
        if ($prefix && strpos($block->getId(), $prefix) !== false) {
            $prefix = '';
        }

        $names = $this->generateNames($prefix, $block->getId());

        if ($block->isNew()) {
            unset($names->deleted);
        }

        $block->deleted = "false";

        $blockVars = [
            'fieldnames' => $names,
            'visibility' => 'collapsed',
            'block' => $block,
            'controls' => []
        ];

        foreach ($block->atoms as $shortName => $atom) {
            $control = [
                'atom' => $atom
            ];

            if ($atom->getError()) {
                $blockVars['visibility'] = 'expanded';
            }

            $data = $block->atoms[$atom->definition->shortname]->value;

            $atomHtml = $this->publishAtom(
                $block->getId(),
                $atom->getDefinition(),
                $entryId,
                $prefix . $block->getId(),
                $data
            );

            $control['html'] = $atomHtml;

            if ($this->_hookExecutor->isActive(HookExecutor::DISPLAY_ATOM)) {
                $hookResponse = $this->_hookExecutor->displayAtom($entryId, $block->getDefinition(), $atom->getDefinition(), $control);

                // Hook can return false, which means the atom will not be added to the publish page at all.
                // If it doesn't return false it needs to be valid.
                if ($this->isValidAtomControl($hookResponse, Atom::class)) {
                    $blockVars['controls'][] = $hookResponse;
                }
            } else {
                $blockVars['controls'][] = $control;
            }
        }

        if ($block->hasChildren()) {
            foreach ($block->children as &$child) {
                $child = $this->renderBlock($entryId, $child);
            }
        }

        return $blockVars;
    }

    /**
     * Displaying a field after a validation error is not as simple as when we're getting
     * a Blocks collection from adapter->getBlocks(), as in displayField(). So emulate a collection
     * of blocks that we get from a database response, then pass it off to renderField so it renders
     * nested data correctly, and also calls the hook.
     *
     * @todo if a block has an invalid atom, then the user makes it valid, but also adds another
     *       block to the page while viewing the validated field, the newly added block isn't added
     *       to the database. In one case a parent/root block was not added, but its child was.
     *       Need to replicate and dig into this a bit more.
     *
     * @param int $entryId
     * @param $blockDefinitions
     * @param $data
     * @param bool $isRevision
     * @return array
     */
    public function displayValidatedField($entryId, $blockDefinitions, $data, $isRevision = false)
    {
        $vars = [];
        $vars['blocks'] = [];
        $blocks = [];

        $treeHelper = new TreeHelper();

        if (isset($data['tree_order']) && $data['tree_order']) {
            $treeHelper->buildNestedSet(json_decode($data['tree_order'], true));
        } else if (isset($_POST['field_id_'. $this->_fieldId]['tree_order']) && $_POST['field_id_'. $this->_fieldId]['tree_order']) {
            $postedTreeData = json_decode($_POST['field_id_'. $this->_fieldId]['tree_order'], true);
            $treeHelper->buildNestedSet($postedTreeData);
        }

        $treeData = $treeHelper->getTreeData();

        foreach ($data as $id => $blockData) {
            // A couple of keys we want to ignore because they are not valid blocks
            if (in_array($id, ['blocks_new_block_0', 'tree_order'])) {
                continue;
            }

            $block = new Block();
            $block->setOrder(isset($blockData['order']) ? $blockData['order'] : '-1');

            if ($isRevision || substr($id, 0, 17) === 'blocks_new_block_') {
                $block->setId($id);
                $block->setDeleted('false');
                $block->setIsNew(true);
                $block->setPrefix('blocks_new_block_');

                // If viewing a revision we need to build the blocks as if they're new so they'll save properly,
                // otherwise it'll think they're existing blocks and try to update the database, but the IDs may
                // not exist anymore, so it won't update anything.
                if ($isRevision) {
                    $block->setId(md5(rand()));
                    $revisionId = (int) str_replace('blocks_block_id_', '', $id);
                    $block->setRevisionId($revisionId);
                    if (isset($treeData[$revisionId])) {
                        $block->setTreeData($treeData[$revisionId]);
                    }
                } else {
                    if (isset($treeData[$id])) {
                        $block->setTreeData($treeData[$id]);
                    }
                }

            } else if (is_array($blockData) && !empty($blockData)) {
                $block->setId($blockData['id']);
                $block->setDeleted($blockData['deleted']);
                $block->setIsNew(false);
                $block->setPrefix('blocks_block_id_');

                if (isset($treeData[$blockData['id']])) {
                    $block->setTreeData($treeData[$blockData['id']]);
                }
            } else {
                // We don't have valid data to work with, so don't continue so we don't throw errors.
                continue;
            }

            $blockDefinition = $this->findBlockDefinition($blockDefinitions, intval($blockData['blockdefinitionid']));
            $block->setDefinition($blockDefinition);

            // There are chances an entire block won't be rendered if no values were saved in its fields.
            if ($isRevision && !isset($blockData['values'])) {
                continue;
            }

            foreach ($blockData['values'] as $valueId => $valueData) {
                if (substr($valueId, -6) === '_error') {
                    continue;
                }

                $atomDefinitionId = str_replace('col_id_', '', $valueId);

                // If the value ID is like col_id_blocks_10_something, we can ignore this.
                if (strpos($atomDefinitionId, '_')) {
                    continue;
                }

                $atomDefinition = $this->findAtomDefinition(
                    $block->getDefinition(),
                    intval($atomDefinitionId)
                );

                $atom = new Atom();
                $atom->setDefinition($atomDefinition);
                $atom->setValue($valueData);

                if (isset($blockData['values'][$valueId . '_error'])) {
                    $atom->setError($blockData['values'][$valueId . '_error']);
                }

                $block->addAtom($atomDefinition->getShortName(), $atom);
            }

            $blocks[] = $block;
        }

        $tree = $treeHelper->buildBlockTree($blocks);

        /** @var Block $block */
        foreach ($tree as $index => $block) {
            $vars['blocks'][] = $this->renderBlock($entryId, $block);
        }

        $vars['blockdefinitions'] = $this->createBlockDefinitionsVars($entryId, $blockDefinitions);
        $vars['fieldid'] = $this->_fieldId;

        return $vars;
    }

    /**
     * @param $prefix
     * @param $id
     * @return stdClass
     */
    protected function generateNames($prefix, $id)
    {
        $names = new stdClass();
        $names->baseName     = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . ']';
        $names->id           = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][id]';
        $names->definitionId = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][blockdefinitionid]';
        $names->order        = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][order]';
        $names->deleted      = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][deleted]';
        $names->draft        = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][draft]';

        return $names;
    }

    /**
     * @param $entryId
     * @param array $blockDefinitions
     * @return array
     */
    protected function createBlockDefinitionsVars($entryId, $blockDefinitions)
    {
        $blockDefinitionVars = [];

        if (!isset(ee()->file_field)) {
            ee()->load->library('file_field');
        }

        /** @var BlockDefinition $blockDefinition */
        foreach ($blockDefinitions as $blockDefinition)
        {
            $templateId = 'blocks-template-' . $this->_fieldId . '-' . $blockDefinition->getShortName();

            $names = new stdClass();
            $names->baseName          = 'field_id_' . $this->_fieldId . '[blocks_new_block_0]';
            $names->order             = 'field_id_' . $this->_fieldId . '[blocks_new_block_0][order]';
            $names->blockdefinitionid = 'field_id_' . $this->_fieldId . '[blocks_new_block_0][blockdefinitionid]';
            $names->draft             = 'field_id_' . $this->_fieldId . '[blocks_new_block_0][draft]';

            $definitionVars = [
                'fieldnames' => $names,
                'templateid' => $templateId,
                'name' => $blockDefinition->getName(),
                'shortname' => $blockDefinition->getShortName(),
                'instructions' => $blockDefinition->getInstructions(),
                'preview_image' => ee()->file_field->parse_string($blockDefinition->getPreviewImage()),
                'settings' => $blockDefinition->getSettings(),
                'blockdefinitionid' => $blockDefinition->getId(),
                'controls' => []
            ];

            foreach ($blockDefinition->getAtomDefinitions() as $shortName => $atomDefinition)
            {
                $control = [
                    'atom' => $atomDefinition
                ];

                $atomHtml = $this->publishAtom(null, $atomDefinition, $entryId, null, null);

                $control['html'] = $atomHtml;

                if ($this->_hookExecutor->isActive(HookExecutor::DISPLAY_ATOM)) {
                    $hookResponse = $this->_hookExecutor->displayAtom($entryId, $blockDefinition, $atomDefinition, $control);

                    // Hook can return false, which means the atom will not be added to the publish page at all.
                    // If it doesn't return false it needs to be valid.
                    if ($this->isValidAtomControl($hookResponse, AtomDefinition::class)) {
                        $definitionVars['controls'][] = $hookResponse;
                    }
                } else {
                    $definitionVars['controls'][] = $control;
                }
            }

            $blockDefinitionVars[] = $definitionVars;
        }

        return $blockDefinitionVars;
    }

    /**
     * Returns publish field HTML for a given atoms
     *
     * @param $blockId
     * @param AtomDefinition $atomDefinition Atom Definition.
     * @param int $entryId           Entry ID.
     * @param $rowId
     * @param $data
     * @return string
     */
    protected function publishAtom($blockId, AtomDefinition $atomDefinition, $entryId, $rowId, $data)
    {
        $fieldtype = $this->_ftManager->instantiateFieldtype(
            $atomDefinition,
            null,
            $blockId,
            $this->_fieldId,
            $entryId
        );

        if (is_null($fieldtype)) {
            return '<div class="alert inline issue">
                <p>Bloqs unable to create the requested atom: '. $atomDefinition->getName() .'</p>
            </div>';
        }

        if (is_null($data)) {
            $data = '';
        }

        // Set up the block ID.
        $fieldtype->setSetting('grid_row_id', $blockId);
        $fieldtype->setSetting('blocks_block_id', $blockId);

        // Call the fieldtype's field display method and capture the output
        $display_field = $fieldtype->displayField($data);

        if (is_null($rowId)) {
            $rowId = 'blocks_new_block_0';
        }

        // Because several new fields are React fields with encoded settings which are used to render the
        // final html output instead of having access to the html here like all other basic fieldtypes.
        if (App::isGteEE4() && preg_match('/-react="(.*?)" data-input-value="(.*?)"/', $display_field, $matches)) {
            $settings = json_decode(base64_decode($matches[1]));
            $fieldName = $matches[2];
            if (preg_match('/(.*?)\[(.*?)\](.*)/', $fieldName, $fieldNameMatches)) {
                $newFieldName = $this->_fieldName.'['.$rowId.'][values]['.$fieldNameMatches[1].']['. $fieldNameMatches[2] .']'. $fieldNameMatches[3];
            } else {
                $newFieldName = $this->_fieldName.'['.$rowId.'][values]['.$fieldName.']';
            }

            $settings->name = $newFieldName;
            $settings = base64_encode(json_encode($settings));

            $display_field = str_replace($matches[1], $settings, $display_field);
            $display_field = preg_replace('/data-input-value="(.*?)"/', 'data-input-value="'. $newFieldName .'"', $display_field);
        }

        // Return the publish field HTML with namespaced form field names
        $display_field = StringHelper::namespaceInputs('data-input-value',
            $display_field,
            '$1data-input-value="'.$this->_fieldName.'['.$rowId.'][values][$3]$4"',
            ['a']
        );

        return StringHelper::namespaceInputs(
            'name',
            $display_field,
            '$1name="'.$this->_fieldName.'['.$rowId.'][values][$3]$4"'
        );
    }

    /**
     * @param $data
     * @param $entryId
     * @return array|string
     */
    public function validate($data, $entryId)
    {
        $blockDefinitions = $this->_adapter->getBlockDefinitionsForField($this->_fieldId);
        $blocks = $this->_adapter->getBlocks($entryId, $this->_fieldId);

        return $this->processFieldData(
            $blocks,
            $blockDefinitions,
            'validate',
            $data,
            $entryId
        );
    }

    /**
     * @param $data
     * @param $entryId
     */
    public function save($data, $entryId)
    {
        // If importing entries via a module action, such as Datagrab, don't try to re-save
        // blocks if there is no data. Otherwise it will delete existing blocks.
        if (defined('REQ') && REQ == 'ACTION' && (empty($data) || !is_array($data))) {
            return;
        }

        $treeHelper = new TreeHelper();

        if (isset($data['tree_order'])) {
            $treeHelper->buildNestedSet($data['tree_order']);
        }

        // Get column data for the current field
        $blockDefinitions = $this->_adapter->getBlockDefinitionsForField($this->_fieldId);
        $blocks = $this->_adapter->getBlocks($entryId, $this->_fieldId);

        $data = $this->processFieldData(
            $blocks,
            $blockDefinitions,
            'save',
            $data,
            $entryId
        );

        $searchValues = array();

        if ($this->_hookExecutor->isActive(HookExecutor::PRE_SAVE_BLOCKS)) {
            $data = $this->_hookExecutor->preSaveBlocks($entryId, $this->_fieldId, $data);
        }

        foreach ($data['value'] as $colId => $blockData) {
            $blockDefinition = $this->findBlockDefinition(
                $blockDefinitions,
                intval($blockData['blockdefinitionid'])
            );

            if ($blockDefinition === null) {
                continue;
            }

            $order = isset($blockData['order']) ? (int) $blockData['order'] : 0;
            $draft = isset($blockData['draft']) ? (int) $blockData['draft'] : 0;

            // Always have the most up-to-date tree data
            $treeData = $treeHelper->getTreeData();
            $blockTreeData = [];

            if (substr($colId, 0, 16) === 'blocks_block_id_') {
                $blockId = intval(substr($colId, 16));

                // Nestable?
                if (isset($treeData[$blockId])) {
                    $blockTreeData = $treeData[$blockId];
                    $order = $blockTreeData['order'];
                }

                if ($blockData['deleted'] == 'true') {
                    $this->_adapter->deleteBlock($blockId);

                    /* @var FieldTypeWrapper $fieldtype */
                    foreach ($blockData['fieldtypes'] as $atomDefinitionId => $fieldtype) {
                        $atomDefinition = $this->findAtomDefinition($blockDefinition, (int) $atomDefinitionId);

                        $fieldtype->reinitialize($atomDefinition, $colId, $blockId, $this->_fieldId, $entryId);
                        $fieldtype->delete(array($blockId));
                    }

                    continue;
                }

                $result = $this->_adapter->setBlockOrder($blockId, $order, $entryId, $this->_fieldId, $data, $blockTreeData, $draft);
                if ($result) {
                    $blockId = $result;
                }
            } else {
                $blockDefinitionId = intval($blockData['blockdefinitionid']);

                // Nestable?
                if (isset($treeData[$colId])) {
                    $blockTreeData = $treeData[$colId];
                    $order = $blockTreeData['order'];
                }

                $blockId = $this->_adapter->createBlock(
                    $blockDefinitionId,
                    $entryId,
                    $this->_fieldId,
                    $order,
                    $data,
                    $blockTreeData,
                    $draft
                );

                // If multiple new blocks are added, ensure that any
                // children blocks have the correct parent_id
                if (substr($colId, 0, 11) === 'blocks_new_') {
                    $treeHelper->updateParentId($colId, $blockId);
                }
            }

            if ($this->_hookExecutor->isActive(HookExecutor::POST_SAVE_BLOCK)) {
                $blockData = $this->_hookExecutor->postSaveBlock($entryId, $this->_fieldId, $blockId, $blockData);
            }

            foreach ($blockData['values'] as $atomDefinitionId => $atomData) {
                $this->_adapter->setAtomData($blockId, $atomDefinitionId, $atomData);
            }

            // Run post_save on fieldtypes that need it.
            /* @var FieldTypeWrapper $fieldtype */
            foreach ($blockData['fieldtypes'] as $atomDefinitionId => $fieldtype) {
                /** @var AtomDefinition $atomDefinition */
                $atomDefinition = $this->findAtomDefinition($blockDefinition, (int) $atomDefinitionId);

                $value = $blockData['values'][$atomDefinitionId];

                $fieldtype->reinitialize($atomDefinition, $colId, $blockId, $this->_fieldId, $entryId);
                $fieldtype->postSave($value);

                if ($atomDefinition->isSearchable()) {
                    $searchValues[] = $value;
                }
            }
        }

        if (count($searchValues) > 0) {
            // Handled in ext by after_channel_entry_update()
            $this->EE->session->set_cache('bloqs', 'searchValues', [
                'entryId' => $entryId,
                'fieldId' => $this->_fieldId,
                'fieldValue' => encode_multi_field($searchValues)
            ]);
        }

        if ($this->_hookExecutor->isActive(HookExecutor::POST_SAVE)) {
            $blocks = $this->_adapter->getBlocks($entryId, $this->_fieldId);

            $context = [];
            $context['entry_id'] = $entryId;
            $context['field_id'] = $this->_fieldId;

            $this->_hookExecutor->postSave($blocks, $context);
        }
    }

    /**
     * @param $blocks
     * @param $blockId
     * @return null
     */
    protected function findBlock($blocks, $blockId)
    {
        // The could be a more efficient way to do this than a for loop. But
        // at this point, I don't think it's necessary. We'll have maybe 10
        // blocks max?
        foreach ($blocks as $block) {
            if ($block->id === $blockId) {
                return $block;
            }
        }
        return NULL;
    }

    /**
     * @param $blockDefinitions
     * @param $blockDefinitionId
     * @return null
     */
    protected function findBlockDefinition($blockDefinitions, $blockDefinitionId)
    {
        // Ditto my comment in findBlock about efficiency.
        foreach ($blockDefinitions as $blockDefinition) {
            if ($blockDefinition->id === $blockDefinitionId) {
                return $blockDefinition;
            }
        }
        return NULL;
    }

    /**
     * @param BlockDefinition $blockDefinition
     * @param $atomDefinitionId
     * @return AtomDefinition|null
     */
    protected function findAtomDefinition(BlockDefinition $blockDefinition, $atomDefinitionId)
    {
        foreach ($blockDefinition->getAtomDefinitions() as $shortName => $atomDefinition) {
            if ($atomDefinition->id === $atomDefinitionId) {
                return $atomDefinition;
            }
        }

        return null;
    }

    /**
     * Processes a POSTed Grid field for validation for saving
     *
     * The main point of the validation method is, of course, to validate the
     * data in each cell and collect any errors. But it also reconstructs
     * the post data in a way that display_field can take it if there is a
     * validation error. The validation routine also keeps track of any other
     * input fields and carries them through to the save method so that those
     * values are available to fieldtypes while they run their save methods.
     *
     * The save method takes the validated data and gives it to the fieldtype's
     * save method for further processing, in which the fieldtype can specify
     * other columns that need to be filled.
     *
     * @param   array   The blocks
     * @param   array   The block definitions
     * @param   string  Method to process, 'save' or 'validate'
     * @param   array   Grid publish form data
     * @param   int     Entry ID of entry being saved
     * @return  string|array
     */
    protected function processFieldData($blocks, $blockDefinitions, $method, $data, $entryId)
    {
        $this->EE->load->helper('custom_field_helper');

        // We'll store our final values and errors here
        $finalValues = array();
        $errors = FALSE;

        // $data = ' ' check is b/c of dumb things the Datagrab module does
        if (!$data || !is_array($data)) {
            return array('value' => $finalValues, 'error' => $errors);
        }

        // Make a copy of the files array so we can spoof it per field below
        $grid_field_name = $this->_fieldName;
        $files_backup = $_FILES;

        foreach ($data as $rowId => $blockData) {
            if ($rowId == 'blocks_new_block_0' || substr($rowId, 0, 7) !== 'blocks_') {
                // Don't save this. It's from the templates.
                continue;
            }

            $blockId = str_replace('blocks_block_id_', '', $rowId);
            /** @var Block $block */
            $block = $this->findBlock($blocks, intval($blockId));

            /** @var BlockDefinition $blockDefinition */
            if (is_null($block)) {
                $blockDefinitionId = intval($blockData['blockdefinitionid']);
                $blockDefinition = $this->findBlockDefinition($blockDefinitions, $blockDefinitionId);
            } else {
                $blockDefinition = $this->findBlockDefinition($blockDefinitions, $block->definition->id);
            }

            if (isset($blockData['deleted']) && $blockData['deleted'] == 'true') {
                $finalValues[$rowId]['deleted'] = $blockData['deleted'];
                $finalValues[$rowId]['blockdefinitionid'] = $blockData['blockdefinitionid'];

                /** @var AtomDefinition $atomDefinition */
                foreach ($blockDefinition->getAtomDefinitions() as $atomDefinition) {
                    $fieldtype = $this->_ftManager->instantiateFieldtype(
                        $atomDefinition,
                        $rowId,
                        $blockId,
                        $this->_fieldId,
                        $entryId
                    );

                    $finalValues[$rowId]['fieldtypes'][$atomDefinition->getId()] = $fieldtype;
                }

                continue;
            } else {
                $finalValues[$rowId]['deleted'] = 'false';
            }

            if (isset($blockData['values'])) {
                $row = $blockData['values'];
            } else {
                $row = [];
            }

            $finalValues[$rowId]['id'] = $blockId;
            $finalValues[$rowId]['order'] = $blockData['order'];
            $finalValues[$rowId]['blockdefinitionid'] = $blockData['blockdefinitionid'];
            $finalValues[$rowId]['draft'] = $blockData['draft'];

            /** @var AtomDefinition $atomDefinition */
            foreach ($blockDefinition->getAtomDefinitions() as $atomDefinition) {
                $atom_id = 'col_id_'.$atomDefinition->getId();

                // Handle empty data for default input name
                if ( ! isset($row[$atom_id])) {
                    $row[$atom_id] = null;
                }

                // Assign any other input fields to POST data for normal access
                foreach ($row as $key => $value) {
                    $_POST[$key] = $value;

                    // If we're validating, keep these extra values around so
                    // fieldtypes can access them on save
                    if ($method == 'validate' && ! isset($finalValues[$rowId]['values'][$key])) {
                        $finalValues[$rowId]['values'][$key] = $value;
                    }
                }

                $fieldtype = $this->_ftManager->instantiateFieldtype(
                    $atomDefinition,
                    $rowId,
                    $blockId,
                    $this->_fieldId,
                    $entryId
                );

                // Pass Grid row ID to fieldtype if it's an existing row
                if (strpos($rowId, 'blocks_block_id_') !== false) {
                    $fieldtype->setSetting('grid_row_id', $blockId);
                    $fieldtype->setSetting('blocks_block_id', $blockId);
                }

                // Inside Blocks our files arrays end up being deeply nested.
                // Since the fields access these arrays directly, we set the
                // FILES array to what is expected by the field for each
                // iteration.
                $_FILES = [];

                if (isset($files_backup[$grid_field_name])) {
                    $newFiles = [];

                    foreach ($files_backup[$grid_field_name] as $files_key => $value) {
                        if (isset($value[$rowId]['values'][$atom_id])) {
                            $newFiles[$files_key] = $value[$rowId]['values'][$atom_id];
                        }
                    }

                    $_FILES[$atom_id] = $newFiles;
                }

                // For validation, gather errors and validated data
                if ($method == 'validate') {
                    //run the fieldtypes validate method
                    $result = $fieldtype->validate($row[$atom_id]);

                    $error = $result;

                    // First, assign the row data as the final value
                    $value = $row[$atom_id];

                    // Here we extract possible $value and $error variables to
                    // overwrite the assumptions we've made, this is a chance for
                    // fieldtypes to correct input data or show an error message
                    if (is_array($result)) {
                        if (isset($result['value'])) {
                            $value = $result['value'];
                        }
                        if (isset($result['error'])) {
                            $error = $result['error'];
                        }
                    }

                    // Assign the final value to the array
                    $finalValues[$rowId]['values'][$atom_id] = $value;

                    // If column is required and the value from validation is empty,
                    // throw an error, except if the value is 0 because that can be
                    // a legitimate data entry
                    if (isset($atomDefinition->settings['col_required'])
                        && $atomDefinition->settings['col_required'] == 'y'
                        && empty($value)
                        && $value !== 0
                        && $value !== '0'
                    ) {
                        $error = lang('bloqs_field_required');
                    }

                    // Is this AJAX validation? If so, just return the result for the field we're validating
                    // Check for $this->EE global function b/c this is called in a PHPUnit test.
                    if (function_exists('ee') && $this->EE->input->is_ajax_request() && $field = $this->EE->input->post('ee_fv_field')) {
                        if ($field == 'field_id_'.$this->_fieldId.'['.$rowId.'][values]['.$atom_id.']') {
                            return $error;
                        }
                    }

                    // If there's an error, assign the old row data back so the
                    // user can see the error, and set the error message
                    if (is_string($error) && ! empty($error)) {
                        $finalValues[$rowId]['values'][$atom_id] = $row[$atom_id];
                        $finalValues[$rowId]['values'][$atom_id.'_error'] = $error;
                        $errors = lang('bloqs_validation_error');
                    }
                }
                // 'save' method
                elseif ($method == 'save')
                {
                    $result = $fieldtype->save($row[$atom_id]);

                    // Flatten array
                    if (is_array($result)) {
                        $result = \encode_multi_field($result);
                    }

                    $finalValues[$rowId]['fieldtypes'][$atomDefinition->id] = $fieldtype;
                    $finalValues[$rowId]['values'][$atomDefinition->id] = $result;

                    if (is_null($block)) {
                        $finalValues[$rowId]['blockdefinitionid'] = $blockData['blockdefinitionid'];
                    }
                }

                # BB: WHAT?
                // Remove previous input fields from POST
                foreach ($row as $key => $value) {
                    unset($_POST[$key]);
                }
            }
        }

        // reset $_FILES in case it's used in other code
        $_FILES = $files_backup;

        return [
            'value' => $finalValues,
            'error' => $errors
        ];
    }
}
