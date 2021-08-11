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

namespace EEBlocks\Database;

use Basee\App;
use Behat\Testwork\Hook\Hook;
use CI_DB_result;
use EEBlocks\Controller\HookExecutor;
use EEBlocks\Helper\TreeHelper;
use EEBlocks\Model\BlockDefinition;
use EEBlocks\Model\AtomDefinition;
use EEBlocks\Model\Block;
use EEBlocks\Model\Atom;

class Adapter
{
    /**
     * @var
     */
    private $EE;

    /**
     * @var HookExecutor
     */
    private $_hookExecutor;

    /**
     * @param $ee
     * @throws \Exception
     */
    function __construct($ee) {
        if (is_null($ee)) {
            throw new \Exception("ExpressionEngine object is required");
        }
        $this->EE = $ee;
        $this->_hookExecutor = new HookExecutor($ee);
    }

    /**
     * @param $entryId
     * @param $fieldId
     * @return array
     */
    public function getBlocks($entryId, $fieldId)
    {
        $collection = [];

        $queryString = <<<EOF
SELECT
    bd.id as bd_id,
    bd.shortname as bd_shortname,
    bd.name as bd_name,
    bd.instructions as bd_instructions,
    bd.settings as bd_settings,
    b.id as b_id,
    b.order as b_order,
    b.parent_id as b_parent_id,
    b.draft as b_draft,
    b.depth as b_depth,
    b.lft as b_lft,
    b.rgt as b_rgt,
    ad.id as ad_id,
    ad.shortname as ad_shortname,
    ad.name as ad_name,
    ad.instructions as ad_instructions,
    ad.order as ad_order,
    ad.type as ad_type,
    ad.settings as ad_settings,
    a.id as a_id,
    IFNULL(a.data, '') as a_data
FROM exp_blocks_block b
LEFT JOIN exp_blocks_blockdefinition bd
  ON b.blockdefinition_id = bd.id
LEFT JOIN exp_blocks_atomdefinition ad
  ON bd.id = ad.blockdefinition_id
LEFT JOIN exp_blocks_atom a
  ON a.block_id = b.id AND a.atomdefinition_id = ad.id
WHERE b.field_id = :fieldId AND entry_id = :entryId
ORDER BY b.order, ad.order
EOF;

        $queryReplacements = [
            'fieldId' => $fieldId,
            'entryId' => $entryId,
        ];

        // -------------------------------------------
        //  'blocks_get_blocks' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::GET_BLOCKS)) {
            $query = $this->_hookExecutor->getBlocks($queryString, $queryReplacements);
        } else {
            $query = $this->query($queryString, $queryReplacements);
        }
        //
        // -------------------------------------------

        $previousBlockId = NULL;
        $currentBlock = NULL;

        /** @var \CI_DB_result $query */
        foreach ($query->result() as $row) {
            if ($previousBlockId !== intval($row->b_id)) {
                $previousBlockId = intval($row->b_id);
                if (!is_null($currentBlock)) {
                    $collection[] = $currentBlock;
                }

                $blockDefinition = new BlockDefinition();
                $blockDefinition
                    ->setId(intval($row->bd_id))
                    ->setShortName($row->bd_shortname)
                    ->setName($row->bd_name)
                    ->setInstructions($row->bd_instructions)
                    ->setSettings(json_decode($row->bd_settings, true))
                ;

                $currentBlock = new Block();
                $currentBlock
                    ->setId(intval($row->b_id))
                    ->setOrder(intval($row->b_order))
                    ->setDepth(intval($row->b_depth))
                    ->setParentId(intval($row->b_parent_id))
                    ->setDraft(intval($row->b_draft))
                    ->setLft(intval($row->b_lft))
                    ->setRgt(intval($row->b_rgt))
                    ->setDefinition($blockDefinition)
                ;
            }

            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId(intval($row->ad_id))
                ->setShortName($row->ad_shortname)
                ->setName($row->ad_name)
                ->setInstructions($row->ad_instructions)
                ->setOrder(intval($row->ad_order))
                ->setType($row->ad_type)
                ->setSettings(json_decode($row->ad_settings, true))
            ;

            $atom = new Atom();
            $atom
                ->setId(intval($row->a_id))
                ->setValue($row->a_data)
                ->setDefinition($atomDefinition)
            ;

            $currentBlock->addAtom($atomDefinition->shortname, $atom);
        }

