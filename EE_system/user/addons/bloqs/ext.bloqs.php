<?php

use Basee\App;
use BoldMinded\Bloqs\Service\Setting;
use EEBlocks\Controller\HookExecutor;
use EEBlocks\Database\Adapter;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry;

if ( !defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Publisher Extension Class
 *
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

class Bloqs_ext
{
    /**
     * @var array
     */
    public $settings = [];

    /**
     * Add-on version
     * @var integer
     */
    public $version = BLOQS_VERSION;

    /**
     * Does the extension have its own settings?
     * @var string
     */
    public $settings_exist = 'n';

    public function core_boot()
    {
        if (REQ === 'CP') {
            $this->validateLicense();
        }
    }

    /**
     * @param ChannelEntry $channelEntry
     * @throws Exception
     */
    public function after_channel_entry_save(ChannelEntry $channelEntry)
    {
        $searchValues = ee()->session->cache('bloqs', 'searchValues');

        if (!$searchValues) {
            return;
        }

        $hookExecutor = new HookExecutor(ee());
        $hookExecutor->updateSearchValues($searchValues);
    }

    /**
     * @param ChannelEntry $channelEntry
     * @throws Exception
     */
    public function after_channel_entry_update(ChannelEntry $channelEntry)
    {
        $searchValues = ee()->session->cache('bloqs', 'searchValues');

        if (!$searchValues) {
            return;
        }

        $adapter = new Adapter(ee());
        $adapter->updateFieldData($searchValues['entryId'], $searchValues['fieldId'], $searchValues['fieldValue']);
    }

    /**
     * Since we don't have foreign key constraints and some installs may be using MyISAM, do things the hard way...
     *
     * @param ChannelEntry $channelEntry
     */
    public function after_channel_entry_delete(ChannelEntry $channelEntry)
    {
        $entryId = $channelEntry->getId();

        /** @var CI_DB_result $blocksQuery */
        $blocksQuery = ee()->db->where('entry_id', $entryId)->get('blocks_block');
        $blocks = [];

        foreach ($blocksQuery->result() as $row) {
            $blocks[] = $row->id;
        }

        if (!empty($blocks)) {
            ee()->db
                ->where_in('block_id', $blocks)
                ->delete('blocks_atom');

            ee()->db
                ->where_in('id', $blocks)
                ->delete('blocks_block');
        }
    }

    /**
     * @param $fieldName
     * @param $entry_ids
     * @param $depths
     * @param $sql
     * @return array
     * @throws Exception
     */
    public function relationships_query($fieldName, $entry_ids, $depths, $sql)
    {
        // Before we attempt to call the ee:LivePreview service lets make sure its available.
        $isLivePreviewAvailable = App::isFeatureAvailable('livePreview');

        // We only want to use this hook when previewing...
        if (!$isLivePreviewAvailable || !ee('LivePreview')->hasEntryData()) {
            return ee('db')->query($sql)->result_array();
        }

        // last_caller is not an existing property on the Extensions class, but we're making it one.
        ee()->extensions->last_caller = 'bloqs';

        $data = ee('LivePreview')->getEntryData();
        $result = [];

        /** @var \EllisLab\ExpressionEngine\Model\Channel\Channel $channel */
        $channel = ee('Model')->get('Channel', $data['channel_id'])->first();
        $allFields = $channel->getAllCustomFields();

        $bloqsFields = $allFields->filter(function($field) {
            return $field->field_type === 'bloqs';
        })->pluck('field_id');

        if (!$bloqsFields) {
            return $result;
        }

        $adapter = new Adapter(ee());

        foreach ($bloqsFields as $fieldId)
        {
            // Don't bother if we don't have the field, if it doesn't have the row data, or if it has no rows.
            if (!isset($data['field_id_' . $fieldId]) || empty($data['field_id_' . $fieldId])) {
                continue;
            }

            $columns = [];
            $blocks = $adapter->getBlockDefinitionsForField($fieldId);

            /** @var \EEBlocks\Model\BlockDefinition $block */
            foreach ($blocks as $block) {
                /** @var \EEBlocks\Model\AtomDefinition $atomDefinition */
                foreach ($block->getAtomDefinitions() as $atomDefinition) {
                    if ($atomDefinition->getType() === 'relationship') {
                        $columns[] = $atomDefinition->getId();
                    }
                }
            }

            $blockIdIterator = 1;

            if (is_array($data['field_id_' . $fieldId])) {
                foreach ($data['field_id_' . $fieldId] as $blockId => $block) {
                    if ($blockId === 'tree_order') {
                        continue;
                    }

                    foreach ($columns as $colId) {
                        if (isset($block['values']['col_id_' . $colId]['data'])) {
                            foreach ($block['values']['col_id_' . $colId]['data'] as $order => $id) {
                                if (!$id) {
                                    continue;
                                }
                                $result[] = [
                                    'L0_field' => $colId,
                                    'L0_grid_field_id' => $fieldId,
                                    'L0_grid_col_id' => $colId,
                                    'L0_grid_row_id' => $blockIdIterator,
                                    'L0_parent' => $blockIdIterator,
                                    'L0_id' => (int)$id,
                                    'order' => $order + 1,
                                ];
                            }
                        }
                    }

                    $blockIdIterator++;
                }
            }
        }

        return $result;
    }

    private function validateLicense()
    {
        $ping = new Basee\Ping('bloqs_last_ping', 2400);

        if ($ping->shouldPing()) {
            $ping->updateLastPing();

            /** @var Setting $setting */
            $setting = ee('bloqs:Setting');

            $license = new Basee\License('https://license.boldminded.com');
            $response = $license->checkLicense([
                'payload' => base64_encode(json_encode([
                    'a'   => 'Bloqs',
                    'api' => '1',
                    'b'   => BLOQS_BUILD_VERSION,
                    'd'   => ee()->config->item('base_url'),
                    'e'   => APP_VER,
                    'i'   => 1456,
                    'l'   => $setting->get('license'),
                    'p'   => phpversion(),
                    's'   => ee()->config->item('site_id'),
                    'v'   => BLOQS_VERSION,
                ]))
            ]);

            if (
                ($response !== null && isset($response['status'])) &&
                (!$setting->get('license') || $response['status'] === 'invalid') &&
                (!empty(ee()->uri->rsegments) && end(ee()->uri->rsegments) !== 'license')
            ) {
                ee('CP/Alert')
                    ->makeInline('shared-form')
                    ->asWarning()
                    ->withTitle('License is invalid.')
                    ->addToBody('Please enter a valid license. Your license is available at boldminded.com, or expressionengine.com.')
                    ->defer();

                ee()->functions->redirect(
                    ee('CP/URL')->make('addons/settings/bloqs/license')->compile()
                );
            }
        }
    }
}
