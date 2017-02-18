<?php
/*
Plugin Name: Wp-ftp-media-library--SH-fork
Plugin URI: http://wordpress.stackexchange.com/questions/74180/upload-images-to-remote-server
Description: Let's you upload images to ftp-server and remove the upload folder from the local machine.
Version: 0.2
Author: Sheryl Hohman: v2 define external server constants in wp-config. forked from orig Author: Pontus Abrahamsson; 
Author URI: http://pontusab.se (original author)

*/

/**
 * @version 0.2
 */

function wpse_74180_upload_to_ftp( $args ) {

	$upload_dir = wp_upload_dir();
	$upload_url = get_option('upload_url_path');
	$upload_yrm = get_option('uploads_use_yearmonth_folders');


	/**
	 * Change this to match your server
	 * You only need to change the those with (*)
	 * If marked with (-) its optional 
	 */
	
	// 170216 SH: Define these constants in wp-config.php for security
        // EXIT out of this function if any of these constants NOT defined
	
	if (!defined(SH_UPLOADS_FTP_SERVER_HOSTNAME) or 
	   !defined(SH_UPLOADS_FTP_SERVER_PORT) or 
	   !defined(SH_UPLOADS_FTP_SERVER_USERNAME) or 
	   !defined(SH_UPLOADS_FTP_SERVER_PASSWORD) or
	   !defined(SH_UPLOADS_FTP_SERVER_DOMAIN_NAME))
		exit("constants for external uploads server are undefined");
	
	// create default value - this param need not be custom-defined in wp-config
	defined(SH_UPLOADS_FTP_SERVER_DOMAIN_NAME) or define(SH_UPLOADS_FTP_SERVER_DOMAIN_NAME, '/')

	$settings = array(

		'host'	  =>  SH_UPLOADS_FTP_SERVER_HOSTNAME,     // * the ftp-server hostname, ie:
		'port'    =>  SH_UPLOADS_FTP_SERVER_PORT,         // * the ftp-server port (of type int), ie: 21
		'user'	  =>  SH_UPLOADS_FTP_SERVER_USERNAME,     // * ftp-user, ie: 'username'
		'pass'	  =>  SH_UPLOADS_FTP_SERVER_PASSWORD,	  // * ftp-password, ie: password'
		'cdn'     =>  SH_UPLOADS_FTP_SERVER_DOMAIN_NAME,  // * domain or subdomain name to the root of the uploads, ie: 'cdn.example.com'
		'path'	  =>  SH_UPLOADS_FTP_SERVER_FTP_ROOT_PATH,// - ftp-path, default is root ('/'). 
								  //     Change here, and add the dir on the ftp-server,
		'base'	  =>  $upload_dir['basedir']  	          // Basedir on local 
	);


	/**
	 * Change the upload url to the ftp-server
	 */

	if( empty( $upload_url ) ) {
		update_option( 'upload_url_path', esc_url( $settings['cdn'] ) );
	}


	/**
	 * Host-connection
	 * Read about it here: http://php.net/manual/en/function.ftp-connect.php
	 */
	
	$connection = ftp_connect( $settings['host'], $settings['port'] );


	/**
	 * Login to ftp
	 * Read about it here: http://php.net/manual/en/function.ftp-login.php
	 */

	$login = ftp_login( $connection, $settings['user'], $settings['pass'] );

	
	/**
	 * Check ftp-connection
	 */

	if ( !$connection || !$login ) {
	    die('Connection attempt failed, Check your settings');
	}


	function ftp_putAll($conn_id, $src_dir, $dst_dir, $created) {
            $d = dir($src_dir);
	    // for each file in the directory..
	    while($file = $d->read()) {
		// prevent an infinite loop
	        if ($file != "." && $file != "..") {
		    // if the 'file' it is a directory
	            if (is_dir($src_dir."/".$file)) { 
	                if (!@ftp_chdir($conn_id, $dst_dir."/".$file)) {
			    // create directories that do not yet exist
	                    ftp_mkdir($conn_id, $dst_dir."/".$file); 
	                }
			// recurse
	                $created  = ftp_putAll($conn_id, $src_dir."/".$file, $dst_dir."/".$file, $created); 
	            } else {
			// put the files here
	                $upload = ftp_put($conn_id, $dst_dir."/".$file, $src_dir."/".$file, FTP_BINARY); 
	                if($upload)
	                	$created[] = $src_dir."/".$file;
	            }
	        }
	    }
	    $d->close();
	    return $created;
	}

	/**
	 * If we ftp-upload successfully, mark it for deletion
	 * http://php.net/manual/en/function.ftp-put.php
	 */
	$delete = ftp_putAll($connection, $settings['base'], $settings['path'], array());
	


	// Delete all successfully-copied files
	foreach ( $delete as $file ) {
		unlink( $file );
	}
	
	return $args;
}
add_filter( 'wp_generate_attachment_metadata', 'wpse_74180_upload_to_ftp' );