        if (!is_null($currentBlock)) {
            $collection[] = $currentBlock;
        }

        return $collection;
    }

    /**
     * Used primarily for EE's Live Preview, but given any array of blocks from $_POST data will
     * return a collection of Blocks to be passed to TagController.
     *
     * @param $blocks
     * @param $fieldId
     * @return array
     */
    public function getBlocksFromPost($blocks, $fieldId)
    {
        $i = 1;
        $collection = [];
        $blockDefinitions = $this->getBlockDefinitionsForField($fieldId);

        $treeHelper = new TreeHelper();
        $treeOrder = json_decode($blocks['tree_order'], true);

        if ($treeOrder) {
            $treeHelper->buildNestedSet($treeOrder);
        }

        $treeData = $treeHelper->getTreeData();

        foreach ($blocks as $blockName => $blockData) {
            // Make sure its a block, and not the hidden placeholder for new rows
            if (substr($blockName, 0, 6) !== 'blocks' || $blockName === 'blocks_new_block_0') {
                continue;
            }

            $blockDefinitionId = $blockData['blockdefinitionid'];
            /** @var BlockDefinition $blockDefinition */
            $blockDefinition = $this->findWhere($blockDefinitions, ['id' => $blockDefinitionId]);
            $atomDefinitions = $blockDefinition->getAtomDefinitions();
            $blockId = isset($blockData['id']) ? intval($blockData['id']) : $blockName;

            $currentBlock = new Block();
            $currentBlock
                ->setId($i)
                ->setOrder($i)
                ->setDefinition($blockDefinition)
            ;

            if (isset($treeData[$blockId])) {
                $currentBlock
                    ->setDepth($treeData[$blockId]['depth'])
                    ->setParentId($treeData[$blockId]['parent_id'])
                    ->setLft($treeData[$blockId]['lft'])
                    ->setRgt($treeData[$blockId]['rgt'])
                ;
            }

            $atomId = 1;

            foreach ($blockData['values'] as $columnId => $columnValue) {
                $atomDefinitionId = (int) str_replace('col_id_', '', $columnId);
                /** @var AtomDefinition $atomDefinition */
                $atomDefinition = $this->findWhere($atomDefinitions, ['id' => $atomDefinitionId]);

                $atom = new Atom();
                $atom
                    ->setId($atomId)
                    ->setValue($columnValue)
                    ->setDefinition($atomDefinition)
                ;

                $currentBlock->addAtom($atomDefinition->shortname, $atom);

                $atomId++;
            }

            $collection[] = $currentBlock;

            $i++;
        }

        return $collection;
    }

    /**
     * @param int $entryId
     * @param int $fieldId
     * @return array
     */
    public function getBlockIds($entryId, $fieldId)
    {
        return array_column($this->getBlocks($entryId, $fieldId), 'id');
    }

    /**
     * Given an array of objects, find the object matching the properties defined.
     * Similar to Underscore.js _findWhere()
     *
     * @param $list
     * @param $props
     * @return mixed
     */
    private function findWhere($list, $props)
    {
        $result = array_filter(
            $list,
            function ($e) use ($props) {
                $count = 0;
                foreach ($props as $key => $value) {
                    if ($value == $e->$key) {
                        $count += 1;
                    }
                    return $count == count($props);
                }
            }
        );

        $result = array_values($result);

        return isset($result[0]) ? $result[0] : null;
    }

    /**
     * @return array
     */
    public function getBlockDefinitions()
    {
        $collection = [];

        $queryString = <<<EOF
SELECT
    bd.id as bd_id,
    bd.shortname as bd_shortname,
    bd.name as bd_name,
    bd.instructions as bd_instructions,
    bd.preview_image as bd_preview_image,
    bd.settings as bd_settings,
    ad.id as ad_id,
    ad.shortname as ad_shortname,
    ad.name as ad_name,
    ad.instructions as ad_instructions,
    ad.order as ad_order,
    ad.type as ad_type,
    ad.settings as ad_settings
FROM exp_blocks_blockdefinition bd
LEFT JOIN exp_blocks_atomdefinition ad
  ON ad.blockdefinition_id = bd.id
ORDER BY bd.shortname, ad.order
EOF;

        $ee = $this->EE;

        /** @var \CI_DB_result $query */
        $query = $ee->db->query($queryString);

        $previousBlockId = null;
        $currentBlock = null;

        foreach ($query->result() as $row) {
            if ($previousBlockId !== intval($row->bd_id)) {
                $previousBlockId = intval($row->bd_id);
                if (!is_null($currentBlock)) {
                    $collection[] = $currentBlock;
                }

                $currentBlock = new BlockDefinition();
                $currentBlock
                    ->setId(intval($row->bd_id))
                    ->setShortName($row->bd_shortname)
                    ->setName($row->bd_name)
                    ->setInstructions($row->bd_instructions)
                    ->setPreviewImage($row->bd_preview_image)
                    ->setSettings(json_decode($row->bd_settings, true))
                ;
            }

            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId(intval($row->ad_id))
                ->setShortName($row->ad_shortname)
                ->setName($row->ad_name)
                ->setInstructions($row->ad_instructions)
                ->setOrder(intval($row->ad_order))
                ->setType($row->ad_type)
                ->setSettings(json_decode($row->ad_settings, true))
            ;

            $currentBlock->addAtomDefinition($row->ad_shortname, $atomDefinition);
        }

        if (!is_null($currentBlock)) {
            $collection[] = $currentBlock;
        }

        return $collection;
    }

    /**
     * @param $blockDefinitionId
     * @return BlockDefinition|null
     */
    public function getBlockDefinitionById($blockDefinitionId)
    {
        $blockDefinitions = $this->getBlockDefinitions();
        $blockDefinition = null;

        foreach ($blockDefinitions as $blockDefinitionCandidate) {
            if ($blockDefinitionCandidate->id === $blockDefinitionId) {
                $blockDefinition = $blockDefinitionCandidate;
                break;
            }
        }

        return $blockDefinition;
    }

    /**
     * @param $shortName
     * @return BlockDefinition|null
     */
    public function getBlockDefinitionByShortname($shortName)
    {
        $blockDefinitions = $this->getBlockDefinitions();
        $blockDefinition = null;

        foreach ($blockDefinitions as $blockDefinitionCandidate) {
            if ($blockDefinitionCandidate->shortname === $shortName) {
                $blockDefinition = $blockDefinitionCandidate;
                break;
            }
        }

        return $blockDefinition;
    }

    /**
     * @param $fieldId
     * @return array
     */
    public function getBlockDefinitionsForField($fieldId)
    {
        $collection = [];

        $queryString = <<<EOF
SELECT
    bd.id as bd_id,
    bd.shortname as bd_shortname,
    bd.name as bd_name,
    bd.instructions as bd_instructions,
    bd.preview_image as bd_preview_image,
    bd.settings as bd_settings,
    ad.id as ad_id,
    ad.shortname as ad_shortname,
    ad.name as ad_name,
    ad.instructions as ad_instructions,
    ad.order as ad_order,
    ad.type as ad_type,
    ad.settings as ad_settings
FROM exp_blocks_blockfieldusage bfu
LEFT JOIN exp_blocks_blockdefinition bd
  ON bd.id = bfu.blockdefinition_id
LEFT JOIN exp_blocks_atomdefinition ad
  ON ad.blockdefinition_id = bd.id
WHERE bfu.field_id = :fieldId
ORDER BY bfu.order, ad.order
EOF;

        /** @var \CI_DB_result $query */
        $query = $this->query($queryString, [
            'fieldId' => $fieldId
        ]);

        $previousBlockId = null;
        $currentBlockDefinition = null;

        foreach ($query->result() as $row) {
            if ($previousBlockId !== intval($row->bd_id)) {
                $previousBlockId = intval($row->bd_id);
                if (!is_null($currentBlockDefinition)) {
                    $collection[] = $currentBlockDefinition;
                }

                $currentBlockDefinition = new BlockDefinition();
                $currentBlockDefinition
                    ->setId(intval($row->bd_id))
                    ->setShortName($row->bd_shortname)
                    ->setName($row->bd_name)
                    ->setInstructions($row->bd_instructions)
                    ->setPreviewImage($row->bd_preview_image)
                    ->setSettings(json_decode($row->bd_settings, true))
                ;
            }

            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId(intval($row->ad_id))
                ->setShortName($row->ad_shortname)
                ->setName($row->ad_name)
                ->setInstructions($row->ad_instructions)
                ->setOrder(intval($row->ad_order))
                ->setType($row->ad_type)
                ->setSettings(json_decode($row->ad_settings, true))
            ;

            $currentBlockDefinition->addAtomDefinition($row->ad_shortname, $atomDefinition);
        }

        if (!is_null($currentBlockDefinition)) {
            $collection[] = $currentBlockDefinition;
        }

        return $collection;
    }

    /**
     * @param $fieldId
     * @param $blockDefinitionId
     * @param $order
     */
    public function associateBlockDefinitionWithField($fieldId, $blockDefinitionId, $order)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_blockfieldusage
    (field_id, blockdefinition_id, `order`)
