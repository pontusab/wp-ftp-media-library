<?php

if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/cmb2/init.php';
} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/CMB2/init.php';
}

function get_field($field_name,$post_id){
	$post_id = (empty($post_id))?get_the_ID():$post_id;
	return get_post_meta($post_id,$field_name);
}

function wfm_add_theme_option(){
	$cmb_options = new_cmb2_box(array(
		'id' => 'wfm_options',
		'title' => esc_html__('WP FTP Media','wp-ftp-media-library'),
		'object_types' => array('options-page'),
		'option_key' => 'wfm_options',
		'menu_title' => esc_html__('WP FTP Media','wp-ftp-media-library'),
		'parent_slug' => 'options-general.php'
	));
	$cmb_options->add_field(array(
		'name' => esc_html__(__('FTP Host Name', 'wp-ftp-media-library')),
		'desc' => esc_html__(__('The ftp-server hostname, ip or hostname.', 'wp-ftp-media-library')),
		'id' => 'wfm_hostname',
		'type' => 'text',
		'attributes' => array(
			'placeholder' => esc_html__(__('ex: 123.123.123.123 or domain.com', 'wp-ftp-media-library'))
		)
	));
	$cmb_options->add_field(array(
		'name' => esc_html__(__('FTP Port', 'wp-ftp-media-library')),
		'desc' => esc_html__(__('The ftp-server port (of type int)', 'wp-ftp-media-library')),
		'id' => 'wfm_port',
		'type' => 'text',
		'default' => '21',
		'attributes' => array(
			'type' => 'number',
			'maxlenth' => '2',
			'pattern' => '\d*'
		)
	));
	$cmb_options->add_field(array(
		'name' => esc_html__(__('FTP Username', 'wp-ftp-media-library')),
		'desc' => esc_html__(__('The ftp-user', 'wp-ftp-media-library')),
		'id' => 'wfm_username',
		'type' => 'text'
	));
	$cmb_options->add_field(array(
		'name' => esc_html__(__('FTP Password', 'wp-ftp-media-library')),
		'desc' => esc_html__(__('The ftp-password', 'wp-ftp-media-library')),
		'id' => 'wfm_password',
		'type' => 'text',
		'attributes' => array(
			'type' => 'password'
		)
	));
	$cmb_options->add_field(array(
		'name' => esc_html__(__('FTP Root Path', 'wp-ftp-media-library')),
		'desc' => esc_html__(__('This have to be a pointed domain or subdomain to the root of the uploads', 'wp-ftp-media-library')),
		'id' => 'wfm_cdn',
		'type' => 'text',
		'attributes' => array(
			'placeholder' => 'ex: https://img.domain.com'
		)
	));
	$cmb_options->add_field(array(
		'name' => esc_html__(__('FTP Folder Path', 'wp-ftp-media-library')),
		'desc' => esc_html__(__('The ftp-path, default is root (/). Change here and add the dir on the ftp-server', 'wp-ftp-media-library')),
		'id' => 'wfm_path',
		'default' => '/',
		'type' => 'text'
	));
}

add_action('cmb2_admin_init','wfm_add_theme_option');