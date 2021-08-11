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
use EEBlocks\Database\Adapter;
use EEBlocks\Helper\TreeHelper;
use EEBlocks\Model\Atom;
use EEBlocks\Model\AtomDefinition;
use EEBlocks\Model\Block;

/**
 * A parser and outputter for the root tag of the Blocks fieldtype.
 *
 * This class is primarily used from Blocks_ft::replace_tag
 */
class TagController
{
    private $EE;
    private $_ftManager;
    private $_fieldId;
    private $_prefix;
    private $_adapter;
    private $_fieldSettings;

    /**
     * Create the controller
     *
     * @param object $ee The ExpressionEngine instance.
     * @param int $fieldId The database ID for the EE field itself.
     * @param \EEBlocks\Controller\FieldTypeManager $fieldTypeManager The
     *        object responsible for creating and loading field types.
     * @param Adapter $adapter
     */
    public function __construct($ee, $fieldId, $fieldTypeManager, $adapter, $fieldSettings)
    {
        $this->EE = $ee;
        $this->_prefix = 'blocks';
        $this->_fieldId = $fieldId;
        $this->_ftManager = $fieldTypeManager;
        $this->_adapter = $adapter;
        $this->_fieldSettings = $fieldSettings;
    }

    public function buildContexts($blocks)
    {
        $contexts = [];
        $totalsForBlockDefinitions = [];
        $indexForBlockDefinitions = [];
        $total = count($blocks);
        $index = 0;

        if ($this->isNestable()) {
            $treeHelper = new TreeHelper();
            $children = $treeHelper->findChildrenAndDescendants($blocks);

            $blocks = array_filter($blocks, function ($block) use ($children) {
                /** @var Block $block */
                return !(in_array($block->getId(), $children));
            });
        }

        /** @var Block $block */
        foreach ($blocks as $block) {
            if ($block->isDraft()) {
                continue;
            }

            $shortName = $block->definition->shortname;

            if (!isset($totalsForBlockDefinitions[$shortName])) {
                $totalsForBlockDefinitions[$shortName] = 0;
                $indexForBlockDefinitions[$shortName] = 0;
            }

            $totalsForBlockDefinitions[$shortName]++;
        }

        /** @var Block $block */
        foreach ($blocks as $block) {
            if ($block->isDraft()) {
                continue;
            }

            $shortName = $block->definition->shortname;

            $indexForBlockDefinition = $indexForBlockDefinitions[$shortName];

            $context = new TagOutputBlockContext(
                $block,
                $index,
                $total,
                $indexForBlockDefinition,
                $totalsForBlockDefinitions[$shortName]
            );

            $contexts[] = $context;

            $index++;
            $indexForBlockDefinitions[$shortName]++;
        }

        for ($i = 0; $i < count($contexts); $i++) {
            if (0 <= $i - 1) {
                $contexts[$i]->setPreviousContext($contexts[$i - 1]);
            }
            if ($i + 1 < count($contexts)) {
                $contexts[$i]->setNextContext($contexts[$i + 1]);
            }
        }

        return $contexts;
    }

    /**
     * @return bool
     */
    private function isNestable()
    {
        if (isset($this->_fieldSettings['nestable']) && $this->_fieldSettings['nestable'] === 'y') {
            return true;
        }

        return false;
    }

    /**
     * The primary entry point for the Blocks parser
     *
     * @param string $tagdata The parsed template that EE gives.
     * @param \EEBlocks\Model\Block[] $blocks The blocks that will be
     *        outputted.
     * @param array $channelRow Top-level row data that EE provides.
     *        Typically $this->row from the fieldtype.
     *
     * @return string
     */
    public function replace($tagdata, $blocks, $channelRow)
    {
        $output = '';

        $contexts = $this->buildContexts($blocks);

        $this->EE->session->cache['blockHistory'] = [];
        $this->EE->session->cache['blockVars'] = [];

        foreach ($contexts as $context)
        {
            $output .= $this->_renderBlockSections(
                $blocks,
                $tagdata,
                $context,
                $channelRow
            );
        }

        if ($this->isNestable()) {
            // If we have any open blocks still, reverse the history and close up shop!
            $blockHistory = $this->EE->session->cache['blockHistory'];

            if (!empty($blockHistory)) {
                $blockHistory = array_reverse($blockHistory);

                foreach ($blockHistory as $historyData) {
                    $output .= $this->_getAndParseCloseContent($historyData);

                    // Not necessary, but clean it up anyway.
                    unset($this->EE->session->cache['blockHistory'][$historyData['blockId']]);
                }
            }
        }

        if (
            isset($this->EE->session->cache['blockVarsCollection']) &&
            !empty($this->EE->session->cache['blockVarsCollection'])
        ) {
            $blockVars = $this->EE->session->cache['blockVarsCollection'];

            foreach ($blockVars as $blockId => $vars) {
                $replacement = 'bloqs:start:'. $blockId .' vars="' . htmlspecialchars(json_encode($vars), ENT_QUOTES) .'"';
                $output = str_replace('bloqs:start:'. $blockId, $replacement, $output);
            }
        }

        return $output;
    }

