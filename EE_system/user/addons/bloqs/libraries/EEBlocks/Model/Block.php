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

namespace EEBlocks\Model;

class Block
{
    // Properties should be marked as private, but for backwards compatibility keeping as public

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $order;

    /**
     * @var int
     */
    public $depth;

    /**
     * @var int
     */
    public $lft;

    /**
     * @var int
     */
    public $rgt;

    /**
     * @var int
     */
    public $parent_id = 0;

    /**
     * @var BlockDefinition
     */
    public $definition;

    /**
     * @var Atom[] array
     */
    public $atoms = [];

    /**
     * @var array
     */
    public $children = [];

    /**
     * @var bool
     */
    public $deleted = false;

    /**
     * @var bool
     */
    public $isNew = false;

    /**
     * @var string
     */
    public $prefix = '';

    /**
     * @var string
     */
    public $revisionId;

    /**
     * @var bool
     */
    public $draft = false;

    /**
     * @param Block $child
     */
    public function addChild(Block $child)
    {
        $this->children[] = $child;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return count($this->children) > 0 ? true : false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @param int $depth
     * @return $this
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @param int $lft
     * @return $this
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @param int $rgt
     * @return $this
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @param int $parent_id
     * @return $this
     */
    public function setParentId($parent_id)
    {
        $this->parent_id = $parent_id;

        return $this;
    }

    /**
     * @return BlockDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param BlockDefinition $definition
     * @return $this
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return Atom[]
     */
    public function getAtoms()
    {
        return $this->atoms;
    }

    /**
     * @param Atom[] $atoms
     * @return $this
     */
    public function setAtoms($atoms)
    {
        $this->atoms = $atoms;

        return $this;
    }

    /**
     * @param $shortName
     * @param $definition
     * @return $this
     */
    public function addAtom($shortName, $definition)
    {
        $this->atoms[$shortName] = $definition;

        return $this;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param array $children
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     * @return $this
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        return $this->isNew;
    }

    /**
     * @param bool $isNew
     */
    public function setIsNew($isNew)
    {
        $this->isNew = $isNew;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getRevisionId()
    {
        return $this->revisionId;
    }

    /**
     * @param string $revisionId
     * @return $this
     */
    public function setRevisionId($revisionId)
    {
        $this->revisionId = $revisionId;

        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setTreeData($data)
    {
        $this->setOrder($data['order']);
        $this->setParentId($data['parent_id']);
        $this->setDepth($data['depth']);
        $this->setLft($data['lft']);
        $this->setRgt($data['rgt']);

        return $this;
    }

    /**
     * @return bool
     */
    public function isDraft()
    {
        return $this->draft;
    }

    /**
     * @param bool $draft
     */
    public function setDraft($draft)
    {
        $this->draft = $draft;

        return $this;
    }
}
