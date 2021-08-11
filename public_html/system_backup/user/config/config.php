<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['save_tmpl_files'] = 'y';
// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system_configuration_overrides.html

$config['app_version'] = '5.3.0';
$config['encryption_key'] = 'e37b5aa0002353eb1843083821b7b8db232d1f27';
$config['session_crypt_key'] = '01a5e9968f1bc3b75c882b70d5c2a5adad9794bc';
$config['database'] = array(
	'expressionengine' => array(
		'hostname' => 'localhost',
		'database' => 'twostaging_EE',
		'username' => 'EE_admin',
		'password' => 'X8kZNmaqLr9UZLt',
		'dbprefix' => 'exp_',
		'char_set' => 'utf8mb4',
		'dbcollat' => 'utf8mb4_unicode_ci',
		'port'     => ''
	),
);
$config['share_analytics'] = 'y';

// EOF