<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CV_Subscriptions_List_Table extends WP_List_Table {
    
    function __construct() {
        global $status, $page;

        parent::__construct(array(
            'singular' => __('cv_subscript', 'cv_subscriptions'),
            'plural' => __('cv_subscript', 'cv_subscriptions'),
            'ajax' => false
        ));

        add_action('admin_head', array(&$this, 'admin_header'));
    }

    function admin_header() {
        $page = ( isset($_GET['page']) ) ? esc_attr($_GET['page']) : false;        
    }

    function no_items() {
        _e('No subscription found.');
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'subscription_id':
            case 'subscriptions_status':
            case 'product_id':
            case 'product_name':            
            case 'qty':
            case 'product_total':
            case 'currency':
            case 'billing_period':
            case 'billing_interval':
            case 'customer_id':
            case 'billing_first_name':
            case 'billing_last_name':
            case 'schedule_next_payment':   
            case 'completed_date':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'schedule_next_payment' => array('schedule_next_payment', false),
            'completed_date' => array('completed_date', false),            
            'product_name' => array('product_name', false),
            'billing_last_name' => array('billing_last_name', false),
            'qty' => array('qty', false),
            'billing_period' => array('billing_period', false),
            'subscriptions_status' => array('subscriptions_status', false)
        );
        return $sortable_columns;
    }

    function get_columns() {
        $columns = array(            
            'subscription_id' => __('ID', 'cv_listtable'),
            'subscriptions_status' => __('Status', 'cv_listtable'),            
            'product_name' => __('Product', 'cv_listtable'),
            'qty' => __('QTY', 'cv_listtable'),
            'product_total' => __('Total', 'cv_listtable'),
            'currency' => __('Cur', 'cv_listtable'),
            'billing_period' => __('Period', 'cv_listtable'),
            'billing_interval' => __('Int', 'cv_listtable'),
            'customer_id' => __('Customer ID', 'cv_listtable'),
            'billing_first_name' => __('First Name', 'cv_listtable'),
            'billing_last_name' => __('Last Name', 'cv_listtable'),
            'completed_date' => __('Completed Date', 'cv_listtable'),            
            'schedule_next_payment' => __('Next Payment', 'cv_listtable'),            
        );
        return $columns;
    }
