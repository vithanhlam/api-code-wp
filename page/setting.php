<?php
/*
Update: 11/06/2024
Note: Fix bảo mật cơ bản
*/
if (!defined('ABSPATH')) exit;
?>
<div class="wrapper wrap">
	<h2 class="title"><?php esc_html_e('CODE-WP API Kết nối với Android và iOS', 'api-code-wp'); ?></h2>
</div>
<div class="notice notice-warning is-dismissible">
	<p><?php esc_html_e('Cấu hình thông tin để đồng bộ dữ liệu với ứng dụng di động CODE-WP trên kho Apple và CHPlay', 'api-code-wp'); ?></p>
</div>

<?php


if (isset($_POST['codewp_api_for_android_ios_security_token'], $_POST['codewp_api_for_android_ios_token'], $_POST['codewp_api_for_android_ios_nonce'])) {



	if (wp_verify_nonce(wp_unslash(sanitize_text_field($_POST['codewp_api_for_android_ios_nonce'])), 'codewp_api_for_android_ios_action')) {

		$security_token = sanitize_text_field($_POST['codewp_api_for_android_ios_security_token']);
		$token = sanitize_text_field($_POST['codewp_api_for_android_ios_token']);

		update_option('codewp_api_for_android_ios_security_token', $security_token);
		update_option('codewp_api_for_android_ios_token', $token);

		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Cập nhật thành công', 'api-code-wp') . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Nonce verification failed.', 'api-code-wp') . '</p></div>';
	}
}

?>

<div class="wrapper wrap">
	<form method="post" action="">
		<?php wp_nonce_field('codewp_api_for_android_ios_action', 'codewp_api_for_android_ios_nonce'); ?>

		<table class="form-table" role="presentation">

			<tbody>
				<tr>
					<th scope="row"><label for="codewp_api_for_android_ios_alert"><?php esc_html_e('Mã thông báo', 'api-code-wp'); ?></label></th>
					<td><input name="codewp_api_for_android_ios_alert" type="text" id="api_nekobaka_alert" aria-describedby="tagline-description" value="<?php echo esc_html(get_option('codewp_api_for_android_ios_alert')); ?>" class="regular-text" placeholder="">
						<p>
							<?php echo esc_html_e('Nhận mã này tại ứng dụng CODE-WP phần cài đặt thông báo', 'api-code-wp'); ?>
						</p>
					</td>


				</tr>
				<tr>
					<th scope="row"><label for="codewp_api_for_android_ios_token"><?php esc_html_e('Khóa bảo mật', 'api-code-wp'); ?></label></th>
					<td><input name="codewp_api_for_android_ios_token" type="text" id="codewp_api_for_android_ios_token" aria-describedby="tagline-description" value="<?php echo esc_attr(get_option('codewp_api_for_android_ios_token')); ?>" class="regular-text">
						<p><?php esc_html_e('Bạn cần phải mua plugin API CODE-WP để nhận mã bảo mật ', 'api-code-wp'); ?> <a target="_blank" href="https://code-wp.com/app-code-wp/"><?php esc_html_e('tại đây', 'api-code-wp'); ?></a></p>
					</td>

				</tr>
				<tr>
					<th scope="row"><label for="codewp_api_for_android_ios_security_token"><?php esc_html_e('Mã bảo mật', 'api-code-wp'); ?></label></th>
					<td><input name="codewp_api_for_android_ios_security_token" type="text" id="codewp_api_for_android_ios_security_token" aria-describedby="tagline-description" value="<?php echo esc_attr(get_option('codewp_api_for_android_ios_security_token')); ?>" class="regular-text" placeholder="">
						<p><?php esc_html_e('Để thay đổi mã bảo mật truy cập', 'api-code-wp'); ?> <a target="_blank" href="https://code-wp.com/tai-khoan/orders/"><?php esc_html_e('vào đây','api-code-wp'); ?></a></p>


					</td>

				</tr>

			</tbody>
		</table>
		<div class="btn_submit"><?php submit_button(); ?></div>
	</form>
</div>