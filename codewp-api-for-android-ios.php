<?php

/**
 * @link              https://code-wp.com
 * @since             1.1.9
 *
 * @wordpress-plugin
 * Plugin Name:       CODE-WP API kết nối ứng dụng Android - iOS
 * Plugin URI:        https://code-wp.com
 * Description:       Ứng dụng đồng bộ dữ liệu bài viết, sản phẩm, và nhiều thứ khác giúp bạn kết nối với điện thoại di động Android và iOS
 * Version:           1.1.9
 * Author:            Vi Văn Lâm
 * Author URI:        https://fb.com/vithanhlam
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       api-code-wp
 **/

if ( ! defined( 'ABSPATH' ) ) exit;

define('CODEWP_API_FOR_ANDROID_IOS_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define('CODEWP_API_FOR_ANDROID_IOS_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('CODEWP_API_FOR_ANDROID_IOS_VERSION', '1.1.9');

  
require CODEWP_API_FOR_ANDROID_IOS_PLUGIN_PATH.'/class-codewp-api-for-android-ios.php';
new CODEWP_API_FOR_ANDROID_IOS();


add_filter('plugin_action_links', 'codewp_api_for_android_ios_plugin_action_links', 10, 2);
function codewp_api_for_android_ios_plugin_action_links($links, $plugin_file) {
    if (plugin_basename(__FILE__) === $plugin_file) {
        $custom_link = '<a href="' . esc_url(admin_url('admin.php?page=codewp-api-for-android-ios')) . '">' . esc_html__('Cấu hình', 'api-code-wp') . '</a>';
        array_push($links, $custom_link);
    }
    return $links;
}