VALUES
    (:fieldId, :blockDefinitionId, :order)
ON DUPLICATE KEY UPDATE
    `order` = :order
EOF;

        $this->query($queryString, [
            'fieldId' => $fieldId,
            'blockDefinitionId' => $blockDefinitionId,
            'order' => $order
        ]);
    }

    /**
     * @param $fieldId
     * @param $blockDefinitionId
     * @return mixed
     */
    public function disassociateBlockDefinitionWithField($fieldId, $blockDefinitionId)
    {
        $ee = $this->EE;

        $queryString1 = <<<EOF
DELETE a
FROM exp_blocks_atom a
LEFT JOIN exp_blocks_block b
ON a.block_id = b.id
WHERE field_id = :fieldId
  AND blockdefinition_id = :blockDefinitionId
EOF;

        $queryString2 = <<<EOF
DELETE FROM exp_blocks_block
WHERE field_id = :fieldId
  AND blockdefinition_id = :blockDefinitionId
EOF;

        $queryString3 = <<<EOF
DELETE FROM exp_blocks_blockfieldusage
WHERE field_id = :fieldId
  AND blockdefinition_id = :blockDefinitionId
EOF;

        $queryReplacements = [
            'fieldId' => $fieldId,
            'blockDefinitionId' => $blockDefinitionId
        ];

        // -------------------------------------------
        //  'blocks_disassociate' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::DISASSOCIATE)) {
            $ee->db->trans_start();
            $this->_hookExecutor->disassociate([$queryString1, $queryString2, $queryString3], $queryReplacements);
            $ee->db->trans_complete();
        } else {
            $ee->db->trans_start();
            $this->query($queryString1, $queryReplacements);
            $this->query($queryString2, $queryReplacements);
            $this->query($queryString3, $queryReplacements);
            $ee->db->trans_complete();
        }
        //
        // -------------------------------------------

        return $ee->db->trans_status();
    }

    /**
     * @param $blockId
     * @param $atomDefinitionId
     * @param $data
     */
    public function setAtomData($blockId, $atomDefinitionId, $data)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_atom
    (block_id, atomdefinition_id, data)
