<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Moo_OnlineOrders_Helpers
{
    public static function getCharsetOfDbTable($table_name) {
        global $wpdb;
        $result = $wpdb->get_row("SHOW CREATE TABLE `{$wpdb->prefix}$table_name`",'ARRAY_A');
        if (isset($result["Create Table"])){
            preg_match('/CHARSET=([^\s]+)/', $result['Create Table'], $matches);
            if (isset($matches[0])){
               $matches = explode('=',$matches[0]);
               if (isset($matches[1])){
                   return $matches[1];
               }
            }
        }
        return $wpdb->charset;
    }
    public static function getCharsetOfDbColumn($table_name,$column_name) {
        global $wpdb;
        $results = $wpdb->get_results("SHOW FULL COLUMNS FROM `{$wpdb->prefix}$table_name`",'ARRAY_A');
        foreach ($results as $result) {
            if(isset($result['Field']) && $result['Field'] === $column_name ){
               if (isset($result['Collation'])){
                   return $result['Collation'];
               }
            }
        }
        return $wpdb->collate;
    }
}