    /**
     * Display the total number of Blocks.
     *
     * @param $blocks
     * @param array $params Parameters given via the EE tag.
     * @return int
     */
    public function totalBlocks($blocks, $params)
    {
        if (isset($params['type'])) {
            $type = $params['type'];
            $types = explode('|', $type);
            $count = 0;
            /** @var Block $block */
            foreach ($blocks as $block) {
                $shortName = $block->getDefinition()->getShortName();
                if (in_array($shortName, $types)) {
                    $count++;
                }
            }
            return $count;
        } else {
            return count($blocks);
        }
    }

    /**
     * @param array $blocks
     * @param string $tagdata
     * @param TagOutputBlockContext $context
     * @param array $channelRow
     * @return string
     */
    protected function _renderBlockSections($blocks = [], $tagdata, TagOutputBlockContext $context, $channelRow = [])
    {
        $sections = $this->EE->api_channel_fields->get_pair_field($tagdata, $context->getShortname(), '');
        $output = '';

        //
        // There can be multiple sections.
        //
        // {block-field}
        //   {simple}
        //    <p>{content}</p>
        //   {/simple}
        //
        //   {simple}
        //   <div>Why would anybody do this?</div>
        //   {/simple}
        // {/block-field}
        //
        // So we need to run the process for each section.
        //
        foreach ($sections as $section)
        {
            // Then handle nesting, and if not nesting, strip any {close:*} tag pairs that may exist.
            if ($this->isNestable()) {
                /*
                 * Add to the context so vars are available:
                 * parent_id, parent_shortname
                 * count_siblings, count_children
                 * total_siblings, total_children
                 */
                $blockId = $context->getCurrentBlock()->getId();
                $treeHelper = new TreeHelper();

                $parent = $treeHelper->findParent($blocks, $blockId);
                $children = $treeHelper->findChildren($blocks, $blockId);
                $siblings = $treeHelper->findSiblings($blocks, $blockId);

                $parentId = ($parent !== null ? $parent->getId() : 0);
                $parentShortName = '';

                // Create some additional {blocks:X} variables
                $context->setTotalChildren(count($children));
                $context->setTotalSiblings(count($siblings));
                $context->setParentId($parentId);

                if (isset($this->EE->session->cache['blockHistory'][$parentId])) {
                    /** @var TagOutputBlockContext $parentContext */
                    $parentContext = $this->EE->session->cache['blockHistory'][$parentId]['currContext'];
                    $parentShortName = $parentContext->getShortname();
                }

                $context->setParentShortName($parentShortName);

                $this->EE->session->cache['blockHistory'][$blockId] = [
                    'blockId' => $blockId,
                    'currContext' => $context,
                    'closeContent' => null,
                    'closePair' => null,
                ];

                // Parse all atom fields and conditionals with updated contexts before we start removing
                // the closing chunks, which may also contain atom's and conditionals
                $tagdata = $this->_renderBlockSection(
                    $section[1],
                    $context,
                    $channelRow
                );

                $closeChunk = $this->EE->api_channel_fields->get_pair_field(
                    $tagdata,
                    $context->getShortname(),
                    'close:'
                );

                if (isset($closeChunk[0][3])) {
                    $chunkCloseContent = $closeChunk[0][1];
                    $chunkClosePair = $closeChunk[0][3];

                    $this->EE->session->cache['blockHistory'][$blockId]['closeContent'] = $chunkCloseContent;
                    $this->EE->session->cache['blockHistory'][$blockId]['closePair'] = $chunkClosePair;

                    // Remove the {close:X} tags, we don't render them yet.
                    $tagdata = str_replace($chunkClosePair, '', $tagdata);
                }

                /**
                 * @var Block $currBlock
                 * @var Block $prevBlock
                 */
                $currBlock = $context->getCurrentBlock();
                $prevBlock = $context->getPreviousBlock();

                $tagdata = $this->_getMarkerStart($blockId) . $tagdata;

                if ($prevBlock !== null) {
                    if ($currBlock->getDepth() === $prevBlock->getDepth()) {
                        $prevSibling = $treeHelper->findPreviousSibling($blocks, $currBlock->getId());
                        $historyData = $this->EE->session->cache['blockHistory'][$prevSibling->getId()];

                        $closeContent = $this->_getAndParseCloseContent($historyData);

                        $tagdata = $closeContent . $tagdata;

                        // Remove from the history b/c now its a closed block
                        unset($this->EE->session->cache['blockHistory'][$prevSibling->getId()]);
                    }

                    if ($currBlock->getDepth() < $prevBlock->getDepth()) {
                        $prevSibling = $treeHelper->findPreviousSibling($blocks, $currBlock->getId());

                        $history = $this->EE->session->cache['blockHistory'];
                        $key = array_search($prevSibling->getId(), array_keys($history));
                        $historyData = array_slice($history, $key, count($history), true);

                        // Don't close the current block yet, it'll get closed next time around.
                        foreach ($historyData as $data) {
                            if ($data['blockId'] == $currBlock->getId()) {
                                continue;
                            }

                            $closeContent = $this->_getAndParseCloseContent($data);

                            $tagdata = $closeContent . $tagdata;

                            // Remove from the history b/c now its a closed block
                            unset($this->EE->session->cache['blockHistory'][$data['blockId']]);
                        }
                    }
                }
            } else {
                // Parse all atom fields and conditionals
                $tagdata = $this->_renderBlockSection(
                    $section[1],
                    $context,
                    $channelRow
                );

                // If the field is not nestable it may still share other blocks with nested fields and those
                // template blocks will have close tags in them, so render the close tags immediately.
                $closeChunk = $this->EE->api_channel_fields->get_pair_field(
                    $tagdata,
                    $context->getShortname(),
                    'close:'
                );

                $blockId = $context->getCurrentBlock()->getId();

                if (isset($closeChunk[0][3])) {
                    $tagdata = str_replace($closeChunk[0][3], $closeChunk[0][1], $tagdata);
                }

                $tagdata = $this->_getMarkerStart($blockId) . $tagdata . $this->_getMarkerEnd($blockId);
            }

            $output .= $tagdata;
        }

        return $output;
    }