VALUES
    (:blockId, :atomDefinitionId, :data)
ON DUPLICATE KEY UPDATE
    data = :data
EOF;
        $ee = $this->EE;

        $queryReplacements = [
            'blockId' => $blockId,
            'atomDefinitionId' => $atomDefinitionId,
            'data' => $data
        ];

        // -------------------------------------------
        //  'blocks_set_atom_data' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::SET_ATOM_DATA)) {
            $this->_hookExecutor->setAtomData($queryString, $queryReplacements);
        } else {
            $this->query($queryString, $queryReplacements);
        }
        //
        // -------------------------------------------
    }

    /**
     * @param int   $blockDefinitionId
     * @param int   $entryId
     * @param int   $fieldId
     * @param int   $order
     * @param array $fieldData
     * @param array $treeData
     * @param int   $draft
     * @return int
     */
    public function createBlock($blockDefinitionId, $entryId, $fieldId, $order, $fieldData = [], $treeData = [], $draft = 0)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_block
    (blockdefinition_id, entry_id, field_id, `order`, parent_id, draft, depth, lft, rgt)
VALUES
    (:blockDefinitionId, :entryId, :fieldId, :order, :parentId, :draft, :depth, :lft, :rgt)
EOF;

        $parentId = isset($treeData['parent_id']) ? $treeData['parent_id'] : 0;
        $depth = isset($treeData['depth']) ? $treeData['depth'] : 0;
        $lft = isset($treeData['lft']) ? $treeData['lft'] : 0;
        $rgt = isset($treeData['rgt']) ? $treeData['rgt'] : 0;

        $queryReplacements = [
            'blockDefinitionId' => $blockDefinitionId,
            'entryId' => $entryId,
            'fieldId' => $fieldId,
            'order' => $order,
            'parentId' => $parentId,
            'depth' => $depth,
            'lft' => $lft,
            'rgt' => $rgt,
            'draft' => $draft,
        ];

        // -------------------------------------------
        //  'blocks_create_block' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::CREATE_BLOCK)) {
            $blockId = $this->_hookExecutor->createBlock(
                $queryString,
                $queryReplacements,
                $entryId,
                $fieldId,
                $fieldData
            );

            if ($blockId) {
                return $blockId;
            }
        } else {
            $this->query($queryString, $queryReplacements);
        }
        //
        // -------------------------------------------

        return $this->EE->db->insert_id();
    }

    /**
     * @param int   $blockId
     * @param int   $order
     * @param int   $entryId
     * @param int   $fieldId
     * @param array $fieldData
     * @param array $treeData
     * @param int   $draft
     * @return int|null
     */
    public function setBlockOrder($blockId, $order, $entryId = null, $fieldId = null, $fieldData = [], $treeData = [], $draft = 0)
    {
        $queryString = "UPDATE exp_blocks_block SET `order` = :order, `parent_id` = :parentId, `depth` = :depth, `lft` = :lft, `rgt` = :rgt, `draft` = :draft WHERE id = :id";

        $parentId = isset($treeData['parent_id']) ? $treeData['parent_id'] : 0;
        $depth = isset($treeData['depth']) ? $treeData['depth'] : 0;
        $lft = isset($treeData['lft']) ? $treeData['lft'] : 0;
        $rgt = isset($treeData['rgt']) ? $treeData['rgt'] : 0;

        $queryReplacements = [
            'order' => $order,
            'parentId' => $parentId,
            'depth' => $depth,
            'lft' => $lft,
            'rgt' => $rgt,
            'id' => $blockId,
            'draft' => $draft,
        ];

        // -------------------------------------------
        //  'blocks_set_block_order' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::SET_BLOCK_ORDER)) {
            return $this->_hookExecutor->setBlockOrder(
                $queryString,
                $queryReplacements,
                $entryId,
                $fieldId,
                $fieldData
            );
        }
        //
        // -------------------------------------------

        $this->query($queryString, $queryReplacements);

        return null;
    }

    /**
     * @param $blockId
     * @return mixed
     */
    public function deleteBlock($blockId)
    {
        $ee = $this->EE;

        $queryString1 = 'DELETE FROM exp_blocks_atom WHERE block_id = :blockId';
        $queryString2 = 'DELETE FROM exp_blocks_block WHERE id = :blockId';

        $queryReplacements = [
            'blockId' => $blockId
        ];

        // -------------------------------------------
        //  'blocks_delete_block' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::DELETE_BLOCK)) {
            $ee->db->trans_start();
            $this->_hookExecutor->deleteBlock([$queryString1, $queryString2], $queryReplacements);
            $ee->db->trans_complete();
        } else {
            $ee->db->trans_start();
            $this->query($queryString1, $queryReplacements);
            $this->query($queryString2, $queryReplacements);
            $ee->db->trans_complete();
        }
        //
        // -------------------------------------------

        return $ee->db->trans_status();
    }

    /**
     * @param int $entryId
     */
    public function deleteBlocksByEntry($entryId)
    {
        $ee = $this->EE;

        /** @var CI_DB_result $query */
        $query = $ee->db->where('entry_id', $entryId)->get('blocks_block');
        $blocks = array_column($query->result_array(), 'id');

        // -------------------------------------------
        //  'blocks_delete_blocks_by_entry' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::DELETE_BLOCKS_BY_ENTRY)) {
            $this->_hookExecutor->deleteBlocksByEntry($entryId);
        } else {
            if (!empty($blocks)) {
                $ee->db->where_in('id', $blocks)->delete('blocks_block');
                $ee->db->where_in('block_id', $blocks)->delete('blocks_atom');
            }
        }
        //
        // -------------------------------------------
    }

    /**
     * Create the core parts of a block definition. Note that this does not
     * create the atoms within a block definition.
     * @param BlockDefinition $blockDefinition
     */
    public function createBlockDefinition(BlockDefinition $blockDefinition)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_blockdefinition
    (name, shortname, instructions, preview_image, settings)
