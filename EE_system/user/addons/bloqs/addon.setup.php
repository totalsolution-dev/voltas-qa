<?php
// Build: d67ffef0
/**
 * @package     ExpressionEngine
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

require_once 'vendor/autoload.php';

use EllisLab\ExpressionEngine\Core\Provider;

if (!class_exists('Bloqs_base')) {
    require_once(PATH_THIRD . 'bloqs/base.bloqs.php');
}

if (!defined('BLOQS_VERSION')) {
    define('BLOQS_VERSION', '4.2.1');
}

if (!defined('BLOQS_BUILD_VERSION')) {
    define('BLOQS_BUILD_VERSION', 'd67ffef0');
}

if (!defined('BLOQS_TRIAL')) {
    define('BLOQS_TRIAL', false);
}

return [
    'author'      => 'BoldMinded',
    'author_url'  => 'https://boldminded.com',
    'docs_url'    => 'https://boldminded.com/add-ons/bloqs',
    'name'        => 'Bloqs',
    'description' => 'A modular content add-on for ExpressionEngine',
    'version'     => BLOQS_VERSION,
    'namespace'   => 'BoldMinded\Bloqs',
    'settings_exist' => true,

    'services.singletons' => [
        'FormSecret' => function () {
            return new BoldMinded\Bloqs\Service\FormSecret();
        },
        'Setting' => function () {
            return new Basee\Setting('blocks_settings');
        },
        'Trial' => function ($provider) {
            /** @var Provider $provider */
            $setting = $provider->make('Setting');
            /** @var \BoldMinded\Bloqs\Service\Trial $trialService */
            $trialService = new BoldMinded\Bloqs\Service\Trial();
            $trialService
                ->setTrialEnabled(BLOQS_TRIAL)
                ->setInstalledDate($setting->get('installed_date'))
                ->setMessageTitle('Your free trial of Bloqs has expired and is disabled.')
                ->setMessageBody('Please go to the <a href="https://boldminded.com">https://boldminded.com</a> to purchase the full version.');

            return $trialService;
        },
    ]
];