    /**
     * @param array $historyData
     * @return string
     */
    private function _getAndParseCloseContent($historyData)
    {
        if (!isset($historyData['currContext']) || !($historyData['currContext'] instanceof TagOutputBlockContext)) {
            $this->EE->lang->loadfile('bloqs');
            show_error(lang('bloqs_nesting_error_no_close_tags'));
        }

        // Get the special blocks variables and prepare to replace them.
        $blocksVariables = $this->getContextVariables($historyData['currContext']);

        $closeContent = $historyData['closeContent'];

        $blockVariableKeys = array_map(function($value) {
            return LD . $value . RD;
        }, array_keys($blocksVariables));

        $blockVariableValues = array_values($blocksVariables);

        $closeContent = str_replace($blockVariableKeys, $blockVariableValues, $closeContent);

        return $closeContent . $this->_getMarkerEnd($historyData['blockId']);
    }

    /**
     * @param int $blockId
     * @return string
     */
    private function _getMarkerStart($blockId)
    {
        return '{!-- bloqs:start:'. $blockId .' --}';
    }

    /**
     * @param int $blockId
     * @return string
     */
    private function _getMarkerEnd($blockId)
    {
        return '{!-- bloqs:end:'. $blockId .' --}';
    }

    /**
     * @param string $tagdata
     * @param TagOutputBlockContext $context
     * @param array $channelRow
     * @return mixed
     */
    protected function _renderBlockSection($tagdata, TagOutputBlockContext $context, $channelRow = [])
    {
        $fieldName = ''; // It's just nothing. Period.
        $entryId = $channelRow['entry_id'];

        $block = $context->getCurrentBlock();

        $tagdata = $this->parseRelationships($block, $tagdata);
        $tagdata = $this->_parseConditionals($tagdata, $context);
        $gridRow = $tagdata;

        // Gather the variables to parse
        if ( ! preg_match_all(
                "/".LD.'?[^\/]((?:(?:'.preg_quote($fieldName).'):?)+)\b([^}{]*)?'.RD."/",
                $tagdata,
                $matches,
                PREG_SET_ORDER)
            )
        {
            return $tagdata;
        }

        // Get the special blocks variables and prepare to replace them.
        $blocksVariables = $this->getContextVariables($context);

        foreach ($matches as $match) {
            $fieldTag = $match[0];
            $fieldShortName = $match[2];

            // Get tag name, modifier and params for this tag
            $field = App::parseVariableProperties($fieldShortName, $fieldName . ':');

            // Get any field pairs
            $fieldChunks = $this->EE->api_channel_fields->get_pair_field($tagdata, $field['field_name'], '');

            // Work through field pairs first
            foreach ($fieldChunks as $fieldChunkData) {
                list($modifier, $content, $params, $chunk) = $fieldChunkData;

                if (!isset($block->atoms[$field['field_name']])) {
                    continue;
                }

                $atom = $block->atoms[$field['field_name']];
                // Prepend the column ID with "blocks_" so it doesn't collide with any real grid columns.
                $columnId = 'col_id_' . $this->_prefix . '_' . $atom->definition->id;
                $channelRow[$columnId] = $atom->value;

                $replaceData = $this->replaceTag(
                    $atom->definition,
                    $this->_fieldId,
                    $entryId,
                    $block->id,
                    [
                        'modifier'  => $modifier,
                        'params'    => $params
                    ],
                    $channelRow,
                    $content
                );

                // Replace tag pair
                $gridRow = str_replace($chunk, $replaceData, $gridRow);
            }

            // Now handle any Blocks-specific variables.
            if (isset($blocksVariables[$fieldShortName])) {
                $replaceData = $blocksVariables[$fieldShortName];
            }

            // Now handle any single variables
            else if (isset($block->atoms[$field['field_name']]) &&
                strpos($gridRow, $fieldTag) !== false)
            {
                $atom = $block->atoms[$field['field_name']];
                $columnId = 'col_id_' . $this->_prefix . '_' . $atom->definition->id;
                $channelRow[$columnId] = $atom->value;

                $replaceData = $this->replaceTag(
                    $atom->definition,
                    $this->_fieldId,
                    $entryId,
                    $block->getId(),
                    $field,
                    $channelRow
                );
            } else {
                $replaceData = $fieldTag;
            }

            // Finally, do the replacement
            $gridRow = str_replace($fieldTag, $replaceData, $gridRow);

            // Nestable fields are allowed to have Atoms with a name prefix of block_var_, if this is the case, then
            // we'll save references to the values so they can be used in child blocks.
            // Reset the vars every root element if its nestable, otherwise reset every block
            if ($block->getDepth() === 0 || !$this->isNestable()) {
                $this->EE->session->cache['blockVars'] = [];
                $this->EE->session->cache['blockVarsById'] = [];
            }

            /** @var Atom $atom */
            foreach ($block->getAtoms() as $atomName => $atom) {
                if (substr($atomName,0 , 10) === 'block_var_') {
                    $this->EE->session->cache['blockVars'][$atomName] = $atom->getValue();
                    $this->EE->session->cache['blockVarsById'][$atom->getDefinition()->getId()] = $atom->getValue();
                }
            }

            // Save the block vars to a collection for this block by ID to be referenced later at the end of replace()
            if (isset($this->EE->session->cache['blockVarsById'])) {
                $this->EE->session->cache['blockVarsCollection'][$block->getId()] = $this->EE->session->cache['blockVarsById'];
            }

            if (isset($this->EE->session->cache['blockVars'][$fieldShortName])) {
                $vars = [
                    $fieldShortName => $this->EE->session->cache['blockVars'][$fieldShortName]
                ];

                $gridRow = ee()->functions->prep_conditionals($gridRow, [$vars]);
                $gridRow = ee()->TMPL->parse_variables($gridRow, [$vars]);
            }
        }

        return $gridRow;
    }

