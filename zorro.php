<?php
/*
Plugin Name: Zorro URL Shortener
Plugin URI: https://github.com/tobias-redmann/zorro
Description: A URL Shortener for mascerade urls
Version: 1.0
Author: Tobias Redmann
Author URI: http://www.tricd.de
License: MIT
*/

include('lib/base62-encode.php');


register_activation_hook( __FILE__, array('Zorro', 'installZorro') );

add_filter('query_vars', array('Zorro','addQueryVars'));

add_action('init',  array('Zorro', 'addRewriteRule')); 
add_action('wp',    array('Zorro', 'commandController'));


add_shortcode('zorro', array('Zorro', 'shortcode'));



class Zorro {
  
  const TABLE_URL = 'zorro_urls';
  
  const TABLE_TEMP_CLICKS = 'zorro_clicks_temporary';
  
  
  /**
   * Returns the table name with prefix for the urls table
   * 
   * @global type $wpdb
   * @return type
   */
  static function getTableUrls() {
    
    global $wpdb;
    
    return ($wpdb->prefix . self::TABLE_URL);
    
  }
  
  static function getTemporaryTable() {
    
    global $wpdb;
    
    return ($wpdb->prefix . self::TABLE_TEMP_CLICKS);
    
  }
  
  
  /**
   * Creates the shortcode
   * 
   * @param type $attributes
   * @param type $content
   * @return type
   */
  static function shortcode($attributes, $content) {
    
    extract(shortcode_atts(array('url' => '/'), $attributes));
    
    $url = self::getShortUrl($url);
    
    if (trim($content) == '') {
      
      $content = $url;
      
    }
    
    return '<a href="'. $url .'">'. $content .'</a>';
    
    
  }
  
  
  /**
   * Controller to handle the zorro requests
   */
  static function commandController() {      
    
    $command = get_query_var('zorro_cmd');
    
    
    if ($command == 'redirect') {
      
      self::handleRedirect();
      
    }
    
  }
  
  static function handleRedirect() {
    
    $shorturl = get_query_var('shorturl_id');
    
    $url = self::getUrlById($shorturl);
    
    if ($url !== false) {
      
      self::trackClick(Base62::convert($shorturl,62,10));
      
      wp_redirect($url, 301);
      exit();
      
    } 
    
  }
  
  
  static function installZorro() {
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $sql = "CREATE TABLE ". self::getTableUrls() ." (
        id int(11) NOT NULL AUTO_INCREMENT,
        url varchar(2560) NOT NULL,
        PRIMARY KEY (id),
        KEY url (url(767)))";

    
    dbDelta( $sql );
    
    $temporary_tracking_table = "CREATE TABLE ". self::getTemporaryTable() ." (
      id int(11) NOT NULL AUTO_INCREMENT,
      url_id int(11) NOT NULL,
      click_time datetime NOT NULL,
      ip varchar(30),
      referrer varchar(2560),
      browser varchar(2560),
      PRIMARY KEY (id)
     ) ";
    
    dbDelta($temporary_tracking_table);
    
    
  }
  
  static function trackClick($shorturl_id) {
    
    global $wpdb;
    
    $args = array();
    
    $types = array();
    
    // id
    $args['url_id'] = $shorturl_id;
    $types[]        = '%d';
   
    // click_time
    $args['click_time'] = current_time('mysql');
    $types[]            = '%s';
    
    // ip
    if (isset($_SERVER['REMOTE_ADDR'])) {
      
      $args['ip'] = $_SERVER['REMOTE_ADDR'];
      $types[]    = '%s';
      
    }
    
    // referrer
    if (isset($_SERVER['HTTP_REFERER'])) {
      
      $args['referrer'] = $_SERVER['HTTP_REFERER'];
      $types[]          = '%s';
      
    }
    
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      
      $args['browser']  = $_SERVER['HTTP_USER_AGENT'];
      $types[]          = '%s';
      
    }
    
    $wpdb->insert( self::getTemporaryTable(), 
      $args, 
      $types 
    );
    
    
    
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
  
  
  function addQueryVars($public_query_vars) {
    
    $public_query_vars[] = 'shorturl_id';
    $public_query_vars[] = 'zorro_cmd';
    
    return $public_query_vars;
  }
  


  function addRewriteRule() {  
  
    add_rewrite_rule("^r/([^/])?",'index.php?zorro_cmd=redirect&shorturl_id=$matches[1]','top');  

  } 
  
  
}






 
    
 
    



?>