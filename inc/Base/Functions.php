<?php
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Base;


class Functions{
    public function register(){
        require_once( ABSPATH . 'wp-includes/option.php' );
        add_action('update_option_packaging_amount',[$this,'sync_db_supplements'], 10, 2);
        add_action('update_option_label_amount',[$this,'sync_db_supplements'], 10, 2);
        /*add_action('update_option_packaging_limit',[$this,'sync_db_supplements'], 10, 2);
        add_action('update_option_packaging_new_order_amount',[$this,'sync_db_supplements'], 10, 2);
        add_action('update_option_packaging_new_order_sent',[$this,'sync_db_supplements'], 10, 2);*/
        add_action('update_option_supplement_amount',[$this,'sync_db_supplements'], 10, 2);
        add_action('update_option_packaging_big_amount',[$this,'sync_db_supplements'], 10, 2);
        add_action( 'init', [$this, 'register_new_order_status'] );
        add_filter( 'wc_order_statuses', [$this, 'add_new_status_to_order_statuses'] );
    }

    //BEILAGEN DB-SYNCS
    function sync_db_supplements(){
        global $wpdb;
        $value = $wpdb->query("UPDATE babytuch_inventory SET amount=(SELECT option_value FROM wp_options
        WHERE option_name='packaging_amount') WHERE item_name='packagings'"); 
        $value = $wpdb->query("UPDATE babytuch_inventory SET amount=(SELECT option_value FROM wp_options
        WHERE option_name='label_amount') WHERE item_name='labels'");
        $value = $wpdb->query("UPDATE babytuch_inventory SET amount=(SELECT option_value FROM wp_options
        WHERE option_name='supplement_amount') WHERE item_name='supplements'");
        $value = $wpdb->query("UPDATE babytuch_inventory SET amount=(SELECT option_value FROM wp_options
        WHERE option_name='packaging_big_amount') WHERE item_name='packagings_large'");
    }
    
    //NEUER BESTELL STATUS
    function register_new_order_status() {
        register_post_status( 'wc-returning', array(
            'label'                     => 'returning',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Returning (%s)', 'Returning (%s)' )
        ) );
        register_post_status( 'wc-packing', array(
            'label'                     => 'packing',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Packing (%s)', 'Packing (%s)' )
        ) );

	    register_post_status( 'wc-return-received', array(
		    'label'                     => 'return-received',
		    'public'                    => true,
		    'exclude_from_search'       => false,
		    'show_in_admin_all_list'    => true,
		    'show_in_admin_status_list' => true,
		    'label_count'               => _n_noop( 'Return received (%s)', 'Return received (%s)' )
	    ) );

	    register_post_status( 'wc-refund-required', array(
		    'label'                     => 'refund-required',
		    'public'                    => true,
		    'exclude_from_search'       => false,
		    'show_in_admin_all_list'    => true,
		    'show_in_admin_status_list' => true,
		    'label_count'               => _n_noop( 'Refund required (%s)', 'Refund required (%s)' )
	    ) );


	    register_post_status( 'wc-action-required', array(
		    'label'                     => 'action-required',
		    'public'                    => true,
		    'exclude_from_search'       => false,
		    'show_in_admin_all_list'    => true,
		    'show_in_admin_status_list' => true,
		    'label_count'               => _n_noop( 'Action required (%s)', 'Action required (%s)' )
	    ) );

	    register_post_status( 'wc-replaced', array(
		    'label'                     => 'replaced',
		    'public'                    => true,
		    'exclude_from_search'       => false,
		    'show_in_admin_all_list'    => true,
		    'show_in_admin_status_list' => true,
		    'label_count'               => _n_noop( 'Replaced (%s)', 'Replaced (%s)' )
	    ) );

	    register_post_status( 'wc-partially-refunded', array(
		    'label'                     => 'partially-refunded',
		    'public'                    => true,
		    'exclude_from_search'       => false,
		    'show_in_admin_all_list'    => true,
		    'show_in_admin_status_list' => true,
		    'label_count'               => _n_noop( 'Partially refunded (%s)', 'Partially refunded (%s)' )
	    ) );

    }

    function add_new_status_to_order_statuses( $order_statuses ) {
        $new_order_statuses = array();
    
        foreach ( $order_statuses as $key => $status ) {
    
            $new_order_statuses[ $key ] = $status;

            // insert after processing status
            if ( 'wc-processing' === $key ) {
                $new_order_statuses['wc-packing'] = 'In Verpackungsprozess';
            }

            // insert after completed status
            if ( 'wc-completed' === $key ) {
                $new_order_statuses['wc-returning'] = 'Rückgesandt';
	            $new_order_statuses['wc-return-received'] = 'Rücksendung bei Logistik eingetroffen';
	            $new_order_statuses['wc-refund-required'] = 'Wartet auf Rückerstattung';
	            $new_order_statuses['wc-action-required'] = 'Handlungsbedarf';
	            $new_order_statuses['wc-replaced'] = 'Teilweise Rückerstattet';
	            $new_order_statuses['wc-replaced'] = 'Ersetzt';
            }

        }
        return $new_order_statuses;
    }

}