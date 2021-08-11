<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		http://addons.reinos.nl
 * @copyright 	Copyright (c) 2019 Reinos.nl Internet Media
 *
 * Copyright (c) 2018. Reinos.nl Internet Media
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
 * assignment of copyright to the original author (Rein de Vries and
 * Reinos.nl Internet Media) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

//contants
if ( ! defined('FREEBIE_NAME'))
{
	define('FREEBIE_NAME', 'Freebie');
	define('FREEBIE_CLASS', 'Freebie');
	define('FREEBIE_MAP', 'freebie');
	define('FREEBIE_VERSION', '3.2.2');
	define('FREEBIE_DESCRIPTION', 'Tell EE to ignore specific segments when routing URLs');
	define('FREEBIE_DOCS', 'http://docs.reinos.nl/freebie');
	define('FREEBIE_AUTHOR', 'Rein de Vries');
	define('FREEBIE_AUTHOR_URL', 'http://addons.reinos.nl/');
	define('FREEBIE_STATS_URL', 'http://reinos.nl/index.php/module_stats_api/v1');
}

//configs
$config['name'] = FREEBIE_NAME;
$config['version'] = FREEBIE_VERSION;

//load compat file
require_once(PATH_THIRD.FREEBIE_MAP.'/compat.php');

/* End of file config.php */
/* Location: /system/expressionengine/third_party/default/config.php */
