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

use \EEBlocks\Model\Block;
use \EEBlocks\Model\Atom;

/**
 * The context for a single block when being outputted by the TagController.
 */
class TagOutputBlockContext
{
    private $_previousContext;
    private $_currentBlock;
    private $_nextContext;
    private $_index;
    private $_total;
    private $_indexOfType;
    private $_totalOfType;
    private $_totalSiblings;
    private $_totalChildren;
    private $_parentShortName;
    private $_parentId;

    public function __construct(
        $currentBlock,
        $index,
        $total,
        $indexOfType,
        $totalOfType
    )
    {
        $this->_currentBlock  = $currentBlock;
        $this->_index         = $index;
        $this->_total         = $total;
        $this->_indexOfType   = $indexOfType;
        $this->_totalOfType   = $totalOfType;

        $this->_previousContext = null;
        $this->_nextContext = null;
    }

    /**
     * @return Block
     */
    public function getCurrentBlock()
    {
        return $this->_currentBlock;
    }

    /**
     * @return Block
     */
    public function getPreviousBlock()
    {
        /** @var TagOutputBlockContext $context */
        $context = $this->getPreviousContext();

        if ($context === null) {
            return null;
        }

        return $context->getCurrentBlock();
    }

    /**
     * @return int
     */
    public function getBlockId()
    {
        $currentBlock = $this->getCurrentBlock();

        return $currentBlock->id;
    }

    /**
     * @return int
     */
    public function getPreviousBlockId()
    {
        /** @var TagOutputBlockContext $context */
        $context = $this->getPreviousContext();

        if ($context === null) {
            return null;
        }

        return $context->getBlockId();
    }

    /**
     * @return int
     */
    public function getNextBlockId()
    {
        /** @var TagOutputBlockContext $context */
        $context = $this->getNextContext();

        if ($context === null) {
            return null;
        }

        return $context->getBlockId();
    }

    /**
     * Get the shortname for the associated block
     *
     * Provides a simple (and abstract) way to get the block's shortname, so
     * that the caller doesn't have to have a huge chain of property lookups
     * and function calls.
     *
     * @return string
     */
    public function getShortname()
    {
        return $this->_currentBlock->definition->shortname;
    }

    /**
     * @param $previousContext
     */
    public function setPreviousContext($previousContext)
    {
        $this->_previousContext = $previousContext;
    }

    /**
     * @return $this
     */
    public function getPreviousContext()
    {
        return $this->_previousContext;
    }

    /**
     * @param $nextContext
     */
    public function setNextContext($nextContext)
    {
        $this->_nextContext = $nextContext;
    }

    /**
     * @return $this
     */
    public function getNextContext()
    {
        return $this->_nextContext;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->_index + 1;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->_index;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->_total;
    }

    /**
     * @return int
     */
    public function getIndexOfType()
    {
        return $this->_indexOfType;
    }

    /**
     * @return int
     */
    public function getCountOfType()
    {
        return $this->_indexOfType + 1;
    }

    /**
     * @return int
     */
    public function getTotalOfType()
    {
        return $this->_totalOfType;
    }

    /**
     * @return mixed
     */
    public function getTotalSiblings()
    {
        return $this->_totalSiblings;
    }

    /**
     * @param mixed $totalSiblings
     */
    public function setTotalSiblings($totalSiblings)
    {
        $this->_totalSiblings = $totalSiblings;
    }

    /**
     * @return mixed
     */
    public function getTotalChildren()
    {
        return $this->_totalChildren;
    }

    /**
     * @param mixed $totalChildren
     */
    public function setTotalChildren($totalChildren)
    {
        $this->_totalChildren = $totalChildren;
    }

    /**
     * @return mixed
     */
    public function getParentShortName()
    {
        return $this->_parentShortName;
    }

    /**
     * @param mixed $parentShortName
     */
    public function setParentShortName($parentShortName)
    {
        $this->_parentShortName = $parentShortName;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->_parentId;
    }

    /**
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->_parentId = $parentId;
    }
}
