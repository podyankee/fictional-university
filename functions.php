<?php

require get_theme_file_path( '/inc/like-route.php' );
require get_theme_file_path( '/inc/search-route.php' );

function university_custom_rest() {
    register_rest_field( 'post', 'authorName', array(
        'get_callback' => function () {return get_the_author();},
    ) );

    register_rest_field( 'note', 'userNoteCount', array(
        'get_callback' => function () {return count_user_posts( get_current_user_id(), 'note' );},
    ) );
}

function pageBAnner( $args = null ) {
    if ( !isset( $args['title'] ) ) {
        $args['title'] = get_the_title();
    }

    if ( !isset( $args['subtitle'] ) ) {
        $args['subtitle'] = get_field( 'page_banner_subtitle' );
    }

    if ( !isset( $args['photo'] ) ) {
        if ( get_field( 'page_banner_background_image' ) ) {
            $args['photo'] = get_field( 'page_banner_background_image' )['sizes']['pageBanner'];
        } else {
            $args['photo'] = get_theme_file_uri( '/images/ocean.jpg' );
        }
    }

    ?>

<div class="page-banner">
	<div class="page-banner__bg-image" style="background-image: url(<?php echo $args['photo']; ?>);"></div>
	<div class="page-banner__content container container--narrow">
		<h1 class="page-banner__title"><?php echo $args['title'] ?></h1>
		<div class="page-banner__intro">
			<p><?php echo $args['subtitle'] ?></p>
		</div>
	</div>
</div>

<?php }

function university_files() {
    wp_enqueue_script( 'googleMap', '//maps.googleapis.com/maps/api/js?key=AIzaSyBlSy1EJlA5EM-tmqaqOZsTvDyN5tn1H6I', null, '1.0', true );
    wp_enqueue_script( 'main_university_js', get_theme_file_uri( 'build/index.js' ), array( 'jquery' ), '1.0', true );
    wp_enqueue_style( 'custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i' );
    wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
    wp_enqueue_style( 'university_main_styles', get_theme_file_uri( '/build/style-index.css' ) );
    wp_enqueue_style( 'university_extra_styles', get_theme_file_uri( '/build/index.css' ) );

    wp_localize_script( 'main_university_js', 'universityData', array(
        'root_url' => get_site_url(),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
    ) );
}

function university_features() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'professorLandscape', 400, 260, true );
    add_image_size( 'professorPortrait', 480, 650, true );
    add_image_size( 'pageBanner', 1500, 350, true );
}

function university_adjust_queries( $query ) {
    if ( !is_admin() and is_post_type_archive( 'campus' ) and $query->is_main_query() ) {
        $query->set( 'posts_per_page', -1 );
    }

    if ( !is_admin() and is_post_type_archive( 'program' ) and $query->is_main_query() ) {
        $query->set( 'orderby', 'title' );
        $query->set( 'order', 'ASC' );
        $query->set( 'posts_per_page', -1 );
    }

    if ( !is_admin() and is_post_type_archive( 'event' ) and $query->is_main_query() ) {
        $today = date( 'Ymd' );
        $query->set( 'meta_key', 'event_date' );
        $query->set( 'order_by', 'meta_value_num' );
        $query->set( 'order', 'ASC' );
        $query->set( 'meta_query', array(
            array(
                'key'     => 'event_date',
                'compare' => '>=',
                'value'   => $today,
                'type'    => 'numeric',
            ),
        ) );
    }
}

function universityMapKey( $api ) {
    $api['key'] = 'AIzaSyBlSy1EJlA5EM-tmqaqOZsTvDyN5tn1H6I';
    return $api;
}

// Redirect subscriber accounts out of admin and onto homepage

function redirectSubsToFrontend() {
    $ourCurrentUser = wp_get_current_user();

    if ( count( $ourCurrentUser->roles ) == 1 AND $ourCurrentUser->roles[0] == 'subscriber' ) {
        wp_redirect( site_url( '/' ) );
        exit;
    }
}

function ourHeaderUrl() {
    return esc_url( site_url( '/' ) );
}

add_action( 'admin_init', 'redirectSubsToFrontend' );

// Hide amin bar fo subscriber

function noSubsAdminBar() {
    $ourCurrentUser = wp_get_current_user();

    if ( count( $ourCurrentUser->roles ) == 1 AND $ourCurrentUser->roles[0] == 'subscriber' ) {
        show_admin_bar( false );
    }
}

function ourLoginCSS() {
    wp_enqueue_style( 'custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i' );
    wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
    wp_enqueue_style( 'university_main_styles', get_theme_file_uri( '/build/style-index.css' ) );
    wp_enqueue_style( 'university_extra_styles', get_theme_file_uri( '/build/index.css' ) );
}

function ourLoginTitle() {
    return get_bloginfo( 'name' );
}

function makeNotePrivate( $data, $postarr ) {
    if ( $data['post_type'] == 'note' ) {

        if ( count_user_posts( get_current_user_id(), 'note' ) > 4 AND !$postarr['ID'] ) {
            die( "You have reached your note limit." );
        }

        $data['post_content'] = sanitize_textarea_field( $data['post_content'] );
        $data['post_title'] = sanitize_text_field( $data['post_title'] );
    }

    if ( $data['post_type'] == 'note' AND $data['post_status'] != 'trash' ) {
        $data['post_status'] = 'private';
    }
    return $data;
}

add_action( 'admin_loaded', 'noSubsAdminBar' );

add_action( 'rest_api_init', 'university_custom_rest' );
add_action( 'wp_enqueue_scripts', 'university_files' );
add_action( 'after_setup_theme', 'university_features' );
add_action( 'pre_get_posts', 'university_adjust_queries' );
add_action( 'login_enqueue_scripts', 'ourLoginCSS' );

add_filter( 'acf/fields/google_map/api', 'universityMapKey' );

// Customize login Screen

add_filter( 'login_headerurl', 'ourHeaderUrl' );

add_filter( 'login_headertitle', 'ourLoginTitle' );

add_filter( 'wp_insert_post_data', 'makeNotePrivate', 10, 2 );

add_filter( 'ai1wm_exclude_content_from_export', 'ignoreCertainFiles' );

function ignoreCertainFiles( $exclude_filters ) {
    $exclude_filters[] = 'themes/fictional-university/node_modules';
    return $exclude_filters;
}