    /**
     * @param Block $block
     * @param $tagdata
     * @return null
     */
    protected function buildRelationshipParser(Block $block, $tagdata)
    {
        $this->EE->load->library('relationships_parser');
        $channel = $this->EE->session->cache('mod_channel', 'active');

        $relationships = array();

        foreach ($block->getAtoms() as $shortname => $atom)
        {
            $atomDefinition = $atom->definition;
            if ($atomDefinition->type == 'relationship')
            {
                $relationships[$atomDefinition->shortname] = $atomDefinition->id;
            }
        }

        try
        {
            if (!empty($relationships))
            {
                $relationshipParser = $this->EE->relationships_parser->create(
                    $channel->rfields,
                    array($block->id), // Um, only gonna parse this one?
                    $tagdata,
                    $relationships, // field_name => field_id
                    $this->_fieldId
                );
            }
            else
            {
                $relationshipParser = NULL;
            }
        }
        catch (\EE_Relationship_exception $e)
        {
            $relationshipParser = NULL;
        }

        return $relationshipParser;
    }

    protected function parseRelationships($block, $tagdata)
    {
        $relationshipParser = $this->buildRelationshipParser($block, $tagdata);

        $channel = $this->EE->session->cache('mod_channel', 'active');

        $rowId = $block->id;

        if ($relationshipParser)
        {
            try
            {
                $tagdata = $relationshipParser->parse($rowId, $tagdata, $channel);
            }
            catch (\EE_Relationship_exception $e)
            {
                $this->EE->TMPL->log_item($e->getMessage());
            }
        }

        return $tagdata;
    }