VALUES
    (:name, :shortName, :instructions, :preview_image, :settings)
EOF;
        $ee = $this->EE;

        $this->query($queryString, [
            'name' =>$blockDefinition->getName(),
            'shortName' => $blockDefinition->getShortName(),
            'instructions' => $blockDefinition->getInstructions(),
            'preview_image' => $blockDefinition->getPreviewImage(),
            'settings' => json_encode($blockDefinition->getSettings()),
        ]);

        $blockDefinition->id = $ee->db->insert_id();
    }

    /**
     * Update the core parts of a block definition. Note that this does not
     * update the atoms within a block definition.
     * @param BlockDefinition $blockDefinition
     */
    public function updateBlockDefinition(BlockDefinition $blockDefinition)
    {
        $queryString = <<<EOF
UPDATE exp_blocks_blockdefinition
SET
    name = :name,
    shortname = :shortName,
    instructions = :instructions,
    preview_image = :preview_image,
    settings = :settings
WHERE
    id = :id
EOF;

        $this->query($queryString, [
            'name' => $blockDefinition->getName(),
            'shortName' => $blockDefinition->getShortName(),
            'instructions' => $blockDefinition->getInstructions(),
            'preview_image' => $blockDefinition->getPreviewImage(),
            'settings' => json_encode($blockDefinition->getSettings()),
            'id' => $blockDefinition->getId(),
        ]);
    }

    /**
     * @param $blockDefinitionId
     * @return mixed
     */
    public function deleteBlockDefinition($blockDefinitionId)
    {
        $ee = $this->EE;

        $queries = [
<<<EOF
DELETE a
FROM exp_blocks_atom a
INNER JOIN exp_blocks_block b
  ON a.block_id = b.id
WHERE b.blockdefinition_id = :blockDefinitionId
EOF
, <<<EOF
DELETE
FROM exp_blocks_atomdefinition
WHERE blockdefinition_id = :blockDefinitionId
EOF
, <<<EOF
DELETE
FROM exp_blocks_block
WHERE blockdefinition_id = :blockDefinitionId
EOF
, <<<EOF
DELETE
FROM exp_blocks_blockfieldusage
WHERE blockdefinition_id = :blockDefinitionId
EOF
, <<<EOF
DELETE FROM exp_blocks_blockdefinition WHERE id = :blockDefinitionId
EOF
];

        $ee->db->trans_start();
        foreach ($queries as $queryString) {
            $this->query($queryString, [
                'blockDefinitionId' => $blockDefinitionId
            ]);
        }
        $ee->db->trans_complete();

        return $ee->db->trans_status();
    }

    /**
     * @param $blockDefinitionId
     * @param $atomDefinition
     */
    public function createAtomDefinition($blockDefinitionId, AtomDefinition $atomDefinition)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_atomdefinition
  (blockdefinition_id, shortname, name, instructions, `order`, type, settings)
