<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['require_cookie_consent'] = 'n';
$config['force_redirect'] = 'n';
$config['enable_devlog_alerts'] = 'y';
$config['cache_driver'] = 'file';
// START custom config ==============================
// $system_folder = "";

$protocol           = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "https://" : "http://";
$base_url           = $protocol . $_SERVER['HTTP_HOST'];
$base_path          = $_SERVER['DOCUMENT_ROOT'];

$images_folder      = "images";
$images_path        = $base_path . "/" . $images_folder;
$images_url         = $base_url . "/" . $images_folder;
// $config['license_number']       = ‘X’;
$config['multiple_sites_enabled'] = 'n';
$config['show_ee_news'] 		= 'n';
$config['cp_url']               = $base_url.'/admin.php';
$config['doc_url']              = 'http://expressionengine.com/user_guide/';
$config['is_system_on']         = 'y';
$config['allow_extensions']     = 'y';
$config['site_label']           = 'Voltas';
$config['site_name']            = 'Voltas.com';
$config['cookie_prefix']        = '';
$config['index_page']           = "";
$config['base_url']             = $base_url . "/";
$config['site_url']             = $config['base_url'];
$config['theme_folder_path']    = $base_path . "/themes/";
$config['theme_folder_url']     = $base_url . "/themes/";
$config['cp_theme']             = "default";
$config['emoticon_path']        = $images_url . "/smileys/";
$config['captcha_path']         = $images_path . "/captchas/";
$config['captcha_url']          = $images_url . "/captchas/";
$config['avatar_path']          = $images_path . "/avatars/";
$config['avatar_url']           = $images_url . "/avatars/";
$config['photo_path']           = $images_path . "/member_photos/";
$config['photo_url']            = $images_url . "/member_photos/";
$config['sig_img_path']         = $images_path . "/signature_attachments/";
$config['sig_img_url']          = $images_url . "/signature_attachments/";
$config['prv_msg_upload_path']  = $images_path . "/pm_attachments/";
$config['disable_all_tracking'] = 'y';
// ======
$config['show_profiler']        = 'y'; # y/n
$config['template_debugging']   = 'y'; # y/n
$config['save_tmpl_files']      = "y";  # y/n
$config['debug']                = "1"; # 0: no errors shown. 1: Errors shown to Super Admins. 2: Errors shown to everyone.
$config['enable_sql_caching']   = 'y'; # Cache Dynamic Channel Queries?
$config['email_debug']          = 'n'; # y/n


switch($_SERVER['HTTP_HOST']) {

    case 'www.voltas.com':
        $dbConnection = array (
			'hostname' => 'localhost',
			'database' => 'twostaging_EE',
			'username' => 'EE_admin',
			'password' => 'X8kZNmaqLr9UZLt',
			'dbprefix' => 'exp_',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_unicode_ci',
			'port'     => ''
			);
    break;
    case 'voltas.com':
        $dbConnection = array (
            'hostname' => 'localhost',
			'database' => 'twostaging_EE',
			'username' => 'EE_admin',
			'password' => 'X8kZNmaqLr9UZLt',
			'dbprefix' => 'exp_',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_unicode_ci',
			'port'     => ''
			);
    break;
    case 'devvoltas.voltasworld.com':
        $dbConnection = array (
            'hostname' => 'localhost',
			'database' => 'voltas_web',
			'username' => 'voltas_web_user',
			'password' => 'Password@123',
			'dbprefix' => 'exp_',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_unicode_ci',
			'port'     => ''
			);
    break;
    case '172.16.9.237':
        $dbConnection = array (
            'hostname' => 'localhost',
			'database' => 'voltas_web',
			'username' => 'voltas_web_user',
			'password' => 'Password@123',
			'dbprefix' => 'exp_',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_unicode_ci',
			'port'     => ''
			);
    break;
    case '219.65.116.183':
        $dbConnection = array (
            'hostname' => 'localhost',
			'database' => 'voltas_web',
			'username' => 'voltas_web_user',
			'password' => 'Password@123',
			'dbprefix' => 'exp_',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_unicode_ci',
			'port'     => ''
			);
    break;
    case 'qavoltas.voltasworld.com':
        $dbConnection = array (
            'hostname' => 'localhost',
			'database' => 'voltas_web',
			'username' => 'voltas_web_user',
			'password' => 'Password@123',
			'dbprefix' => 'exp_',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_unicode_ci',
			'port'     => ''
			);
    break;
    case '172.16.9.236':
        $dbConnection = array (
            'hostname' => 'localhost',
			'database' => 'voltas_web',
			'username' => 'voltas_web_user',
			'password' => 'Password@123',
			'dbprefix' => 'exp_',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_unicode_ci',
			'port'     => ''
			);
    break;
    case '219.65.116.182':
        $dbConnection = array (
            'hostname' => 'localhost',
			'database' => 'voltas_web',
			'username' => 'voltas_web_user',
			'password' => 'Password@123',
			'dbprefix' => 'exp_',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_unicode_ci',
			'port'     => ''
			);
    break;
    case 'voltasdev01.twostaging.com':
        $dbConnection = array (
            'hostname' => 'localhost',
			'database' => 'twostaging_EE',
			'username' => 'EE_admin',
			'password' => 'X8kZNmaqLr9UZLt',
			'dbprefix' => 'exp_',
			'char_set' => 'utf8mb4',
			'dbcollat' => 'utf8mb4_unicode_ci',
			'port'     => ''
			);
    break;

}

// END custom config ==============================



// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system_configuration_overrides.html

$config['app_version'] = '5.3.0';
$config['encryption_key'] = '8e90ec59d07c1b0aef4a4438b07a01d5569a0455';
$config['session_crypt_key'] = 'a9a6f34b753e3a3746d14e521b01574a7664ea9b';
$config['database'] = array (
	'expressionengine' => $dbConnection
  );
$config['share_analytics'] = 'n';

// EOF