/*
    function get_head_cv() {
        $heads = []; 
        foreach($this->get_columns()){
            
        }
    }
    $v_csv = CV_Subscriptions_List_Table::get_data_cv();        
        
  */  
    function usort_reorder($a, $b) {        
        $orderby = (!empty($_GET['orderby']) ) ? $_GET['orderby'] : 'product_name';        
        $order = (!empty($_GET['order']) ) ? $_GET['order'] : 'asc';
        $result = strcmp($a[$orderby], $b[$orderby]);
        
        return ( $order === 'asc' ) ? $result : -$result;
    }

   
    static function get_data() {
        global $wpdb;
        $table_data = $wpdb->get_results("        
                SELECT  subscription_line_items.subscription_id, 
                        subscriptions.post_status AS subscriptions_status,
                        product.id AS product_id, 
                        product.post_title AS product_name, 
                        product.post_status, 
                        mo.product_type, 
                        subscription_line_items.qty, 
                        subscription_line_items.product_total,
                        subscription_meta.currency, 
                        subscription_meta.billing_period, 
                        subscription_meta.billing_interval, 
                        subscription_meta.customer_id,
                        subscription_meta.billing_first_name,
                        subscription_meta.billing_last_name,
                        subscription_meta.schedule_next_payment,
                        completed.completed_date
                FROM " . $wpdb->prefix . "posts AS product 

                LEFT JOIN ( 
                        SELECT tr.object_id AS product_id, t.slug AS product_type 
                        FROM " . $wpdb->prefix . "term_relationships AS tr 
                        INNER JOIN " . $wpdb->prefix . "term_taxonomy AS x 
                        ON ( x.taxonomy = 'product_type' AND x.term_taxonomy_id = tr.term_taxonomy_id ) 
                        INNER JOIN " . $wpdb->prefix . "terms AS t 
                        ON t.term_id = x.term_id 
                ) AS mo ON product.id = mo.product_id 

                LEFT JOIN ( 
                        SELECT wcoitems.order_id AS subscription_id, wcoimeta.meta_value AS product_id,
                        wcoimeta.order_item_id, wcoimeta2.meta_value AS product_total, wcoimeta3.meta_value AS qty 
                        FROM " . $wpdb->prefix . "woocommerce_order_items AS wcoitems 
                        INNER JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta AS wcoimeta 
                        ON wcoimeta.order_item_id = wcoitems.order_item_id 
                        INNER JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta AS wcoimeta2 
                        ON wcoimeta2.order_item_id = wcoitems.order_item_id 
                        INNER JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta AS wcoimeta3 
                        ON wcoimeta3.order_item_id = wcoitems.order_item_id 
                        WHERE wcoitems.order_item_type = 'line_item' 
                        AND wcoimeta.meta_key = '_product_id' 
                        AND wcoimeta2.meta_key = '_line_total'
                        AND wcoimeta3.meta_key = '_qty' 
                ) AS subscription_line_items ON product.id = subscription_line_items.product_id 

                LEFT JOIN (
                        SELECT DISTINCT(" . $wpdb->prefix . "postmeta.post_id) AS wppm_id, 
                        " . $wpdb->prefix . "postmeta.meta_value AS currency, 
                        " . $wpdb->prefix . "postmeta1.meta_value AS billing_period, 
                        " . $wpdb->prefix . "postmeta2.meta_value AS billing_interval, 
                        " . $wpdb->prefix . "postmeta3.meta_value AS customer_id,
                        " . $wpdb->prefix . "postmeta4.meta_value AS billing_first_name,
                        " . $wpdb->prefix . "postmeta5.meta_value AS billing_last_name,
                        /*" . $wpdb->prefix . "postmeta6.meta_value AS billing_company,
                        " . $wpdb->prefix . "postmeta7.meta_value AS billing_address,*/
                        " . $wpdb->prefix . "postmeta8.meta_value AS schedule_next_payment/*,
                        " . $wpdb->prefix . "postmeta9.meta_value AS schedule_cancelled,
                        " . $wpdb->prefix . "postmeta10.meta_value AS schedule_end,
                        " . $wpdb->prefix . "postmeta11.meta_value AS paid_date,
                        " . $wpdb->prefix . "postmeta12.meta_value as completed_date */
                        FROM " . $wpdb->prefix . "postmeta
                        INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta1
                        ON " . $wpdb->prefix . "postmeta1.post_id = " . $wpdb->prefix . "postmeta.post_id 
                        INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta2 
                        ON " . $wpdb->prefix . "postmeta2.post_id = " . $wpdb->prefix . "postmeta.post_id 
                        INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta3 
                        ON " . $wpdb->prefix . "postmeta3.post_id = " . $wpdb->prefix . "postmeta.post_id 
                        INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta4 
                        ON " . $wpdb->prefix . "postmeta4.post_id = " . $wpdb->prefix . "postmeta.post_id 
                        INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta5 
                        ON " . $wpdb->prefix . "postmeta5.post_id = " . $wpdb->prefix . "postmeta.post_id 
                        /*INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta6 
                        ON " . $wpdb->prefix . "postmeta6.post_id = " . $wpdb->prefix . "postmeta.post_id 
                        INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta7 
                        ON " . $wpdb->prefix . "postmeta7.post_id = " . $wpdb->prefix . "postmeta.post_id*/
                        INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta8 
                        ON " . $wpdb->prefix . "postmeta8.post_id = " . $wpdb->prefix . "postmeta.post_id 
                        /*INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta9 
                        ON " . $wpdb->prefix . "postmeta9.post_id = " . $wpdb->prefix . "postmeta.post_id
                        INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta10 
                        ON " . $wpdb->prefix . "postmeta10.post_id = " . $wpdb->prefix . "postmeta.post_id 
                        /*INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta11
                        ON " . $wpdb->prefix . "postmeta11.post_id = " . $wpdb->prefix . "postmeta.post_id 
                        INNER JOIN " . $wpdb->prefix . "postmeta AS " . $wpdb->prefix . "postmeta12
                        ON " . $wpdb->prefix . "postmeta12.post_id = " . $wpdb->prefix . "postmeta.post_id */
                        WHERE " . $wpdb->prefix . "postmeta.meta_key = '_order_currency' 
                        AND " . $wpdb->prefix . "postmeta1.meta_key = '_billing_period' 
                        AND " . $wpdb->prefix . "postmeta2.meta_key = '_billing_interval'
                        AND " . $wpdb->prefix . "postmeta3.meta_key = '_customer_user'
                        AND " . $wpdb->prefix . "postmeta4.meta_key = '_billing_first_name'
                        AND " . $wpdb->prefix . "postmeta5.meta_key = '_billing_last_name'
                        /*AND " . $wpdb->prefix . "postmeta6.meta_key = '_billing_company'
                        AND " . $wpdb->prefix . "postmeta7.meta_key = '_billing_address_index'*/
                        AND " . $wpdb->prefix . "postmeta8.meta_key = '_schedule_next_payment'
                        /*AND " . $wpdb->prefix . "postmeta9.meta_key = '_schedule_cancelled'
                        AND " . $wpdb->prefix . "postmeta10.meta_key = '_schedule_end'
                        AND " . $wpdb->prefix . "postmeta11.meta_key = '_paid_date'
                        AND " . $wpdb->prefix . "postmeta12.meta_key = '_completed_date'*/
                ) AS subscription_meta ON subscription_meta.wppm_id = subscription_line_items.subscription_id

                LEFT JOIN " . $wpdb->prefix . "posts AS subscriptions ON subscriptions.ID = subscription_line_items.subscription_id 

                LEFT JOIN (
                SELECT DISTINCT(" . $wpdb->prefix . "postmeta.post_id) AS aadd_id, 
                        " . $wpdb->prefix . "postmeta.meta_value AS completed_date
                        FROM " . $wpdb->prefix . "postmeta                                                
                        WHERE " . $wpdb->prefix . "postmeta.meta_key = '_completed_date' 
                ) AS completed ON completed.aadd_id = subscription_line_items.subscription_id

                /*WHERE product.post_status = 'publish' */
                WHERE product.post_type = 'product' 
                AND subscriptions.post_type = 'shop_subscription' 
                AND subscriptions.post_status NOT IN( 'wc-pending', 'trash' )
                ORDER BY product_id, billing_period, billing_interval;
        ", ARRAY_A);

        return $table_data;
    }

    function prepare_items() {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $all_data = $this->get_data();        
        usort($all_data, array(&$this, 'usort_reorder'));

        $per_page = 50;
        $current_page = $this->get_pagenum();
        $total_items = count($all_data);

        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page
        ));
        
        $to_show_data = array_slice($all_data, ( ( $current_page - 1 ) * $per_page), $per_page);        
        $this->items = $to_show_data;        
    }

}