    /**
     * @param $tagdata
     * @param TagOutputBlockContext $context
     * @return mixed
     */
    protected function _parseConditionals($tagdata, TagOutputBlockContext $context)
    {
        // Compile conditional vars
        $cond = [];
        $cond = array_merge($cond, $this->getContextVariables($context));
        $block = $context->getCurrentBlock();

        // Map column names to their values in the DB
        foreach ($block->atoms as $atom) {
            $cond[$atom->definition->shortname] = $atom->value;
        }

        $tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);

        return $tagdata;
    }

    /**
     * @param TagOutputBlockContext $context
     * @return array
     */
    protected function getContextVariables(TagOutputBlockContext $context)
    {
        $vars = array();

        // Should all of this blocks:code go into the context?
        $vars['blocks:id'] = $context->getBlockId();
        $vars['blocks:shortname'] = $context->getShortname();
        $vars['blocks:index'] = $context->getIndex();
        $vars['blocks:count'] = $context->getCount();
        $vars['blocks:total_blocks'] = $context->getTotal();
        $vars['blocks:total_rows'] = $context->getTotal();
        $vars['blocks:index:of:type'] = $context->getIndexOfType();
        $vars['blocks:count:of:type'] = $context->getCountOfType();
        $vars['blocks:total_blocks:of:type'] = $context->getTotalOfType();
        $vars['blocks:total_rows:of:type'] = $context->getTotalOfType();
        $vars['blocks:previous:id'] = $context->getPreviousBlockId();
        $vars['blocks:previous:shortname'] = '';
        $vars['blocks:next:id'] = $context->getNextBlockId();
        $vars['blocks:next:shortname'] = '';

        $vars['blocks:parent:id'] = $context->getParentId();
        $vars['blocks:parent:shortname'] = $context->getParentShortName();
        $vars['blocks:children:total_blocks'] = $context->getTotalChildren();
        $vars['blocks:children:total_rows'] = $context->getTotalChildren();
        $vars['blocks:siblings:total_blocks'] = $context->getTotalSiblings();
        $vars['blocks:siblings:total_rows'] = $context->getTotalSiblings();

        /** @var TagOutputBlockContext $previousContext */
        $previousContext = $context->getPreviousContext();
        if (!is_null($previousContext)) {
            $vars['blocks:previous:shortname'] = $previousContext->getShortname();
        }

        /** @var TagOutputBlockContext $nextContext */
        $nextContext = $context->getNextContext();
        if (!is_null($nextContext)) {
            $vars['blocks:next:shortname'] = $nextContext->getShortname();
        }

        $allVars = '';
        foreach ($vars as $key => $value) {
            $allVars .= LD.$key.RD .' = '. $value ."\n";
        }

        $vars['blocks:all_vars'] = $allVars;

        return $vars;
    }

    protected function replaceTag(
        $atomDefinition,
        $fieldId,
        $entryId,
        $blockId,
        $field,
        $data,
        $content = false)
    {
        $colId = $this->_prefix . '_' . $atomDefinition->id;

        $fieldtype = $this->_ftManager->instantiateFieldtype(
            $atomDefinition,
            null,
            $blockId,
            $fieldId,
            $entryId
        );

        // Return the raw data if no fieldtype found
        if (!$fieldtype)
        {
            return $this->EE->typography->parse_type(
                $this->EE->functions->encode_ee_tags($data['col_id_' . $colId]));
        }

        // Determine the replace function to call based on presence of modifier
        $modifier = $field['modifier'];
        $parse_fnc = ($modifier) ? 'replace_' . $modifier : 'replace_tag';

        $fieldtype->initialize(array(
            'row' => $data,
            'content_id' => $entryId
        ));

        // Add row ID to settings array
        $fieldtype->setSetting('grid_row_id', $blockId);
        $fieldtype->setSetting('blocks_block_id', $blockId);

        $data = $fieldtype->preProcess($data['col_id_' . $colId]);
        $result = $fieldtype->replace(
            $modifier ? $modifier : NULL,
            $data,
            $field['params'],
            $content
        );

        return $result;
    }
}
