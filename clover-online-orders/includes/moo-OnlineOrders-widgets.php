<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary for widgets.
 *
 * @since      1.0.0
 * @package    Moo_OnlineOrders
 * @subpackage Moo_OnlineOrders/includes
 * @author     Mohammed EL BANYAOUI <elbanyaoui@hotmail.com>
 */
class Moo_OnlineOrders_Widgets_Opening_hours extends WP_Widget{

    function __construct() {
        parent::__construct( 'moo_widget_oh',"Clover orders : Opening hours",array( 'description' => "The opening hours of your store according to Clover"));
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
        echo $args['before_widget'];
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        /* -------- */
        require_once plugin_dir_path(dirname(__FILE__)) . "includes/moo-OnlineOrders-sooapi.php";
        $api   = new Moo_OnlineOrders_SooApi();
        $current_hours = $api->getOpeningHours() ;
        $current_hours = !empty($current_hours["days"]) ? $current_hours["days"] : [] ;

        echo '<div style="padding-left:15px">';
        if(@count($current_hours)>0 && $current_hours != "Please setup you business hours on Clover")
        {
            foreach ($current_hours as $key=>$value) {
                echo '<strong><dt>'.$key.'</dt></strong>';
                if (is_array($value)){
                    foreach ($value as $key1 => $value1) {
                        echo '<dd>'.strtoupper($value1).'</dd>';
                    }
                } else {
                    echo '<dd>'.strtoupper($value).'</dd>';
                }

            }
        }
        else
            echo "Please setup you business hours on Clover";
        echo '</div>';

        /* -------- */
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = 'New title';
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:',"moo_OnlineOrders" ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php
    }
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}

class Moo_OnlineOrders_Widgets_best_selling extends WP_Widget{

    function __construct() {
        parent::__construct( 'moo_widget_bs',"Clover orders : Best Selling (beta)",array( 'description' => "The best selling products"));
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
        $nb_product = $instance['nb_product'];

        echo $args['before_widget'];
        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];

        /* -------- */
        require_once plugin_dir_path( dirname(__FILE__))."models/moo-OnlineOrders-Model.php";
        $model   = new Moo_OnlineOrders_Model();

        $products = $model->getFeaturedProducts(intval($nb_product));
        if (!is_array($products) || count($products) <= 0){
            $products = $model->getBestSellingProducts(intval($nb_product));
        }

        echo '<div style="padding-left:15px">';
        if(@count($products)>0)
        {
            echo '<ul>';
            foreach ($products as $product) {
                echo '<li>'.$product->name.'</li>';
            }
            echo '</ul>';
        }
        else
            echo "Please setup you business hours on Clover";
        echo '</div>';

        /* -------- */
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = 'New title';
        }
        if ( isset( $instance[ 'nb_product' ] ) ) {
            $nb_product = $instance[ 'nb_product' ];
        }
        else {
            $nb_product = '10';
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:',"moo_OnlineOrders" ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'nb_product' ); ?>"><?php _e( 'Number of products:',"moo_OnlineOrders" ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'nb_product' ); ?>" name="<?php echo $this->get_field_name( 'nb_product' ); ?>" type="number" value="<?php echo esc_attr( $nb_product ); ?>" />
        </p>

        <?php
    }
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
        $instance['nb_product'] = ( ! empty( $new_instance['nb_product'] ) ) ? intval( $new_instance['nb_product'] ) : '';
        return $instance;
    }
}

class Moo_OnlineOrders_Widgets_categories extends WP_Widget {

    function __construct() {
        parent::__construct( 'moo_widget_categories',"Clover orders : categories",array( 'description' => "Categories"));
    }

    public function widget( $args, $instance ) {
        $title         = apply_filters( 'widget_title', $instance['title'] );
        $MooOptions    = get_option('moo_settings');
        $store_page_id = $MooOptions['store_page'];
        $store_url     = get_page_link($store_page_id);
        echo $args['before_widget'];
        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title'];

        /* -------- */
        require_once plugin_dir_path( dirname(__FILE__))."models/moo-OnlineOrders-Model.php";
        $model   = new Moo_OnlineOrders_Model();
        $categories = $model->getCategories4wigdets() ;
        echo '<div style="padding-left:15px">';
        if(@count($categories)>0)
        {
            echo '<ul>';
            foreach ($categories as $category) {
                echo '<li><a href="'.$store_url."?&category=".$category->uuid.'">'.$category->name.'</a></li>';
            }
            echo '</ul>';
        }
        else
            echo "You don't have any category";
        echo '</div>';

        /* -------- */
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = 'New title';
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:',"moo_OnlineOrders" ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>

        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}