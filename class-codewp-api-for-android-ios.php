<?php
/* 
Xử lý API 
***************
Laste Update : 15/07/2024
Firt Update : 22/12/2022
Author : Vi Văn Lâm
*/
if ( ! defined( 'ABSPATH' ) ) exit;
class CODEWP_API_FOR_ANDROID_IOS {
	
	public function __construct() {
		$this->add_action_codewp_api_for_android_ios();
		
	}
	
	
	
	public function add_action_codewp_api_for_android_ios() {
		$api_function = array(
			'get_category' => "GET",
			'get_posts' => "GET",
			'get_meta_value' => "GET",
			'get_order' => "GET",
			'get_pages' => "GET",
			'get_products' => "GET",
			'create_page' => "POST",
			'create_post' => "POST",
			'create_product' => "POST",
			'update_product' => "POST",
			'upload_and_remove_gallery' => "POST",
			'update_order_status' => "POST",
			'get_contact_form_7' => "GET",
			'search_item' => "GET",
			'update_category' => "POST",
			'create_category' => "POST",
			'create_token_app' => "POST",
			'get_attributes_product' => "GET",
			'create_attributes_product' => "POST",
			'update_attributes_product' => "POST",
			'get_variation' => "GET",
			'create_product_variable' => "POST",
			'update_product_variable' => "POST",
			'get_products_variation' => "GET",
			'get_analytics' => "GET",
			'update_post' => "POST",
			'get_notes' => "GET",
			'update_notes' => "POST",
			'move_trash' => "DELETE",
			'get_contents' => "GET",
			'update_page' => "POST",
			'update_seo' => "POST",
			'get_seo_data' => "GET",
			'user_login' => "POST",
			'post_content' => "POST",
			'get_content' => "GET",
			'upload_image_app' => "POST",
			'update_post_category' => "POST",
			'wp_editor' => "GET",
		);

		foreach ($api_function as $key => $item) {
			add_action('rest_api_init', function () use ($key, $item) {
				register_rest_route('codewp-api-for-android-ios/v1', '/' . $key . '/', array(
					'methods' => $item,
					'callback' => array($this, $key),
					'permission_callback' => array($this, 'verify_token'),
				));
			});
		}

		
		add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
		add_action('woocommerce_thankyou', array($this, 'send_notifi_app'));
		add_action("wpcf7_before_send_mail", array($this, 'send_notifi_contact_form_7'));

	}
	public function verify_token() {
		$codewp_api_for_android_ios_token = get_option('codewp_api_for_android_ios_security_token');
		$token = isset($_SERVER['HTTP_AUTHORIZATION']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_AUTHORIZATION'])) : '';


		if (esc_html($token) !== 'Bearer ' . esc_html($codewp_api_for_android_ios_token)) {
			return false;
		}

		return true;
	}

	///////////////////////////////////////// Version 1.1.5

	public function get_check_taxonomy($post_id,$term_id,$taxonomy) {
		$product_cat = wp_get_post_terms($post_id, $taxonomy);
		foreach($product_cat as $product_cat_item) {
			if($product_cat_item->term_id == $term_id) {
				return 'selected';
			}
		}
	}

	public function display_plugin_setup_page() {
        include_once CODEWP_API_FOR_ANDROID_IOS_PLUGIN_PATH.'page/setting.php';
    }


	public function add_plugin_admin_menu()  {
		$slug = 'codewp-api-for-android-ios';
        add_menu_page('API CODE-WP', 'API CODE-WP', 'manage_options', esc_html($slug), array($this, 'display_plugin_setup_page'), '', '10');
	
    }

	public function upload_image_app(\WP_REST_Request $param) {
		$post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;

		if (!isset($_FILES['file']) || !isset($_FILES['file']['name'])) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Lỗi khi tải ảnh lên', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		$file = $_FILES['file'];
		$upload_overrides = array('test_form' => false);
		$movefile = wp_handle_upload($file, $upload_overrides);

		if ($movefile && !isset($movefile['error'])) {
			$filename = $movefile['file'];
			$fileurl = $movefile['url'];
			$attachment = array(
				'guid'           => $fileurl,
				'post_mime_type' => $movefile['type'], // Changed from $file['type'] to $movefile['type']
				'post_title'     => sanitize_file_name($file['name']),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_parent'    => $post_id 
			);
			$attach_id = wp_insert_attachment($attachment, $filename);
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata($attach_id, $filename);
			wp_update_attachment_metadata($attach_id, $attach_data);

			$data = array(
				'id' => $attach_id,
				'url' => $fileurl,
			);

			$response = array(
				'status' => 'success',
				'message' => esc_html__('Upload ảnh thành công', 'api-code-wp'),
				'data' => $data,
			);
			return new WP_REST_Response($response, 200);
		} else {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Lỗi khi tải ảnh lên', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
	}



	public function attach_product_thumbnail($post_id, $uploadedfile, $flag){
		if (!function_exists('wp_handle_upload')) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		}

		$upload_overrides = array('test_form' => false);
		$movefile = wp_handle_upload($uploadedfile, $upload_overrides);

		if ($movefile && !isset($movefile['error'])) {
			$file = $movefile['file'];
			$url = $movefile['url'];
			$type = $movefile['type'];

			$attachment = array(
				'post_mime_type' => $type,
				'post_title' => sanitize_file_name($file),
				'post_content' => '',
				'post_status' => 'inherit'
			);

			$attach_id = wp_insert_attachment($attachment, $file, $post_id);
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata($attach_id, $file);
			wp_update_attachment_metadata($attach_id, $attach_data);

			if($flag == 0){
				set_post_thumbnail($post_id, $attach_id);
			}
			if($flag == 1){
				$attach_id_array = get_post_meta($post_id,'_product_image_gallery', true);
				$attach_id_array .= ','.$attach_id;
				update_post_meta($post_id,'_product_image_gallery',$attach_id_array);
			}
			if($flag == 2){      
				$product = wc_get_product($post_id);
				$gallery_ids = $product->get_gallery_image_ids();
				$gallery_ids[] = $attach_id;
				$product->set_gallery_image_ids($gallery_ids);
				$product->save();
			}
		} else {
			echo esc_html($movefile['error']);
		}
	}
	public function get_content(\WP_REST_Request $param) {
		$params = $param->get_params();
		$post_id = isset($params['id']) ? absint($params['id']) : 0;
		$post_type = isset($params['post_type']) ? sanitize_text_field($params['post_type']) : '';

		if($post_type == 'description') {
			
			$product = wc_get_product( $post_id );
			$content = apply_filters( 'woocommerce_short_description', $product->get_short_description());
		
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
				'data' => array('content' => wp_kses_post($content)),
				
			);
			return new WP_REST_Response($response, 200);
		} else {
			$content = get_post_field('post_content', $post_id);
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
				'data' => array('content' => wp_kses_post($content)),
				
			);
			return new WP_REST_Response($response, 200);
		}
		
	}

	public function post_content(\WP_REST_Request $param) {
		$params = $param->get_params();
		$post_id = isset($params['id']) ? absint($params['id']) : 0;
		$post_type = isset($params['post_type']) ? sanitize_text_field($params['post_type']) : '';
		$content = wp_kses_post(wp_unslash($params['content']));
		if($post_type == 'description') {
			$product = wc_get_product( $post_id );
			$product->set_short_description( $content );
			$product->save();
		} else {
			$update_post = array(
				'ID' => $post_id,
				'post_content' => $content,
			);
			wp_update_post($update_post);
		}
		

		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Cập nhật dữ liệu thành công', 'api-code-wp'),
			'data' => array(),
		);
		return new WP_REST_Response($response, 200);
    }


	public function get_order(\WP_REST_Request $param) {
		$params = $param->get_params();
		
		if (!class_exists('WooCommerce')) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn chưa kích hoạt plugin WooCommerce', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		$order_id = isset($params['order_id']) ? absint($params['order_id']) : 0;
		$paged = isset($params['paged']) ? absint($params['paged']) : 1;
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
		$search = isset($params['search']) ? sanitize_text_field($params['search']) : '';


		$args = array(
			'limit' => 10,
			'page' => $paged,
		);

		if (!empty($search)) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key' => '_billing_phone',
					'value' => $search,
					'compare' => 'LIKE',
				),
				array(
					'key' => '_billing_first_name',
					'value' => $search,
					'compare' => 'LIKE',
				),
				array(
					'key' => '_billing_last_name',
					'value' => $search,
					'compare' => 'LIKE',
				),
			);
		}

		if (!empty($status)) {
			$args['status'] = $status;
		}

		$orders = wc_get_orders($args);

		$data = array();
		$count_id = 0;

		foreach ($orders as $order) {
			
			if (!$order instanceof WC_Order) {
				continue;
			}

			$id_post = $order->get_id();
			$status = $order->get_status();
			$total = $order->get_total();
			$date_order = $order->get_date_created()->format('d/m/Y H:i:s');
			
			$items = array();
			
			foreach ($order->get_items() as $item_id => $item) {
				$count_id++;
				$items[] = array(
					'id' => $count_id,
					'title' => sanitize_text_field(get_the_title($id_post)),
					'product_id' => $item->get_product_id(),
					'qty' => $item->get_quantity(),
					'name' => $item->get_name(),
					'price' => $item->get_total(),
					'thumb' => esc_url(get_the_post_thumbnail_url($item->get_product_id(), 'full')),
				);
			}

			$client_name = '';
			if (method_exists($order, 'get_formatted_billing_full_name')) {
				$formatted_name = $order->get_formatted_billing_full_name();
				if (!empty($formatted_name)) {
					$client_name = $formatted_name;
				}
			}
			
			$client_phone = $order->get_billing_phone();

			$data[] = array(
				'id' => $id_post,
				'title' => esc_html('#' . $id_post . ' ' . $client_name . ' ' . $client_phone . ' '),
				'status' => $this->get_status_order($status),
				'status_label' => wc_get_order_status_name($status),
				'total' => @number_format($total),
				'name' => $client_name,
				'date_order' => $date_order,
				'address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() . ' ' . $order->get_billing_city() . ' ' . $order->get_billing_state(),
				'email' => $order->get_billing_email(),
				'phone' => $client_phone,
				'count' => $order->get_item_count(),
				'products' => $items,
				'customer_note' => $order->get_customer_note(),
			);
		}

		if (empty($id_post)) {
			$data[] = null;
		}
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Tải dữ liệu thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}



	public function get_meta_value(\WP_REST_Request $param) {
		$params = $param->get_params();
		$post_id = isset($params['post_id']) ? absint($params['post_id']) : 0;
		$meta_key = isset($params['meta_key']) ? sanitize_text_field($params['meta_key']) : '';

		
		$meta_value = get_post_meta($post_id, $meta_key, true );
		
		$data = array(
			'post_id' => $post_id,
			'meta_key' => $meta_key,
			'meta_value' => $meta_value,
		);
		$response = array(
        'status' => 'success',
			'message' => esc_html__('Tải dữ liệu thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}
	public function get_pages($param) {
		$params = $param->get_params();
		$paged = isset($params['paged']) ? absint($params['paged']) : 1;
		$post_id = isset($params['post_id']) ? absint($params['post_id']) : 0;
		$search = isset($params['search']) ? sanitize_text_field($params['search']) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';

		
		$args = array(
			'post_type' => 'page',
			'posts_per_page' => 10,
			'paged' => $paged
		);
		
		if(!empty($post_id)) {
			$args['p'] = $post_id;
		}
		if(!empty($search)) {
			$args['s'] = $search;
		}
		if(!empty($status)) {
			$args['post_status'] = array($status);
		} else {
			$args['post_status'] = array( 'pending', 'draft', 'future','publish' );
		}
		
		$loop = new WP_Query( $args );
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) : $loop->the_post();
				$post_id = get_the_ID();
				
				$author_id = get_post_field( 'post_author', $post_id );
				$author_name = get_the_author_meta( 'display_name', $author_id );
				
				

				$data[] = [
					'id' => $post_id,
					'title' => get_the_title(),
					'status' => $this->get_status_post(get_post_status()),
					'status_select' => $this->get_status_post(get_post_status(),1),
					'slug' => get_post_field( 'post_name', $post_id ),
					'content' => get_post_field('post_content',$post_id),
					'date_create' => get_the_date('d/m/Y H:i:s'),
					'author' => $author_id,
					'url' => get_the_permalink(),
					'thumb' => get_the_post_thumbnail_url($post_id,'full'),
					'author_name' => $author_name,
				];	
			endwhile;
		} 
		wp_reset_postdata();
		if(empty($post_id)) {
			$data[] = null;
		}
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}

	public function get_products(\WP_REST_Request $param) {
		$params = $param->get_params();
		if ( ! class_exists( 'WooCommerce' ) ) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn chưa kích hoạt plugin WooCommerce', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		$paged = isset($params['paged']) ? absint($params['paged']) : 1;
		$category = isset($params['category']) ? sanitize_text_field($params['category']) : '';
		$product_id = isset($params['product_id']) ? absint($params['product_id']) : 0;
		$search = isset($params['search']) ? sanitize_text_field($params['search']) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';

		
		$args = array(
			'post_type' => 'product',
			'post_status' => 'any',
			'posts_per_page' => 10,
			'paged' => $paged
		);
		
		if(!empty($product_id)) {
			$args['p'] = $product_id;
		}
		if(!empty($category)) {
			$args['tax_query'][] = array(
				'taxonomy'  => 'product_cat', 
				'terms'     =>  $category
			);
		}
		if(!empty($search)) {
			$args['s'] = $search;
		}
		if(!empty($status)) {
			$args['post_status'] = array($status);
		} else {
			$args['post_status'] = array( 'pending', 'draft', 'future','publish' );
		}
		
		$loop = new WP_Query( $args );
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) : $loop->the_post();
				$product_id = get_the_ID();
				
				$author_id = get_post_field( 'post_author', $product_id );
				$author_name = get_the_author_meta( 'display_name', $author_id );
				
				$term_obj_list = get_the_terms($product_id, 'product_cat' );
				
				$product = new WC_product($product_id);
				$attachment_ids = $product->get_gallery_image_ids();
				$gallery = array();
				foreach($attachment_ids as $attachment_ids_item) {
					$gallery[] = array(
						'url' => wp_get_attachment_url($attachment_ids_item, 'full'),
						'id' => $attachment_ids_item,
					);
				}
				
				
				$sale_pricing = get_post_meta($product_id, '_sale_price', true);
				$pricing = get_post_meta($product_id, '_regular_price', true);
				$sku = get_post_meta($product_id, '_sku', true);
				$stock = get_post_meta($product_id, '_stock', true);
				$featured = get_post_meta($product_id, '_featured', true);
				
				@$product_multi = wc_get_product( $product_id );
				
							
				
				$product_tags = wp_get_post_terms($product_id, 'product_tag');
				
				$tags_name = array();
				foreach($product_tags as $product_tags_item) {
					$tags_name[] = $product_tags_item->name;
				}
				
				
				$min_price = '';
				$max_price = '';
				if ($product_multi->is_type('variable')) {
					$variation_prices = $product_multi->get_variation_prices();
					$variation_ids = $product_multi->get_children();
					$prices = array();

					foreach ($variation_ids as $variation_id) {
						$variation = wc_get_product($variation_id);
						if ($variation) {
							$prices[] = (float) $variation->get_price();
						}
					}

					if (!empty($prices)) {
						$min_price = min($prices);
						$max_price = max($prices);

						
					}
					
				} 
				
				
				$data[] = [
					'id' => $product_id,
					'title' => get_the_title(),
					'url' => get_the_permalink(),
					'status' => $this->get_status_post(get_post_status()),
					'status_select' => $this->get_status_post(get_post_status(),1),
					'description' => get_the_excerpt(),
					'content' => get_post_field('post_content',$product_id),
					'date_create' => get_the_date('d/m/Y H:i:s'),
					'featured' => $featured,
					'author' => $author_id,
					'slug' => get_post_field( 'post_name', $product_id ),
					'author_name' => $author_name,
					'category' => $term_obj_list,
					'tags' => $tags_name,
					'thumb' => get_the_post_thumbnail_url($product_id,'full'),
					'gallery' => $gallery,
					'get_type' => $product_multi->get_type(),
					'type' => $this->get_product_type($product_multi->get_type()),
					'product' => array(
						'price' => $pricing,
						'sale_price' => $sale_pricing,
						'sku' => $sku,
						'stock' => $stock,
						'price_min' => $min_price ? $min_price : '',
						'price_max' => $max_price ? $max_price : '',
					),
					
				];	
			endwhile;
		} 
		wp_reset_postdata();
		if(empty($product_id)) {
			$data[] = null;
		} 
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}

	public function get_products_variation(\WP_REST_Request $param) {
		$params = $param->get_params();
		if ( ! class_exists( 'WooCommerce' ) ) {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn chưa kích hoạt plugin WooCommerce', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		
		$product_id = (isset($params['product_id'])) ? absint($params['product_id']) : '';
		if(empty($product_id)) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('ID Sản phẩm không được bỏ trống', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		$product_multi = wc_get_product( $product_id );
	
		if($product_multi->get_type() == 'variable') {
			$get_available_variations = $product_multi->get_available_variations();
			

			foreach($get_available_variations as $keys => $get_available_variations_item) {
			
				$attributes = $get_available_variations_item['attributes'];
				
				$formatted_attributes = [];
			
				$product = wc_get_product($get_available_variations_item['variation_id']);
				
			
				foreach($attributes as $key => $attributes_item) {
					$x_key = str_replace('attribute_pa_','',$key);
					
					$taxonomy = str_replace('attribute_', '', $key);
					$term = get_term_by('slug', $attributes_item, $taxonomy);
		
					$formatted_attributes[] = array(
						'color' => $term->name,
						'paColor' => $x_key
					);
				}
				
				$data_array[] = array(
					'title' => get_the_title($product_id),
					'id' => $get_available_variations_item['variation_id'],
					'product_id' => $product_id,
					'variable' => implode(", ", $attributes),
					'sku' => $get_available_variations_item['sku'],
					'price' => $get_available_variations_item['display_regular_price'],
					'sale_price' => $get_available_variations_item['display_price'] != $get_available_variations_item['display_regular_price'] ? $get_available_variations_item['display_price'] : '',
					'attributes' => $formatted_attributes,
					'thumb' => $get_available_variations_item['image']['url'],
					'qty' => $product->get_stock_quantity(),
					
				);
			}
		}
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
			'data' => $data_array,
		);
		return new WP_REST_Response($response, 200);
	}
	public function get_posts(\WP_REST_Request $param) {
		$params = $param->get_params();
		$paged = isset($params['paged']) ? absint($params['paged']) : 1;
		$category = isset($params['category']) ? sanitize_text_field($params['category']) : '';
		$post_id = isset($params['post_id']) ? absint($params['post_id']) : 0;
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
		$search = isset($params['search']) ? sanitize_text_field($params['search']) : '';

		$status_full = array( 'pending', 'draft', 'future','publish' );
	
		$args = array(
			'post_type' => 'post',
			'posts_per_page' => 10,
			'paged' => $paged
		);
		
		
		if(!empty($post_id)) {
			$args['p'] = $post_id;
		}
		if(!empty($search)) {
			$args['s'] = $search;
		}
		if(!empty($status)) {
			$args['post_status'] = array($status);
		} else {
			$args['post_status'] = $status_full;
		}
		if(!empty($category)) {
			$args['tax_query'][] = array(
				'taxonomy'  => 'category', 
				'terms'     =>  $category
			);
		}
		
		$loop = new WP_Query( $args );
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) : $loop->the_post();
				$post_id = get_the_ID();
				
				$author_id = get_post_field( 'post_author', $post_id );
				$author_name = get_the_author_meta( 'display_name', $author_id );
				
				$category_detail = get_the_category($post_id);
				$post_tags = get_the_tags($post_id);
				
				$tags_name = array();
				if(!empty($post_tags)) {
					foreach($post_tags as $post_tags_item) {
						$tags_name[] = $post_tags_item->name;
					}
				}
				
				
				$data[] = [
					'id' => $post_id,
					'title' => get_the_title(),
					'status' => $this->get_status_post(get_post_status()),
					'status_select' => $this->get_status_post(get_post_status(),1),
					'description' => wp_kses_post(get_the_excerpt()),
					'content' => wp_kses_post(get_post_field('post_content',$post_id)),
					'date_create' => get_the_date('d/m/Y H:i:s'),
					'author' => $author_id,
					'slug' => get_post_field( 'post_name', $post_id ),
					'url' => get_the_permalink($post_id),
					'tags' => $tags_name,
					'author_name' => $author_name,
					'category' => $category_detail,
					'thumb' => get_the_post_thumbnail_url($post_id,'full'),
				];	
			endwhile;
		} 
		wp_reset_postdata();
		if(empty($post_id)) {
			$data[] = null;
		} 
		 $response = array(
			'status' => 'success',
			'message' => esc_html__('Thành công','api-code-wp'),
			'data' => $data,
		);
	
		return rest_ensure_response($response);

	}
	public function update_post_category(\WP_REST_Request $param) {
		$params = $param->get_params();
		$post_id = isset($params['post_id']) ? absint($params['post_id']) : 0;
		$taxonomy = isset($params['taxonomy']) ? sanitize_text_field($params['taxonomy']) : '';
		$select = isset($params['select']) ? json_decode(wp_unslash($params['select']), true) : [];

		if ($post_id && $taxonomy && !empty($select)) {
			$term_ids = array_map(function($item) {
				return absint($item['term_id']);
			}, $select);

			wp_set_post_terms($post_id, $term_ids, $taxonomy);

			$response = array(
				'status' => 'success',
				'message' => esc_html__('Cập nhật danh mục thành công', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		} else {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Lỗi', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
	}
	public function get_category(\WP_REST_Request $param) {
		$params = $param->get_params();
		$id = isset($params['id']) ? absint($params['id']) : 0;
		$taxonomy = isset($params['taxonomy']) ? sanitize_text_field($params['taxonomy']) : '';
		$post_id = isset($params['post_id']) ? absint($params['post_id']) : 0;

		
		if($taxonomy == 'product_cat') {
			if ( ! class_exists( 'WooCommerce' ) ) {
				
				$response = array(
					'status' => 'error',
					'message' => esc_html__('Bạn chưa kích hoạt plugin WooCommerce', 'api-code-wp'),
					'data' => array(),
				);
				return new WP_REST_Response($response, 200);
			}
		}
		
		$terms = get_terms( array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
			'exclude' => $id,
		) );
		
		if ( ! class_exists( 'WooCommerce' ) ) {
			foreach($terms as $terms_item) {
				$set_select_category = $this->set_select_category($post_id, $taxonomy, $terms_item->name);
				$master_name = get_term_by('id', $terms_item->parent, $taxonomy);
				$data[] = array(
					'term_id' => $terms_item->term_id,
					'name' => $terms_item->name,
					'slug' => $terms_item->slug,
					'taxonomy' => $terms_item->taxonomy,
					'parent' => $terms_item->parent,
					'count' => $terms_item->count,
					'description' => $terms_item->description,
					'thumb' => null,
					'master_name' => $master_name->name,
					'isSelected' => $set_select_category,
					

				);
				
				$parent_category = get_term( $id, $taxonomy);
					
				$data_id[] = array(
					'label' => $terms_item->name,
					'value'	=> $terms_item->term_id,
					'selected' => ($parent_category->parent == $terms_item->term_id) ? true : false,
					
				);
				
			
			}
			
		} else {
			
			foreach($terms as $terms_item) {
				$thumbnail_id = get_woocommerce_term_meta( $terms_item->term_id, 'thumbnail_id', true ); 
				$image = wp_get_attachment_url( $thumbnail_id ); 
				
				
				$set_select_category = $this->set_select_category($post_id, $taxonomy, $terms_item->name);
				$master_name = get_term_by('id', $terms_item->parent, $taxonomy);
				$data[] = array(
					'term_id' => $terms_item->term_id,
					'name' => $terms_item->name,
					'slug' => $terms_item->slug,
					'taxonomy' => $terms_item->taxonomy,
					'parent' => $terms_item->parent,
					'count' => $terms_item->count,
					'description' => $terms_item->description,
					'thumb' => $image,
					'isSelected' => $set_select_category,
					'master_name' => $master_name->name,
					

				);
				
				$parent_category = get_term( $id, $taxonomy);
				
					
				$data_id[] = array(
					'label' => $terms_item->name,
					'value'	=> $terms_item->term_id,
					'selected' => ($parent_category->parent == $terms_item->term_id) ? true : false,
					
				);
				
			
			}
		} 
		
		if(!empty($id)) {
			$data_id[] = array(
				'label' => esc_html__('Không chọn','api-code-wp'),
				'value'	=> 0,
				'selected' => false,
				
			);
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
				'data' => $data_id,
			);
			return new WP_REST_Response($response, 200);
		} else {
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
				'data' => $data,
			);
			return new WP_REST_Response($response, 200);
		}
		
	}

	public function set_select_category($post_id, $taxonomy, $cat_name) {
		$terms = wp_get_post_terms($post_id, $taxonomy);
		if (!is_wp_error($terms)) {
			foreach ($terms as $term) {
				if ($cat_name == $term->name) {
					return true;
				}
			}
		}
		return false;
	}



	public function create_page(\WP_REST_Request $param) {
		$params = $param->get_params();
		$title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$content = isset($params['content']) ? sanitize_textarea_field($params['content']) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';


		if ($title == '' || $content == '') {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Vui lòng nhập đầy đủ thông tin', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		$create = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_author'  => 1,
			'post_type'    => 'page',
		);

		$post_id = wp_insert_post($create);
		$slug = get_post_field('post_name', $post_id);
		$url = get_permalink($post_id);
		$thumbnail_id = get_post_thumbnail_id($post_id);
		$thumbnail_url = '';
		if ($thumbnail_id) {
			$thumbnail_data = wp_get_attachment_image_src($thumbnail_id, 'thumbnail');
			$thumbnail_url = $thumbnail_data[0];
		}

		$data = array(
			'id'        => $post_id,
			'title'     => $title,
			'slug'      => $slug,
			'url'       => $url,
			'thumb' => $thumbnail_url,
		);
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Tạo trang thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}


	public function create_post(\WP_REST_Request $param) {
		$params = $param->get_params();
		$title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$content = isset($params['content']) ? wp_kses_post(wp_unslash($params['content'])) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';

		
		if($title == '' || $content == '') {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Vui lòng nhập đầy đủ thông tin', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		$create = array(
		  'post_title'    => $title,
		  'post_content'  => $content,
		  'post_status'   => $status,
		  'post_author'   => 1,
		  'post_type'     => 'post',
		);


		$post_id = wp_insert_post( $create );
		
		if (isset($_FILES['thumb']) && !empty($_FILES['thumb']['name'])) { 
			$this->attach_product_thumbnail($post_id,$_FILES['thumb'],'0');
		}
		$post_tags = get_the_tags($post_id);
		
		$tags_name = array();
			foreach($post_tags as $post_tags_item) {
				$tags_name[] = $post_tags_item->name;
			}
		
		$data = array(
			'id' => $post_id,
			'title' => $title,
			'slug' => get_post_field( 'post_name', $post_id ),
			'url' => get_the_permalink($post_id),
			'tags' => $tags_name,
			'thumb' => get_the_post_thumbnail_url($post_id,'full'),
		);
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Tạo bài viết thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}

	public function create_product(\WP_REST_Request $param) {
		$params = $param->get_params();
		if (!class_exists('WooCommerce')) {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn chưa kích hoạt plugin Woocommerce', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		$title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$sku = isset($params['sku']) ? sanitize_text_field($params['sku']) : '';
		$price = isset($params['price']) ? sanitize_text_field($params['price']) : '';
		$stock = isset($params['stock']) ? sanitize_text_field($params['stock']) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
		$sale_price = isset($params['sale_price']) ? sanitize_text_field($params['sale_price']) : '';
		$content = isset($params['content']) ? sanitize_textarea_field($params['content']) : '';


		if ($title == '') {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Vui lòng nhập tiêu đề', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		if ($price <= $sale_price) {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn không được phép nhập giá gốc nhỏ hơn giá khuyến mãi', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		$product = new WC_Product();
		$product->set_name($title);
		$product->set_status($status);
		$product->set_description($content);
		$product->set_regular_price($price);
		$product->set_sale_price($sale_price);

		try {
			$product->set_sku($sku);
		} catch (WC_Data_Exception $e) {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Mã sản phẩm đã được sử dụng', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		if ($stock !== '') {
			$product->set_stock_quantity($stock);
			$product->set_stock_status('instock');
			$product->set_manage_stock(true);
		} else {
			$product->set_manage_stock(false);
		}

		$product_id = $product->save();

		if (isset($_FILES['thumb']) && !empty($_FILES['thumb']['name'])) {
			$this->attach_product_thumbnail($product_id, $_FILES['thumb'], '0');
		}

		$attachment_ids = $product->get_gallery_image_ids();
		$gallery = array();
		foreach ($attachment_ids as $attachment_ids_item) {
			$gallery[] = array(
				'url' => wp_get_attachment_url($attachment_ids_item, 'full'),
				'id' => $attachment_ids_item,
			);
		}

		$product_tags = wp_get_post_terms($product_id, 'product_tag');
		$tags_name = array();
		foreach ($product_tags as $product_tags_item) {
			$tags_name[] = $product_tags_item->name;
		}

		$data = array(
			'id' => $product_id,
			'title' => $title,
			'url' => get_the_permalink($product_id),
			'slug' => get_post_field('post_name', $product_id),
			'tags' => $tags_name,
			'thumb' => get_the_post_thumbnail_url($product_id, 'full'),
			'gallery' => $gallery,
			'get_type' => $product->get_type(),
			'type' => $this->get_product_type($product->get_type()),
			'product' => array(
				'price' => $price,
				'sale_price' => $sale_price,
				'sku' => $sku,
				'stock' => $stock,
			),
		);

		$response = array(
			'status' => 'success',
			'message' => esc_html__('Tạo sản phẩm thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}

	/// Update post 
	public function update_post(\WP_REST_Request $param) {
		$params = $param->get_params();
		$product_id = isset($params['id']) ? absint($params['id']) : 0;
		$title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
		$slug = isset($params['slug']) ? sanitize_title($params['slug']) : '';
		$tags = isset($params['tags']) ? sanitize_text_field($params['tags']) : '';

		
		
		if(empty($title)) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Vui lòng không bỏ trống tiêu đề', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		
		$update_post = array(
			'ID'           => $product_id,
			'post_title'   => $title,
			'post_status' => $status,
			'post_name' => $slug,
			'post_type' => 'post',
		);

		wp_update_post($update_post);
		
		wp_set_post_tags( $product_id, $tags); 
		
		if (isset($_FILES['thumb']) && !empty($_FILES['thumb']['name'])) { 
			$this->attach_product_thumbnail($product_id,$_FILES['thumb'],'0');
		}
		
	
		$data = array(
			'id' => $product_id,
			'title' => $title,
			'type' => 'post',
		);
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Cập nhật bài viết thành công', 'api-code-wp'),
			'data' => array(),
		);
		return new WP_REST_Response($response, 200);
	}

	public function update_page(\WP_REST_Request $param) {
		$params = $param->get_params();
		$page_id = isset($params['id']) ? absint($params['id']) : 0;
		$title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
		$slug = isset($params['slug']) ? sanitize_text_field($params['slug']) : '';
		
		if(empty($title)) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Vui lòng không bỏ trống tiêu đề', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		$update_product = array(
			'ID'           => $page_id,
			'post_title'   => $title,
			'post_status' => $status,
			'post_name' => $slug,
			'post_type' => 'page',
		);

		wp_update_post($update_product);
		
		if (isset($_FILES['thumb']) && !empty($_FILES['thumb']['name'])) { 
			$this->attach_product_thumbnail($page_id,$_FILES['thumb'],'0');
		}
		
	
		$data = array(
			'id' => $page_id,
			'title' => $title,
			'type' => 'page',
		);
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Cập nhật trang thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}

	public function update_product(\WP_REST_Request $param) {
		$params = $param->get_params();
		$product_id = isset($params['id']) ? absint($params['id']) : 0;
		$title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$sku = isset($params['sku']) ? sanitize_text_field($params['sku']) : '';
		$stock = isset($params['stock']) ? sanitize_text_field($params['stock']) : '';
		$price = isset($params['price']) ? sanitize_text_field($params['price']) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
		$sale_price = isset($params['sale_price']) ? sanitize_text_field($params['sale_price']) : '';
		$description = isset($params['description']) ? sanitize_textarea_field($params['description']) : '';
		$type = isset($params['type']) ? sanitize_text_field($params['type']) : '';
		$slug = isset($params['slug']) ? sanitize_title($params['slug']) : '';
		$tags = isset($params['tags']) ? sanitize_text_field($params['tags']) : '';


		$product = wc_get_product($product_id);

		if (!$product) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Không tìm thấy sản phẩm', 'api-code-wp'),
				'data' => array('type' => $type),
			);
			return new WP_REST_Response($response, 200);
		}

		try {
			$product->set_sku($sku);
		} catch (WC_Data_Exception $e) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Mã sản phẩm đã được sử dụng', 'api-code-wp'),
				'data' => array('type' => $type),
			);
			return new WP_REST_Response($response, 200);
		}

		if ($type != 'variable') {
			if ($price <= $sale_price) {
				$response = array(
					'status' => 'error',
					'message' => esc_html__('Bạn không được phép nhập giá gốc nhỏ hơn giá khuyến mãi', 'api-code-wp'),
					'data' => array('type' => $type),
				);
				return new WP_REST_Response($response, 200);
			}
		}

		$product->set_name($title);
		$product->set_status($status);
		$product->set_short_description($description);
		$product->set_slug($slug);
		$product->set_regular_price($price);
		$product->set_sale_price($sale_price);

		if ($stock !== '') {
			$product->set_stock_quantity($stock);
			$product->set_stock_status('instock');
			$product->set_manage_stock(true);
		} else {
			$product->set_manage_stock(false);
		}

		$product->save();

		wp_set_post_terms($product_id, $tags, 'product_tag', false);

		if ($status == 'future') {
			$product->set_featured(true);
			wp_set_object_terms($product_id, 'featured', 'product_visibility', true);
		} elseif ($status == 'publish') {
			$product->set_featured(false);
			wp_remove_object_terms($product_id, 'featured', 'product_visibility');
		}

		wp_set_object_terms($product_id, $type, 'product_type');

		if (isset($_FILES['thumb']) && !empty($_FILES['thumb']['name'])) {
			$this->attach_product_thumbnail($product_id, $_FILES['thumb'], '0');
		}

		$data = array(
			'id' => $product_id,
			'title' => $title,
			'type' => $type,
		);
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Cập nhật sản phẩm thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}


	public function update_order_status(\WP_REST_Request $param) {
		$params = $param->get_params();
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
		$order_id = isset($params['order_id']) ? absint($params['order_id']) : 0;

		
		$order = new WC_Order( $order_id );
		$order->update_status($status, 'CODE-WP');
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Cập nhật thành công', 'api-code-wp'),
			'data' => array(),
		);
		return new WP_REST_Response($response, 200);
	}

	public function search_item(\WP_REST_Request $param) {
		$params = $param->get_params();
		$search = isset($params['search']) ? sanitize_text_field($params['search']) : '';
		$paged = isset($params['paged']) ? absint($params['paged']) : 1;

		
		$args = array(
			'post_type' => array('post','page','product'),
			'post_status' => array( 'pending', 'draft', 'future','publish' ),
			'posts_per_page' => 10,
			'paged' => $paged,
			's' => $search,
		);
		
		
		
		$loop = new WP_Query( $args );
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) : $loop->the_post();
				$product_id = get_the_ID();
				$get_post_type = get_post_type($product_id);
				$author_id = get_post_field( 'post_author', $product_id );
				$author_name = get_the_author_meta( 'display_name', $author_id );
					
				if($get_post_type == 'product') {
					
					
					$term_obj_list = get_the_terms($product_id, 'product_cat' );
					
					$product = new WC_product($product_id);
					$attachment_ids = $product->get_gallery_image_ids();
					$gallery = array();
					foreach($attachment_ids as $attachment_ids_item) {
						$gallery[] = array(
							'url' => wp_get_attachment_url($attachment_ids_item, 'full'),
							'id' => $attachment_ids_item,
						);
					}
					
					
					$sale_pricing = get_post_meta($product_id, '_sale_price', true);
					$pricing = get_post_meta($product_id, '_regular_price', true);
					$sku = get_post_meta($product_id, '_sku', true);
					$stock = get_post_meta($product_id, '_stock', true);
					$featured = get_post_meta($product_id, '_featured', true);
					
					$product_multi = wc_get_product( $product_id );
					
								
					
					$product_tags = wp_get_post_terms($product_id, 'product_tag');
					
					$tags_name = array();
					foreach($product_tags as $product_tags_item) {
						$tags_name[] = $product_tags_item->name;
					}
					
					
					$min_price = '';
					$max_price = '';
					if ($product_multi->is_type('variable')) {
						$variation_prices = $product_multi->get_variation_prices();
						$variation_ids = $product_multi->get_children();
						$prices = array();

						foreach ($variation_ids as $variation_id) {
							$variation = wc_get_product($variation_id);
							if ($variation) {
								$prices[] = (float) $variation->get_price();
							}
						}
		
						if (!empty($prices)) {
							$min_price = min($prices);
							$max_price = max($prices);

							
						}
						
					} 
				
					$data[] = [
						'id' => $product_id,
						'title' => get_the_title(),
						'url' => get_the_permalink(),
						'status' => $this->get_status_post(get_post_status()),
						'status_select' => $this->get_status_post(get_post_status(),1),
						'description' => get_the_excerpt(),
						'content' => get_post_field('post_content',$product_id),
						'date_create' => get_the_date('d/m/Y H:i:s'),
						'featured' => $featured,
						'author' => $author_id,
						'slug' => get_post_field( 'post_name', $product_id ),
						'author_name' => $author_name,
						'category' => $term_obj_list,
						'tags' => $tags_name,
						'thumb' => get_the_post_thumbnail_url($product_id,'full'),
						'gallery' => $gallery,
						'get_type' => $product_multi->get_type(),
						'type' => $this->get_product_type($product_multi->get_type()),
						'post_type' => get_post_type( get_the_ID()),
						'product' => array(
							'price' => $pricing,
							'sale_price' => $sale_pricing,
							'sku' => $sku,
							'stock' => $stock,
							'price_min' => $min_price,
							'price_max' => $max_price,
						),
						
					];
				} elseif($get_post_type == 'page') {
					$data[] = [
						'id' => $product_id,
						'title' => get_the_title(),
						'status' => $this->get_status_post(get_post_status()),
						'status_select' => $this->get_status_post(get_post_status(),1),
						'slug' => get_post_field( 'post_name', $product_id ),
						'content' => get_post_field('post_content',$product_id),
						'date_create' => get_the_date('d/m/Y H:i:s'),
						'author' => $author_id,
						'url' => get_the_permalink(),
						'thumb' => get_the_post_thumbnail_url($product_id,'full'),
						'author_name' => $author_name,
						'post_type' => get_post_type( get_the_ID()),
					];	
				} elseif($get_post_type == 'post') {
					$category_detail = get_the_category($product_id);
					$post_tags = get_the_tags($product_id);
					$tags_name = array();
					foreach($post_tags as $post_tags_item) {
						$tags_name[] = $post_tags_item->name;
					}
					$data[] = [
						'id' => $product_id,
						'title' => get_the_title(),
						'status' => $this->get_status_post(get_post_status()),
						'status_select' => $this->get_status_post(get_post_status(),1),
						'description' => wp_kses_post(get_the_excerpt()),
						'content' => wp_kses_post(get_post_field('post_content',$product_id)),
						'date_create' => get_the_date('d/m/Y H:i:s'),
						'author' => $author_id,
						'slug' => get_post_field( 'post_name', $product_id ),
						'url' => get_the_permalink($product_id),
						'tags' => $tags_name,
						'author_name' => $author_name,
						'category' => $category_detail,
						'thumb' => get_the_post_thumbnail_url($product_id,'full'),
						'post_type' => get_post_type( get_the_ID()),
					];	
				}
				
				
						
				
			endwhile;
		} 
		wp_reset_postdata();
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}

	public function update_category(\WP_REST_Request $param) {
		$params = $param->get_params();
		$term_id = isset($params['term_id']) ? sanitize_text_field($params['term_id']) : '';
		$taxonomy = isset($params['taxonomy']) ? sanitize_text_field($params['taxonomy']) : '';
		$slug = isset($params['slug']) ? sanitize_text_field($params['slug']) : '';
		$description = isset($params['description']) ? sanitize_textarea_field($params['description']) : '';
		$title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';

		
		if(empty($title)) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Tên danh mục không được bỏ trống', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		wp_update_term($term_id, $taxonomy, array(
			'name' => $title,
			'slug' => $slug,
			'parent' => $status,
			'description' => $description
		));
		
		
		
		if ($_FILES['thumb'] && $taxonomy != 'post') {
			$id_image = $this->upload_image($_FILES['thumb']);
			update_term_meta($term_id, 'thumbnail_id', $id_image);
		}
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Cập nhật thành công', 'api-code-wp'),
			'data' => array(),
		);
		return new WP_REST_Response($response, 200);
	}

	public function create_category(\WP_REST_Request $param) {
		$params = $param->get_params();
		
		$taxonomy = isset($params['taxonomy']) ?esc_attr($params['taxonomy']) : '';
		$slug = isset($params['slug']) ? esc_attr($params['slug']) : '';
		$description = isset($params['description']) ? esc_attr($params['description']) : '';
		$title = isset($params['title']) ? esc_attr($params['title']) : '';
		$status = isset($params['status']) ? esc_attr($params['status']) : '';
		
		if(empty($title)) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Vui lòng nhập đầy đủ thông tin', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		
		$wp_insert_term = wp_insert_term(
			$title,   
			$taxonomy,
			array(
				'description' => $description,
				'slug'        => $slug,
				'parent'      => $status,
			)
		);
		
		if ($_FILES['thumb'] && $taxonomy != 'post') {
			$id_image = $this->upload_image($_FILES['thumb']);
			update_term_meta($wp_insert_term['term_id'], 'thumbnail_id', $id_image);
		}
		
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Tạo danh mục thành công', 'api-code-wp'),
			'data' => array(),
		);
		return new WP_REST_Response($response, 200);
		
	}

	public function get_attributes_product(\WP_REST_Request $param) {
		$params = $param->get_params();
		$pa = isset($params['pa']) ? sanitize_text_field($params['pa']) : '';

		if ( ! class_exists( 'WooCommerce' ) ) {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn chưa kích hoạt plugin WooCommerce', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		if ( ! class_exists( 'Woo_Variation_Swatches' ) ) {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn chưa kích hoạt plugin Variation Swatches for WooCommerce', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		
		$attributes = wc_get_attribute_taxonomies();
		if($pa == '') {
			if(!empty($attributes)) {
				foreach($attributes as $attributes_item) {
				
					$data_label[] = array(
						'id' => $attributes_item->attribute_id,
						'label' => $attributes_item->attribute_label,
						'slug' => $attributes_item->attribute_name,
						'type' => $attributes_item->attribute_type,
					);
				}
				
			} else {
				$data_label[] = null;
			}
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
				'data' => $data_label,
			);
			return new WP_REST_Response($response, 200);
		} else {
			$terms = get_terms( array(
				'taxonomy'   => 'pa_'.$pa,
				'hide_empty' => false,
			) );
			
			if(!empty($terms)) {
				foreach($terms as $terms_item) {
					// $attribute_image_id = get_term_meta($terms_item->term_id, 'attribute_'.$terms_item.'_image', true );
					$attribute_image_id = get_term_meta($terms_item->term_id, 'product_attribute_image', true );

					$attribute_image_url = wp_get_attachment_image_src($attribute_image_id, 'full' );
					$data_label[] = array(
						'term_id' => $terms_item->term_id,
						'name' => $terms_item->name, 
						'slug' => $terms_item->slug, 
						'taxonomy' => $terms_item->taxonomy,
						'description' => $terms_item->description,
						'thumb' => $attribute_image_url[0],
						'color' => get_term_meta($terms_item->term_id)["product_attribute_color"][0],
					);
				}
			} else {
				$data_label[] = null;
			}
			
			
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
				'data' => $data_label,
			);
			return new WP_REST_Response($response, 200);
		}
	}

	public function create_attributes_product(\WP_REST_Request $param) {
		$params = $param->get_params();
		$name = isset($params['name']) ? sanitize_text_field($params['name']) : '';
		$slug = isset($params['slug']) ? sanitize_text_field($params['slug']) : '';
		$status = isset($params['status']) ? sanitize_text_field($params['status']) : '';
		$content = isset($params['content']) ? sanitize_textarea_field($params['content']) : '';
		$option_type = isset($params['option_type']) ? sanitize_text_field($params['option_type']) : '';
		$product_attribute_color = isset($params['color']) ? sanitize_text_field($params['color']) : '';

		
		$name_slug = sanitize_title($name);
		
		if($option_type == 'option') {
			if($name == '') {
				
				$response = array(
					'status' => 'error',
					'message' => esc_html__('Vui lòng nhập đầy đủ thông tin', 'api-code-wp'),
					'data' => array(),
				);
				return new WP_REST_Response($response, 200);
			}
			
			$attribute_args = array(
				'name' => $name,
				'slug' => 'pa_'.$name_slug,
				'type' => $status,
				'order_by' => 'menu_order',
				'has_archives' => false,
			);
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( 0 );
			$attribute->set_name( $attribute_args['name'] );
			$attribute->set_options( array() );
			$attribute->set_position( 0 );
			$attribute->set_visible( true );
			$attribute->set_variation( false );
			wc_create_attribute( $attribute_args );
			
		
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Tạo thành công thuộc tính', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
			
		} elseif($option_type == 'option_select') {
			if($name == '') {
				$response = array(
					'status' => 'error',
					'message' => esc_html__('Vui lòng nhập đầy đủ thông tin', 'api-code-wp'),
					'data' => array(),
				);
				return new WP_REST_Response($response, 200);
			}
			
			$term = get_term_by( 'name', $name, 'pa_'.$slug);
			
			if ( ! $term ) {
				$args = array(  
					'description' => $content,
					'slug' => $name_slug,
					'parent' => 0   
				);  
				$result = wp_insert_term($name, 'pa_'.$slug, $args);
				
				if (is_wp_error($result)) {

					$error_message = $result->get_error_message();
					$response = array(
						'status' => 'error',
						'message' => esc_html($error_message),
						'data' => array(),
					);
					return new WP_REST_Response($response, 200);

			
				} else {
					$term_id = $result['term_id'];
					if (isset($_FILES['thumb']) && !empty($_FILES['thumb']['name'])) {
						$id_image = $this->upload_image($_FILES['thumb']);
						if (!is_wp_error($id_image)) {
							update_term_meta($term_id, 'product_attribute_image', $id_image);
						} else {
							
							$response = array(
								'status' => 'error',
								'message' => esc_html__('Lỗi khi upload hình ảnh', 'api-code-wp'),
								'data' => array(),
							);
							return new WP_REST_Response($response, 200);
						}
					}
					if(!empty($product_attribute_color)) {
						update_term_meta( $term_id, 'product_attribute_color', $product_attribute_color);
					}
				}
				
				
				
				
			}
			
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Tạo thuộc tính thành công', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
	}

	public function update_attributes_product(\WP_REST_Request $param) {
		$params = $param->get_params();
		$fields = ['name', 'slug', 'term_id', 'content', 'color', 'pa'];
		$data = [];

		foreach ($fields as $field) {
			$data[$field] = isset($params[$field]) ? sanitize_text_field($params[$field]) : '';
		}

		$data['name_slug'] = sanitize_title($data['name']);
		
		if (empty($data['name'])) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Vui lòng không bỏ thông tin', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		$args = [
			'description' => $data['content'],
			'slug' => $data['name_slug'],
			'parent' => 0,
			'name' => $data['name']
		];
		
		if (isset($_FILES['thumb']) && !empty($_FILES['thumb']['name'])) {
			$id_image = $this->upload_image($_FILES['thumb']);
			if (!is_wp_error($id_image)) {
				update_term_meta($data['term_id'], 'product_attribute_image', $id_image);
			} else {
				
				$response = array(
					'status' => 'error',
					'message' => esc_html__('Lỗi khi upload hình ảnh', 'api-code-wp'),
					'data' => array(),
				);
				return new WP_REST_Response($response, 200);
			}
		}
		
		
		
		$update_result = wp_update_term($data['term_id'], 'pa_'.$data['pa'], $args);
	
		if (is_wp_error($update_result)) {

			$error_message = $update_result->get_error_message();
			$response = array(
				'status' => 'error',
				'message' => esc_html($error_message),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		if (!empty($data['color'])) {
			update_term_meta($data['term_id'], 'product_attribute_color', $data['color']);
		}
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Cập nhật thông tin thuộc tính thành công', 'api-code-wp'),
			'data' => array(),
		);
		return new WP_REST_Response($response, 200);
	}


	public function update_product_variable(\WP_REST_Request $param) {
		$params = $param->get_params();
		$variation_id = isset($params['id']) ? sanitize_text_field($params['id']) : '';
		$sku = isset($params['sku']) ? sanitize_text_field($params['sku']) : '';
		$price = isset($params['price']) ? sanitize_text_field($params['price']) : '';
		$sale_price = isset($params['sale_price']) ? sanitize_text_field($params['sale_price']) : '';
		$qty = isset($params['qty']) ? sanitize_text_field($params['qty']) : '';
		$items = isset($params['items']) ? $params['items'] : [];

		
		if($price <= $sale_price) {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn không được phép nhập giá gốc nhỏ hơn giá khuyến mãi', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
	
		$variation = new WC_Product_Variation($variation_id);
		if (!empty($_FILES['thumb'])) {
			$id_image = $this->upload_image($_FILES['thumb']);
		}
		
		try {
			$variation->set_sku($sku);
		} catch (WC_Data_Exception $e) {
			
		}

		
		
		if(empty($qty)) {
			$variation->set_manage_stock(false); 
		} else {
			$variation->set_manage_stock(true); 
			$variation->set_stock_quantity($qty);
		}
		
		
		
		if (!empty($id_image)) {
			$variation->set_image_id($id_image);
		}
		
		if (!empty($sale_price)) {
			$variation->set_price($sale_price);
			$variation->set_regular_price($price);
			$variation->set_sale_price($sale_price);
			
	
		} else {
			$variation->set_price($price);
			$variation->set_regular_price($price);
		}
		
		if(!empty($items)) {
			$attributes = array(); 
			foreach ($items as $key => $item) {
				$pa_color = isset($item['pa_color']) ? esc_attr($item['pa_color']) : '';
				$color = isset($item['color']) ? esc_attr($item['color']) : '';

				if ($pa_color === '' || $color === '') {
					continue;
				}


				// $attributes[$pa_color] = sanitize_title($color);
				$attributes['pa_' . $pa_color] = sanitize_title($color);

		
			}
			
			
			$variation->set_attributes($attributes);
			
		}
		
		$variation->save();
		
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Cập nhật thuộc tính thành công', 'api-code-wp'),
			'data' => array(),
		);
		return new WP_REST_Response($response, 200);
	}

	public function create_product_variable(\WP_REST_Request $param) {
		$params = $param->get_params();
		$product_id = isset($params['id']) ? absint($params['id']) : 0;
		$sku = isset($params['sku']) ? sanitize_text_field($params['sku']) : '';
		$price = isset($params['price']) ? sanitize_text_field($params['price']) : '';
		$sale_price = isset($params['sale_price']) ? sanitize_text_field($params['sale_price']) : '';
		$qty = isset($params['qty']) ? sanitize_text_field($params['qty']) : '';
		$items = isset($params['items']) ? $params['items'] : [];


		if ($price === '' || $product_id === '' || empty($items)) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Vui lòng nhập đầy đủ thông tin', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		if($price <= $sale_price) {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn không được phép nhập giá gốc nhỏ hơn giá khuyến mãi', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
			
		}

		$id_image = '';
		if (isset($_FILES['thumb']) && $_FILES['thumb']['error'] === UPLOAD_ERR_OK) {
			$id_image = $this->upload_image($_FILES['thumb']);
		}

		$attributes = array(); 

		foreach ($items as $key => $item) {
			$pa_color = isset($item['pa_color']) ? esc_attr($item['pa_color']) : '';
			$color = isset($item['color']) ? esc_attr($item['color']) : '';

			if ($pa_color === '' || $color === '') {
				continue;
			}


			$attributes[$pa_color] = $color;

	
		}


		
		
		$data[] = array(
			'attributes' => $attributes,
			'price' => $price,
			'sale_price' => $sale_price,
			'sku' => $sku,
			'qty' => $qty,
			'thumb' => $id_image,
		);		
	

		$add_product_variation = $this->add_product_variation($product_id,$data);
		if($add_product_variation) {
			
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Tạo sản phẩm có thuộc tính thành công', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
			
		} else {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Lỗi không thể tạo', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		
		}

	}

	public function set_option_attr($taxonomy) {
		$termss = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		) );
		foreach($termss as $terms_item) {
			$termsss[] = $terms_item->name;
		}
		return $termsss;
	}

	public function add_product_variation($product_id, $items) {
		$product = wc_get_product($product_id);
		
		if (!$product || $product->get_type() !== 'variable') {
			return; 
		}


		$existing_attributes = (array) $product->get_attributes();


		foreach ($items as $item) {
			foreach ($item['attributes'] as $key => $value) {
				$taxonomy = 'pa_' . $key;
				$term_slug = sanitize_title($value);
				
				

				if (!isset($existing_attributes[$taxonomy])) {
					
					
					$set_option_attr = $this->set_option_attr($taxonomy);
					
					$attribute_size = new WC_Product_Attribute();
					$attribute_size->set_id( sizeof( $existing_attributes) + 1 );
					$attribute_size->set_name($taxonomy);
					$attribute_size->set_options($set_option_attr);
					$attribute_size->set_position($key);
					$attribute_size->set_visible(true);
					$attribute_size->set_variation(true);
					$existing_attributes[] = $attribute_size;
				} 
			}
		}
		

		foreach ($items as $item) {
			$attributes = [];
			foreach ($item['attributes'] as $key => $value) {
				$attributes['pa_' . $key] = sanitize_title($value);
			}

			$variation = new WC_Product_Variation();
			$variation->set_parent_id($product->get_id());
			$variation->set_attributes($attributes);
			try {
				$variation->set_sku($item['sku']);
			} catch (WC_Data_Exception $e) {
				return false;
			}
			
			$variation->set_regular_price($item['price']);
			$variation->set_sale_price($item['sale_price']);
			
			$variation->set_stock_quantity($item['qty']);
			if(!empty($item['qty'])) {
				$variation->set_manage_stock(true);	
			}
			if(!empty($item['thumb'])) {
				$variation->set_image_id($item['thumb']);
			}
			
			
			$variation->set_stock_status('instock');
			$variation->save();
		}
		
		$product->set_attributes($existing_attributes);
		$product->save();
		
		return true;
	
	}





	public function get_analytics(\WP_REST_Request $param) {
		global $wpdb;
		$count_posts = wp_count_posts('post');
		$count_pages = wp_count_posts('page');
		$count_products = wp_count_posts('product');
		$count_orders = wp_count_posts('shop_order');

		$wc_processing_order_count = 0;
		$total_sales = 0;
		$num_results = 0;
		
		$total_users = count_users();

		$data_array = array(
			'post' => number_format($count_posts->publish),
			'page' => number_format($count_pages->publish),
			
			'user' => $total_users['total_users'],
			'plugin_version' => CODEWP_API_FOR_ANDROID_IOS_VERSION,
		);

		if (!class_exists('WooCommerce')) {
			
		} else {
			$wc_processing_order_count = wc_processing_order_count();

			$order_statuses = array('wc-completed');
			$args = array(
				'status' => $order_statuses,
				'limit' => -1,
			);
			$orders = wc_get_orders($args);
			foreach ($orders as $order) {
				$total_sales += floatval($order->get_total());
			}
			$data_array['order_completed'] = number_format(wc_orders_count('wc-completed'));
			$data_array['order_processing'] = number_format($wc_processing_order_count);
			$data_array['order_cancelled'] = number_format(wc_orders_count('wc-cancelled'));
			$data_array['order_on_hold'] = number_format(wc_orders_count('wc-on-hold'));
			$data_array['total_price'] = number_format($total_sales);
			$data_array['product'] = number_format($count_products->publish);
		}

		if (class_exists('CFDB7_Form_Details')) {
			$cfdb = apply_filters('cfdb7_database', $wpdb);
			$table_name = $cfdb->prefix . 'db7_forms';

			$results = $cfdb->get_results("SELECT * FROM $table_name", OBJECT);

			$num_results = count($results);
			$data_array['contact_form_7'] = $num_results;
		}


		$response = array(
			'status' => 'success',
			'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
			'data' => $data_array,
		);
		return new WP_REST_Response($response, 200);

	}


	public function get_notes(\WP_REST_Request $param) {
		$params = $param->get_params();
		$order_id = isset($params['order_id']) ? absint($params['order_id']) : 0;

		$get_private_order_notes = $this->get_private_order_notes($order_id);
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
			'data' => $get_private_order_notes,
		);
		return new WP_REST_Response($response, 200);
	}

	public function update_notes(\WP_REST_Request $param) {
		$params = $param->get_params();
		$order_id = isset($params['order_id']) ? absint($params['order_id']) : 0;
		$notes = isset($params['notes']) ? esc_html($params['notes']) : '';

		$order = wc_get_order($order_id);
		if ($order) {
			$order->add_order_note($notes);
		}
		$get_private_order_notes = $this->get_private_order_notes($order_id);
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
			'data' => $get_private_order_notes,
		);
		return new WP_REST_Response($response, 200);
	}

	public function move_trash(\WP_REST_Request $param) {
		$params = $param->get_params();
		$id = isset($params['id']) ? absint($params['id']) : 0;
		$type = isset($params['type']) ? sanitize_text_field($params['type']) : '';


		if($type == 'post' || $type == 'page' || $type == 'product') {
			$trash = wp_trash_post($id);
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Xóa thành công', 'api-code-wp'),
				'data' => array(
					
				),
			);
			return new WP_REST_Response($response, 200);
		} elseif($type == 'variable') {
			$variation = new WC_Product_Variation($id);
			if ($variation->delete()) {
				$response = array(
					'status' => 'success',
					'message' => esc_html__('Xóa thành công thuộc tính', 'api-code-wp'),
					'data' => array(),
				);
				return new WP_REST_Response($response, 200);
			}
		} elseif($type == 'order') {
			$order = wc_get_order($id);
			if($order) {
				$order->delete();
				$response = array(
					'status' => 'success',
					'message' => esc_html__('Xóa thành công đơn hàng', 'api-code-wp'),
					'data' => array(),
				);
				return new WP_REST_Response($response, 200);
			}
		} elseif ($type == 'product_cat' || $type == 'category') {
			if (term_exists($id, $type)) {
				$result = wp_delete_term($id, $type);

				if (is_wp_error($result)) {

					$error_message = $result->get_error_message();
					$response = array(
						'status' => 'error',
						'message' => esc_html($error_message),
						'data' => array(),
					);
					return new WP_REST_Response($response, 200);

				} else {
					$response = array(
						'status' => 'success',
						'message' => esc_html__('Xóa thành công danh mục', 'api-code-wp'),
						'data' => array(),
					);
					return new WP_REST_Response($response, 200);
				}

				
			}
		} elseif($type == 'attributes_product') {
			wc_delete_attribute($id);
			
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Xóa thuộc tính thành công', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		} else {
			if (term_exists($id, $type)) {
				$result = wp_delete_term($id, $type);

				if (is_wp_error($result)) {
					$error_message = $result->get_error_message();
					$response = array(
						'status' => 'error',
						'message' => esc_html($error_message),
						'data' => array(),
					);
					return new WP_REST_Response($response, 200);

				} else {
					$response = array(
						'status' => 'success',
						'message' => esc_html__('Xóa thành công', 'api-code-wp'),
						'data' => array(),
					);
					return new WP_REST_Response($response, 200);
				}

			
			}
		}


	}


	public function get_private_order_notes($order_id)	{
		global $wpdb;
		$table_perfixed = $wpdb->prefix . 'comments';
		$query = $wpdb->prepare("
        SELECT *
        FROM $table_perfixed
        WHERE comment_post_ID = %d
        AND comment_type LIKE 'order_note'
    ", $order_id);

		$results = $wpdb->get_results($query);

		$order_notes = array();
		foreach ($results as $note) {
			$order_notes[] = array(
				'note_id'      => intval($note->comment_ID),
				'note_date'    => esc_html($note->comment_date),
				'note_author'  => sanitize_text_field($note->comment_author),
				'note_content' => sanitize_textarea_field($note->comment_content),
			);
		}

		return $order_notes;
	}



	public function create_token_app(\WP_REST_Request $param) {
		$params = $param->get_params();
		$token = isset($params['token']) ? sanitize_text_field($params['token']) : '';

		

		$token = str_replace('ExponentPushToken','', $token);
		
		$date_request = gmdate('d/m/Y H:i:s'); 
		
		update_option( 'codewp_api_for_android_ios_alert', $token);
		update_option( 'codewp_api_for_android_ios_log', $date_request);
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Bật thông báo thành công', 'api-code-wp'),
			'data' => array(),
		);
		return new WP_REST_Response($response, 200);
	}

	public function upload_image($file, $title = null) {
		if (!isset($file['error']) || is_array($file['error'])) {
			return 0;
		}


		require_once(ABSPATH . 'wp-admin/includes/file.php');
		$upload_overrides = array('test_form' => false);
		$movefile = wp_handle_upload($file, $upload_overrides);

		if ($movefile && !isset($movefile['error'])) {
	
			$image_type = wp_check_filetype(basename($movefile['file']), null);
			$image_title = $title ? $title : basename($movefile['file']);


			$attachment = array(
				'guid' => $movefile['url'],
				'post_mime_type' => $image_type['type'],
				'post_title' => sanitize_text_field($image_title),
				'post_content' => '',
				'post_status' => 'inherit'
			);

	
			$attach_id = wp_insert_attachment($attachment, $movefile['file']);


			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
			wp_update_attachment_metadata($attach_id, $attach_data);

			return $attach_id; 
		} else {
			return 0; 
		}
	}


	public function upload_and_remove_gallery(\WP_REST_Request $param) {
		$params = $param->get_params();
		$product_id = isset($params['product_id']) ? absint($params['product_id']) : 0;
		$image_id = isset($params['image_id']) ? absint($params['image_id']) : 0;

		
		if($image_id == '') {
			if($_FILES['thumb']['name'] != '' || $_FILES['thumb'] != '') {
				
				$this->attach_product_thumbnail($product_id,$_FILES['thumb'],2);
				$response = array(
					'status' => 'success',
					'message' => esc_html__('Thêm ảnh vào gallery thành công', 'api-code-wp'),
					'data' => array(),
				);
				return new WP_REST_Response($response, 200);
			
			} else {
				$response = array(
					'status' => 'error',
					'message' => esc_html__('Bạn chưa chọn ảnh để tải lên', 'api-code-wp'),
					'data' => array(),
				);
				return new WP_REST_Response($response, 200);
			}
			
			
		} else {
			$product = wc_get_product($product_id);
			$gallery_ids = $product->get_gallery_image_ids();
			$index = array_search($image_id, $gallery_ids);
			if ($index !== false) {
				unset($gallery_ids[$index]);
				$product->set_gallery_image_ids($gallery_ids);
				$product->save();

				
				$response = array(
					'status' => 'error',
					'message' => esc_html__('Xóa thành công hình ảnh ID', 'api-code-wp'),
					'data' => array(),
				);

				return new WP_REST_Response($response, 200);


			}
		}
		
	}

	public function get_variation(\WP_REST_Request $param) {
		$params = $param->get_params();
		$pa = isset($params['pa']) ? sanitize_text_field($params['pa']) : '';
		$select = isset($params['select']) ? sanitize_text_field($params['select']) : '';
		$color = isset($params['color']) ? sanitize_text_field($params['color']) : '';
		$variation_id = isset($params['product_id']) ? absint($params['product_id']) : 0;


		$variation = new WC_Product_Variation($variation_id);
        $get_attributes = $variation->get_attributes();
		foreach($get_attributes as $key => $get_attributes_item) {
			$taxonomy = str_replace('pa_', '', $key);
			$get_taxonomy = get_taxonomy($key);
			$taxonomy_name = str_replace('Sản phẩm', '', $get_taxonomy->labels->name);
			$status_data_new[] = array(
				'label' => $taxonomy_name,
				'value' => $taxonomy,
				'selected' => false,
			);
		}
		
		
		if($pa == '') {
			$attributes = wc_get_attribute_taxonomies();
			foreach($attributes as $attributes_item) { $count++;
			
				$attribute_public = $attributes_item->attribute_public;
		
				$terms = get_terms( array(
					'taxonomy'   => 'pa_'.$attributes_item->attribute_name,
					'hide_empty' => false,
				) );
			
				if(count($terms) >= 1) {
					$fix_select = str_replace("pa_","",$select);
					if(!empty($variation_id)) {
						$status_data = $status_data_new;
					} else {
						$status_data[] = array(
							'label' => $attributes_item->attribute_label,
							'value' => $attributes_item->attribute_name,
							'selected' => ($fix_select == $attributes_item->attribute_name) ? true : false,
							
						); 	
					}
					
				}
				
			}
			
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
				'data' => $status_data,
				'type' => 'pa_color',
			);
			return new WP_REST_Response($response, 200);
		} else {
			$terms = get_terms( array(
				'taxonomy'   => 'pa_'.$pa,
				'hide_empty' => false,
			) );
			foreach($terms as $terms_item) {
				
				$status_data[] = array(
					'label' => $terms_item->name,
					'value' => $terms_item->slug,
					'selected' => ($color == $terms_item->slug) ? true : false,
					
				); 
			}
			
			$status_data[] = array(
				'label' => esc_html__('Chọn','api-code-wp'),
				'value' => '',
				'selected' => false,
				
			); 	
			
			
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
				'data' => $status_data,
				'type' => 'color',
			);
			return new WP_REST_Response($response, 200);
		}

	}

	public function get_status_order($status) {
		$wc_get_order_statuses = wc_get_order_statuses();
		foreach($wc_get_order_statuses as $key => $item) {
			
			$status_data[] = array(
				'label' => $item,
				'value' => $key,
				'selected' => ($key == 'wc-'.$status) ? true : false,
				
			); 
		}
		return $status_data;
	}

	public function get_status_post($status, $type = '') {
	
		$data_status = array(
			'publish' => esc_html__('Xuất bản', 'api-code-wp'),
			'pending' => esc_html__('Chờ xử lý', 'api-code-wp'),
			'draft' => esc_html__('Nháp', 'api-code-wp'),
			'future' => esc_html__('Nổi bật', 'api-code-wp'),
			'private' => esc_html__('Riêng tư', 'api-code-wp'),
			'trash' => esc_html__('Thùng rác', 'api-code-wp'),
		);

		if ($type == '') {
			
			$status_data = array();

			foreach ($data_status as $key => $item) {
				$status_data[] = array(
					'label' => $item,
					'value' => $key,
					'selected' => ($key == $status) ? true : false,
				);
			}

			return $status_data;
		} else {
		
			return isset($data_status[$status]) ? $data_status[$status] : '';
		}
	}

	public function get_product_type($status) {
		$data_type = array(
			'simple' => esc_html__('Sản phẩm đơn giản','api-code-wp'),
			'variable' => esc_html__('Sản phẩm có biến thể','api-code-wp'),
		);
		
		foreach($data_type as $key => $data_type_item) {
			$status_data[] = array(
				'label' => $data_type_item,
				'value' => $key,
				'selected' => ($key == $status) ? true : false,
				
			); 
		}
		return $status_data;
		
	}
	public function send_notifi_app($order_id) {
		$token = get_option('codewp_api_for_android_ios_alert', true);
		$home_url = get_home_url();
		
		$order_message = sprintf(
			// Translators: %1$d is the order ID, %2$s is the website URL.
			esc_html__('Đơn hàng ID %1$d từ website %2$s', 'api-code-wp'),
			$order_id,
			esc_url($home_url)
		);

		$data = array(
			'to' => 'ExponentPushToken' . sanitize_text_field($token),
			// Translators: This is a notification title indicating a new order.
			'title' => esc_html__('Bạn có đơn hàng mới nè 📬', 'api-code-wp'),
			'body' => $order_message,
			'sound' => 'default',
		);




		
		$args = array(
			'body'        => wp_json_encode($data),
			'headers'     => array('Content-Type' => 'application/json'),
			'timeout'     => 30,
			'redirection' => 5,
			'blocking'    => true,
		);
		$response = wp_remote_post('https://exp.host/--/api/v2/push/send', $args);

		if (is_wp_error($response)) {
			return false;
		}
		// Documenting the use of 3rd party service
		/**
		 * This method uses the Expo Push Notification Service to send push notifications.
		 * Service URL: https://exp.host/--/api/v2/push/send
		 * Service Terms: https://code-wp.com/chinh-sach-bao-mat/
		 * Service Privacy Policy: https://code-wp.com/chinh-sach-bao-mat/
		 */
		$result = wp_remote_retrieve_body($response);
		update_option('codewp_api_for_android_ios_notify', esc_html($result));
	}

	public function send_notifi_contact_form_7($cf7)
	{
		$wpcf = WPCF7_ContactForm::get_current();
		$token = get_option('codewp_api_for_android_ios_alert', true);
		$home_url = get_home_url();

		
		$body_message = sprintf(
			// Translators: %1$d is the contact form ID, %2$s is the website URL.
			esc_html__('#%1$d liên hệ mới từ website %2$s', 'api-code-wp'),
			$wpcf->id,
			esc_url($home_url)
		);

		$data = array(
			'to' => 'ExponentPushToken' . sanitize_text_field($token),
			'title' => esc_html__('Bạn có liên hệ mới nè 📬', 'api-code-wp'),
			'body' => $body_message,
			'sound' => 'default',
		);

		$args = array(
			'body'        => wp_json_encode($data),
			'headers'     => array('Content-Type' => 'application/json'),
			'timeout'     => 30,
			'redirection' => 5,
			'blocking'    => true,
		);

		// Documenting the use of 3rd party service
		/**
		 * This method uses the Expo Push Notification Service to send push notifications.
		 * Service URL: https://exp.host/--/api/v2/push/send
		 * Service Terms: https://code-wp.com/chinh-sach-bao-mat/
		 * Service Privacy Policy: https://code-wp.com/chinh-sach-bao-mat/
		 */
		$response = wp_remote_post('https://exp.host/--/api/v2/push/send', $args);

		if (is_wp_error($response)) {
			return false;
		}

		wp_remote_retrieve_body($response);
	}


	public function get_contact_form_7(\WP_REST_Request $param) {
		global $wpdb;
		
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		if (!is_plugin_active( 'contact-form-cfdb7/contact-form-cfdb-7.php' ) ) {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn chưa cài đặt Plugin Contact Form CFDB7', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
		
		
		
        $cfdb         = apply_filters( 'cfdb7_database', $wpdb );
        $data         = array();
        $table_name   = $cfdb->prefix.'db7_forms';

		$results  = $cfdb->get_results( "SELECT * FROM $table_name", OBJECT);
		
		foreach($results as $item) {
			$date = date_create($item->form_date);

			$data_contact = unserialize($item->form_value);

			$data[] = [
				'form_id' => (int) $item->form_post_id,
				'id' => (int) $item->form_id, 
				'title_form' => sanitize_text_field(get_the_title($item->form_post_id)),
				 /* translators: #%1$d: ID contact form */
				'title' => sprintf( 
					esc_html__(' #%1$d Liên hệ', 'api-code-wp'),
					(int) $item->form_id 
				),
				'data' => $data_contact,
				'date' => date_format($date, "d/m/Y H:i:s"),
				'status' => isset($data_contact['cfdb7_status']) ? sanitize_text_field($data_contact['cfdb7_status']) : '', 
			];

			
			
		}
		
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
			'data' => $data,
		);
		return new WP_REST_Response($response, 200);
	}




	public function get_contents(\WP_REST_Request $param) {
		$params = $param->get_params();
		$post_id = isset($params['post_id']) ? absint($params['post_id']) : 0;
		$post_content = get_post_field('post_content', $post_id);

		$dom = new DOMDocument();
		@$dom->loadHTML(mb_convert_encoding($post_content, 'HTML-ENTITIES', 'UTF-8'));

		$images = $dom->getElementsByTagName('img');

		foreach ($images as $img) {
			$img->setAttribute('width', '300px');
			$img->setAttribute('height', 'auto');
		}

		$new_post_content = $dom->saveHTML();
		
		$response = array(
			'status' => 'success',
			'message' => esc_html__('Lấy dữ liệu thành công', 'api-code-wp'),
			'data' => wp_kses_post($new_post_content),
		);
		return new WP_REST_Response($response, 200);
	}
	public function get_seo_data(\WP_REST_Request $param) {
		$params = $param->get_params();
		$post_id = isset($params['post_id']) ? absint($params['post_id']) : 0;

		if (class_exists('WPSEO_Meta')) {
			$title = get_post_meta($post_id,'_yoast_wpseo_title',true);
			$title2 = get_the_title($post_id);
			
			$description = get_post_meta($post_id,'_yoast_wpseo_metadesc',true);
			
			$data = array(
				'title' => $title ? $title : $title2,
				'description' => esc_html($description),
				'id' => $post_id,
				'plugin' => esc_html__('Yoast SEO', 'api-code-wp'),
			);
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy thông tin thành công', 'api-code-wp'),
				'data' => $data,
			);
			return new WP_REST_Response($response, 200);
		} else if (class_exists('RankMath')) {
			$title = get_post_meta($post_id,'rank_math_title',true);
			$title2 = get_the_title($post_id);
			$description = get_post_meta($post_id,'rank_math_description',true);
			$data = array(
				'title' => $title ? $title : $title2,
				'description' => $description ? esc_html($description) : '',
				'id' => $post_id,
				'plugin' => esc_html__('Rankmath', 'api-code-wp')
			);
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Lấy thông tin thành công', 'api-code-wp'),
				'data' => $data,
			);
			return new WP_REST_Response($response, 200);
		} else {
			
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn chưa cài đặt Yoast SEO hoặc RankMatch', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
	}
	public function update_seo(\WP_REST_Request $param) {
		$params = $param->get_params();
		$post_id = isset($params['id']) ? absint($params['id']) : 0;
		$title = isset($params['title']) ? esc_html($params['title']) : '';
		$description = isset($params['description']) ? esc_html($params['description']) : '';

		
		if (class_exists('WPSEO_Meta')) {
			update_post_meta($post_id, '_yoast_wpseo_metadesc', $description);
			update_post_meta($post_id, '_yoast_wpseo_title', $title);
			
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Cập nhật dữ liệu thành công', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		} else if (class_exists('RankMath')) {
			update_post_meta($post_id, 'rank_math_title', $title);
			update_post_meta($post_id, 'rank_math_description', $description);
			
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Cập nhật dữ liệu thành công', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		} else {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn chưa cài đặt Yoast SEO hoặc RankMatch', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}
		
	}


	private function generate_token($length = 32) {
		return bin2hex(random_bytes($length));
	}

	public function user_login(\WP_REST_Request $param) {
		$params = $param->get_params();
		$username = isset($params['username']) ? sanitize_text_field($params['username']) : '';
		$password = isset($params['password']) ? $params['password'] : '';


		if (empty($username) || empty($password)) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Tài khoản và mật khẩu không được bỏ trống', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		$user = wp_authenticate($username, $password);

		if (is_wp_error($user)) {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Tài khoản hoặc mật khẩu không chính xác', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}


		if (user_can($user, 'administrator')) {

			$user_id = $user->ID;
			$data = array(
				'user_id' => $user_id,
				'display_name' => $user->display_name,
				'email' => $user->user_email,
				'token' => esc_attr(get_option('codewp_api_for_android_ios_security_token')),
				'domain'=> esc_url(get_home_url()),
			);
			$response = array(
				'status' => 'success',
				'message' => esc_html__('Đăng nhập thành công', 'api-code-wp'),
				'data' => $data,
			);
			return new WP_REST_Response($response, 200);
		

		} else {
			$response = array(
				'status' => 'error',
				'message' => esc_html__('Bạn không có quyền truy cập vào ứng dụng', 'api-code-wp'),
				'data' => array(),
			);
			return new WP_REST_Response($response, 200);
		}

		
	
			
	}

	public function wp_editor(\WP_REST_Request $param) {
		$params = $param->get_params();
		$user_id = isset($params['user_id']) ? absint($params['user_id']) : 0;

		$user = get_userdata($user_id);
		if (!$user) {
			return new WP_REST_Response(esc_html__('Người dùng không tồn tại', 'api-code-wp'), 400);
		}

		$user_roles = $user->roles;

		if (in_array('administrator', $user_roles, true)) {

			$token = get_user_meta($user_id, 'codewp_api_for_android_ios_login_token', true);
			$expiry_time = get_user_meta($user_id, 'codewp_api_for_android_ios_token_expiry', true);

			if (isset($token) && isset($expiry_time)) {
				if (time() < $expiry_time) {

					if (isset($_COOKIE['login_token']) && $_COOKIE['login_token'] === $token) {
						wp_set_auth_cookie($user_id);
						wp_redirect(admin_url());
						exit;
					}
				} else {

					delete_user_meta($user_id, 'codewp_api_for_android_ios_login_token');
					delete_user_meta($user_id, 'codewp_api_for_android_ios_token_expiry');
					setcookie('login_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
				}
			}


			$token = $this->generate_token();
			$expiry_time = time() + 3600;


			update_user_meta($user_id, 'codewp_api_for_android_ios_login_token', sanitize_text_field($token));
			update_user_meta($user_id, 'codewp_api_for_android_ios_token_expiry', sanitize_text_field($expiry_time));

			setcookie('login_token', sanitize_text_field($token), sanitize_text_field($expiry_time), COOKIEPATH, COOKIE_DOMAIN);

			wp_set_auth_cookie($user_id);
			wp_redirect(admin_url());
			exit;
		} else {
			return new WP_REST_Response(esc_html__('Bạn không có quyền truy cập','api-code-wp'), 400);
		}
		
		
	}
}