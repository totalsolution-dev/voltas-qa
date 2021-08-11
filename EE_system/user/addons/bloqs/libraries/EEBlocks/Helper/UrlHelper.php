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

use Basee\App;
use EllisLab\ExpressionEngine\Model\Addon\Action;

class UrlHelper
{
    /**
     * Fetch an ACT id, mostly used in CP for Ajax requests.
     *
     * @param string $method
     * @param array $params
     * @param string $class
     *
     * @return string
     */
    public function getAction($method, $params = [], $class = 'Bloqs_mcp')
    {
        /** @var Action $actionModel */
        $action = ee('Model')->get('Action')
            ->filter('class', $class)
            ->filter('method', $method)
            ->first();

        $actionId = $action->action_id;

        if (!$actionId) {
            return '';
        } else {
            if (!empty($params)) {
                return $this->getSiteIndex() .'?ACT='. $actionId .'&'. http_build_query($params);
            } else {
                return $this->getSiteIndex() .'?ACT='. $actionId .'&site_id='. ee()->config->item('site_id');
            }
        }
    }

    public function getSiteIndex()
    {
        $site_index = ee()->config->item('site_index');
        $index_page = ee()->config->item('index_page');

        $index = ($site_index != '') ? $site_index : (($index_page != '') ? $index_page : '');

        if ($index != '' || substr($index, -1) !== '/') {
            $index .= '/';
        }

        if (isset(ee()->config->_global_vars['root_url'])) {
            $site_url = ee()->config->_global_vars['root_url'];
        } else {
            $site_url = $this->getSiteUrl();
        }

        if (substr($site_url, -1) !== '/') {
            $site_url .= '/';
        }

        return reduce_double_slashes($site_url . $index);
    }

    /**
     * Get the site_url
     * @return  string
     */
    public function getSiteUrl()
    {
        // If MSM isn't enabled, return as soon as possible with least resistance.
        if (ee()->config->item('multiple_sites_enabled') !== 'y') {
            return App::configSlashed('site_url');
        }

        $siteUrl = App::configSlashed('site_url', ee()->config->item('site_id'));

        return $siteUrl;
    }
}
