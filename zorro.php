<?php
/*
Plugin Name: Zorro
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: The Plugin's Version Number, e.g.: 1.0
Author: Name Of The Plugin Author
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

include('lib/base62-encode.php');


register_activation_hook( __FILE__, array('Zorro', 'installZorro') );

add_filter('query_vars', 'add_my_var');

add_action('init','wptuts_custom_tags'); 
add_action('wp', array('Zorro', 'commandController'));

add_shortcode('zorro', array('Zorro', 'shortcode'));



class Zorro {
  
  const TABLE_URL = 'zorro_urls';
  
  const TABLE_TEMP_CLICKS = 'zorro_clicks_temporary';
  
  
  static function getTableUrls() {
    
    global $wpdb;
    
    return ($wpdb->prefix . self::TABLE_URL);
    
  }
  
  static function shortcode($attributes, $content) {
    
    extract(shortcode_atts(array('url' => '/'),$attributes));
    
    $url = self::getShortUrl($url);
    
    if (trim($content) == '') {
      
      $content = $url;
      
    }
    
    return '<a href="'. $url .'">'. $content .'</a>';
    
    
  }
  
  
  static function commandController() {
    
    global $wp_query;
    
   
    
    $command = get_query_var('zorro_cmd');
    
    
    if ($command == 'redirect') {
      
      #die();
      
      self::handleRedirect();
      
    }
    
  }
  
  static function handleRedirect() {
    
    $shorturl = get_query_var('shorturl_id');
    
    $url = self::getUrlById($shorturl);
    
    if ($url !== false) {
      
      wp_redirect($url, 301);
      exit();
      
    } else {
      
      die('555');
      
    }
    
  }
  
  
  static function installZorro() {
    
    $sql = "CREATE TABLE ". self::getTableUrls() ." (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        url varchar(2560) NOT NULL,
        PRIMARY KEY (id),
        KEY url (url(767)))";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  
    dbDelta( $sql );
    
    
  }
  
  static function getUrlById($shorturl_id) {
    
    global $wpdb;
    
    $db_id = Base62::convert($shorturl_id, 62, 10);
    
    $sql = "select url from ". self::getTableUrls() . " where id = %d";
    
    $url = $wpdb->get_var($wpdb->prepare( $sql ,$db_id));
    
    if ($url == null) {
      
      return false;
      
    } 
    
    return $url;
    
  }
  
  
  static function createShortUrl($url) {
    
    global $wpdb;
    
    
    $id = self::shortUrlExists($url);
    
    
    if ($id !== false) return $id->id;
    
    
    $id = $wpdb->insert( self::getTableUrls(), 
      array(  
        'url' => $url 
        ), 
      array( 
        '%s' 
      ) 
    );
    
    
    if ($id !== false) {
      
      return $wpdb->insert_id;
      
    } else {
      
      return false;
      
    }
    
    
  }
  
  /**
   * Checks if a static url exists
   * 
   * @global type $wpdb
   * @param type $url
   * @return boolean/Object
   */
  static function shortUrlExists($url) {
    
    global $wpdb;
    
    $sql = 'select * from ' .  self::getTableUrls() . ' where url = %s';
    
    
    $id = $wpdb->get_row($wpdb->prepare($sql, $url));
    
    
    
    if ($id == NULL) {
      
      return false;
      
    } else {
      
      return $id;
      
    }
    
  }
  
  static function buildUrl($id) {
    
    return home_url("/r/") . Base62::convert($id). '/';
    
  }
  
  
  static function getShortUrl($url) {
    
    $id = self::createShortUrl($url);
      
    
    if ($id === false) {
      
      return $url;
      
    } else {
      
      return self::buildUrl($id);
      
    }
    
    
  }
  
  
}

function add_my_var($public_query_vars) {
    
    $public_query_vars[] = 'shorturl_id';
    $public_query_vars[] = 'zorro_cmd';
    
    return $public_query_vars;
}





function wptuts_custom_tags() {  
   add_rewrite_rule("^r/([^/])?",'index.php?zorro_cmd=redirect&shorturl_id=$matches[1]','top');  
  #add_rewrite_rule("^r/([^/])?",'test.php?_cmd_=zorro_redirect&shorturl_id=$matches[1]','top');  
}  
    
 
    

if (!function_exists('gg')) {
  
  function gg($key){

    if (isset($_GET) && isset($_GET[$key]) && trim($_GET[$key]) != '') {

      return trim($_GET[$key]);

    } else {

      return false;

    }

  }

}



?>