VALUES
  (:blockDefinitionId, :shortName, :name, :instructions, :order, :type, :settings)
EOF;
        $ee = $this->EE;

        $this->query($queryString, [
            'blockDefinitionId' => $blockDefinitionId,
            'name' => $atomDefinition->getName(),
            'shortName' => $atomDefinition->getShortName(),
            'instructions' => $atomDefinition->getInstructions(),
            'order' => $atomDefinition->getOrder(),
            'type' => $atomDefinition->getType(),
            'settings' => json_encode($atomDefinition->getSettings())
        ]);

        $atomDefinition->setId($ee->db->insert_id());
    }

    /**
     * @param $atomDefinition
     */
    public function updateAtomDefinition(AtomDefinition $atomDefinition)
    {
        $queryString = <<<EOF
UPDATE exp_blocks_atomdefinition
SET
    name = :name,
    shortname = :shortName,
    instructions = :instructions,
    `order` = :order,
    type = :type,
    settings = :settings
WHERE
    id = :id
EOF;

        $this->query($queryString, [
            'name' => $atomDefinition->getName(),
            'shortName' => $atomDefinition->getShortName(),
            'instructions' => $atomDefinition->getInstructions(),
            'order' => $atomDefinition->getOrder(),
            'type' => $atomDefinition->getType(),
            'settings' => json_encode($atomDefinition->getSettings()),
            'id' => $atomDefinition->getId(),
        ]);
    }

    /**
     * @param $atomDefinitionId
     * @return mixed
     */
    public function deleteAtomDefinition($atomDefinitionId)
    {
        $ee = $this->EE;

        $queryString1 = <<<EOF
DELETE FROM exp_blocks_atom WHERE atomdefinition_id = :atomDefinitionId
EOF;
        $queryString2 = <<<EOF
DELETE FROM exp_blocks_atomdefinition WHERE id = :atomDefinitionId
EOF;

        $ee->db->trans_start();
        $this->query($queryString1, ['atomDefinitionId' => $atomDefinitionId]);
        $this->query($queryString2, ['atomDefinitionId' => $atomDefinitionId]);
        $ee->db->trans_complete();

        return $ee->db->trans_status();
    }

    /**
     * @param $entryId
     * @param $fieldId
     * @param $data
     */
    public function updateFieldData($entryId, $fieldId, $data)
    {
        $ee = $this->EE;

        $updates = [
            'field_id_' . $fieldId => $data
        ];

        // -------------------------------------------
        //  'blocks_update_field_data' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::UPDATE_FIELD_DATA)) {
            $this->_hookExecutor->updateFieldData($entryId, $fieldId, $data);
        } else {
            /** @var \EllisLab\ExpressionEngine\Service\Database\Query $db */
            $db = $ee->db;

            foreach ($updates as $fieldName => $updateData) {
                $tableName = App::getFieldTableName($fieldName);

                $db
                    ->where('entry_id', $entryId)
                    ->update($tableName, [
                        $fieldName => $updateData,
                    ]);
            }
        }
        //
        // -------------------------------------------
    }

    /**
     * @param $blockDefinitionId
     * @param $blockDefinitionName
     * @param $blockDefinitionShortName
     */
    public function copyBlockDefinition($blockDefinitionId, $blockDefinitionName, $blockDefinitionShortName)
    {
        $blockDefinition = $this->getBlockDefinitionById($blockDefinitionId);

        $blockDefinitionCopy = new BlockDefinition();
        $blockDefinitionCopy
            ->setId(null)
            ->setShortName($blockDefinitionShortName)
            ->setName($blockDefinitionName)
        ;

        $this->createBlockDefinition($blockDefinitionCopy);

        foreach ($blockDefinition->getAtomDefinitions() as $shortName => $atom) {
            $atom->id = null;
            $this->createAtomDefinition($blockDefinitionCopy->getId(), $atom);
        }
    }

    /**
     * Simple method to allow for named parameter binding.
     *
     * @param string $queryString
     * @param array $replacements
     * @return \CI_DB_result
     */
    public function query($queryString, $replacements)
    {
        $ee = $this->EE;

        foreach ($replacements as $field => $value) {
            $value = $ee->db->escape($value);
            $queryString = str_replace(':'.$field, $value,$queryString);
        }

        return $ee->db->query($queryString);
    }
}
