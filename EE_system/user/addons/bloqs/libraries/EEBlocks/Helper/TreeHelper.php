<?php

/**
 * @package     ExpressionEngine
 * @subpackage  Helpers
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

namespace EEBlocks\Helper;

use EEBlocks\Model\Block;

class TreeHelper
{
    /**
     * Array created from the nestable plugin
     *
     * @var array
     */
    private $treeData = [];

    /**
     * @var array
     */
    private $indexedBlocks = [];

    /**
     * @var array
     */
    private $childrenAndDescendants = [];

    /**
     * Given an array of Blocks from adapter->getBlocks()
     * from the database and turn it into a nestable collection
     * for the CP view file to render.
     *
     * @param array $blocks
     * @return array
     */
    public function buildBlockTree(array $blocks)
    {
        $tree = [];
        $nodes = [];

        // Create a lookup table first
        foreach ($blocks as $block) {
            $blockId = $block->getRevisionId() ?: $block->getId();
            $nodes[$blockId] = $block;
        }

        /** @var Block $block */
        foreach ($blocks as $block) {
            $parent = $block->getParentId();
            $blockId = $block->getRevisionId() ?: $block->getId();
            $node = $nodes[$blockId];

            if (isset($nodes[$parent])) {
                $nodes[$parent]->addChild($node);
            } else {
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * We aren't as concerned about updating the full nested set data.
     * The main concern is to update the parent id, so newly added blocks
     * have the correct parent_id assigned to it. The rest of the nested set
     * data is updated when the blocks are saved.
     *
     * @param int $colId
     * @param int $blockId
     * @return $this;
     */
    public function updateParentId($colId, $blockId)
    {
        $blockId = (int) $blockId;

        if (isset($this->treeData[$colId])) {
            $this->treeData[$blockId] = $this->treeData[$colId];
            unset($this->treeData[$colId]);
        }

        foreach ($this->treeData as $key => $data) {
            if ($data['parent_id'] === $colId) {
                $this->treeData[$key]['parent_id'] = (int) $blockId;
            }
        }

        return $this;
    }

    /**
     * Recursive function to flatten the block tree we get back from the Nestable jQuery plugin
     *
     * @param array $tree
     * @return $this
     */
    public function buildNestedSet(array $tree)
    {
        $order = 1;

        foreach ($tree as $block) {
            $this->treeData[$block['id']] = [
                'order' => $order,
                'parent_id' => ($block['parent_id'] !== '' ? $block['parent_id'] : 0),
                'depth' => $block['depth'],
                'lft' => $block['lft'],
                'rgt' => $block['rgt'],
            ];

            $order++;
        }

        return $this;
    }

    /**
     * Given an array of Blocks from adapter->getBlocks()
     * find the immediate parent of the requested block.
     *
     * @param array $blocks
     * @param int   $blockId
     * @return Block|null
     */
    public function findParent(array $blocks, $blockId)
    {
        $this->buildIndexedBlocks($blocks);

        if (!isset($this->indexedBlocks[$blockId])) {
            return null;
        }

        /** @var Block $node */
        $node = $this->indexedBlocks[$blockId];

        // Could be the root
        if (!isset($this->indexedBlocks[$node->getParentId()])) {
            return null;
        }

        return $this->indexedBlocks[$node->getParentId()];
    }

    /**
     * Given an array of Blocks from adapter->getBlocks()
     * find the immediate children of the requested block.
     * Will not return all descendants.
     *
     * @param array $blocks
     * @param int   $blockId
     * @return Block[]
     */
    public function findChildren(array $blocks, $blockId)
    {
        $children = [];
        $this->buildIndexedBlocks($blocks);

        if (!isset($this->indexedBlocks[$blockId])) {
            return $children;
        }

        /** @var Block $node */
        $node = $this->indexedBlocks[$blockId];

        /**
         * @var int $blockId
         * @var Block $block
         */
        foreach ($this->indexedBlocks as $blockId => $block) {
            if ($block->getDepth() === $node->getDepth() + 1 && $block->getParentId() === $node->getId()) {
                $children[] = $block;
            }
        }

        return $children;
    }

    /**
     * @param array $blocks
     * @param bool  $draftsOnly
     * @param bool  $recurse
     * @return array
     */
    public function findChildrenAndDescendants(array $blocks, $draftsOnly = true, $recurse = false)
    {
        if ($recurse) {
            $tree = $blocks;
        } else {
            $tree = $this->buildBlockTree($blocks);
        }

        /** @var Block $block */
        foreach ($tree as $block) {
            $children = $block->getChildren();

            if($draftsOnly && $block->isDraft()) {
                $this->childrenAndDescendants[] = $block->getId();

                if (count($children) > 0) {
                    $this->childrenAndDescendants = array_merge($this->childrenAndDescendants, array_column($children, 'id'));

                    $this->iterateChildren($children);
                }
            }

            if (count($children) > 0) {
                $this->findChildrenAndDescendants($children, true, true);
            }
        }

        return $this->childrenAndDescendants;
    }

    /**
     * @param array $children
     */
    private function iterateChildren(array $children)
    {
        /** @var Block $child */
        foreach ($children as $child) {
            $children = $child->getChildren();

            $this->childrenAndDescendants[] = $child->getId();

            if (count($children) > 0) {
                $this->iterateChildren($children);
            }
        }
    }

    /**
     * Given an array of Blocks from adapter->getBlocks()
     * find the siblings of the requested block.
     * Will not return all descendants.
     *
     * @param array $blocks
     * @param int   $blockId
     * @param bool $includeSelf
     * @return Block[]
     */
    public function findSiblings(array $blocks, $blockId, $includeSelf = true)
    {
        $siblings = [];
        $this->buildIndexedBlocks($blocks);

        if (!isset($this->indexedBlocks[$blockId])) {
            return $siblings;
        }

        /** @var Block $node */
        $node = $this->indexedBlocks[$blockId];

        /**
         * @var int $blockId
         * @var Block $block
         */
        foreach ($this->indexedBlocks as $blockId => $block) {
            if ($block->getDepth() === $node->getDepth() && $block->getParentId() === $node->getParentId()) {
                if (!$includeSelf && $block->getId() === $node->getId()) {
                    continue;
                }
                $siblings[] = $block;
            }
        }

        return $siblings;
    }

    /**
     * Given an array of Blocks from adapter->getBlocks()
     * find the immediate previous sibling of the requested block.
     *
     * @param array $blocks
     * @param int   $blockId
     * @return Block|null
     */
    public function findPreviousSibling(array $blocks, $blockId)
    {
        $siblings = $this->findSiblings($blocks, $blockId);

        if (empty($siblings) || $siblings === null) {
            return null;
        }

        // If the first one is the one we're asking for, there are no previous siblings.
        if ($siblings[0]->getId() === $blockId) {
            return null;
        }

        foreach ($siblings as $index => $sibling) {
            if ($sibling->getId() === $blockId) {
                return $siblings[$index-1];
            }
        }

        return null;
    }

    /**
     * @param array $blocks
     */
    private function buildIndexedBlocks(array $blocks)
    {
        if (!empty($this->indexedBlocks)) {
            return;
        }

        foreach ($blocks as $block) {
            $this->indexedBlocks[$block->id] = $block;
        }
    }

    /**
     * @return array
     */
    public function getTreeData()
    {
        return $this->treeData;
    }

    /**
     * @param $treeData
     * @return $this
     */
    public function setTreeData($treeData)
    {
        $this->treeData = $treeData;

        return $this;
    }
}
