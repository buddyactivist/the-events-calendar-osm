<?php
/**
 * Plugin Name: The Events Calendar OSM
 * Description: Sostituisce Google Maps con OSM (TECOSM). Include geocoding automatico, icone personalizzate per categorie, shortcode [mappa_eventi_osm] con clustering e ricerca, e un pannello di configurazione.
 * Version:     2.0.0
 * Author:      BuddyActivist
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==========================================
// 1. CARICAMENTO LIBRERIE (LEAFLET + CLUSTER)
// ==========================================
add_action( 'wp_enqueue_scripts', 'tecosm_advanced_scripts' );
function tecosm_advanced_scripts() {
    global $post;
    
    // Carichiamo le risorse solo dove servono
    if ( is_singular( array('tribe_events', 'tribe_venue') ) || ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'mappa_eventi_osm' ) ) ) {
        wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
        wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
        
        wp_enqueue_style( 'leaflet-cluster-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css' );
        wp_enqueue_style( 'leaflet-cluster-default', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css' );
        wp_enqueue_script( 'leaflet-cluster-js', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', array('leaflet-js'), '1.5.3', true );
    }
}

// ==========================================
// 2. PANNELLO DI AMMINISTRAZIONE
// ==========================================
add_action( 'admin_init', 'tecosm_register_settings' );
function tecosm_register_settings() {
    register_setting( 'tecosm_settings_group', 'tecosm_default_lat' );
    register_setting( 'tecosm_settings_group', 'tecosm_default_lng' );
    register_setting( 'tecosm_settings_group', 'tecosm_default_zoom' );
    register_setting( 'tecosm_settings_group', 'tecosm_map_height' );
}

add_action( 'admin_menu', 'tecosm_add_admin_menu' );
function tecosm_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=tribe_events', 
        'Impostazioni Mappa OSM',          
        'Mappa OSM',                       
        'manage_options',                  
        'tecosm-settings',                 
        'tecosm_settings_page_html'        
    );
}

function tecosm_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1>Impostazioni The Events Calendar OSM</h1>
        <p>Configura i parametri di default per la mappa globale generata dallo shortcode <code>[mappa_eventi_osm]</code>.</p>
        
        <form method="post" action="options.php">
            <?php settings_fields( 'tecosm_settings_group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Latitudine di partenza</th>
                    <td>
                        <input type="text" name="tecosm_default_lat" value="<?php echo esc_attr( get_option('tecosm_default_lat', '41.9028') ); ?>" />
                        <p class="description">Es. 41.9028 (Roma)</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Longitudine di partenza</th>
                    <td>
                        <input type="text" name="tecosm_default_lng" value="<?php echo esc_attr( get_option('tecosm_default_lng', '12.4964') ); ?>" />
                        <p class="description">Es. 12.4964 (Roma)</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Livello di Zoom Iniziale</th>
                    <td>
                        <input type="number" name="tecosm_default_zoom" value="<?php echo esc_attr( get_option('tecosm_default_zoom', '6') ); ?>" min="1" max="19" />
                        <p class="description">Da 1 (Mondo intero) a 19 (Livello strada)</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Altezza Mappa Globale</th>
                    <td>
                        <input type="text" name="tecosm_map_height" value="<?php echo esc_attr( get_option('tecosm_map_height', '600px') ); ?>" />
                        <p class="description">Includi l'unità di misura, es. <code>600px</code> o <code>100vh</code></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// ==========================================
// 3. GEOCODING OSM (NOMINATIM) AL SALVATAGGIO
// ==========================================
add_action( 'save_post_tribe_venue', 'tecosm_geocode_venue', 10, 2 );
function tecosm_geocode_venue( $venue_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    $address = get_post_meta( $venue_id, '_VenueAddress', true );
    $city    = get_post_meta( $venue_id, '_VenueCity', true );
    $country = get_post_meta( $venue_id, '_VenueCountry', true );
    
    if ( empty( $address ) && empty( $city ) ) return;

    $query = urlencode( $address . ', ' . $city . ', ' . $country );
    $url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&q=" . $query;

    $args = array( 'headers' => array( 'User-Agent' => 'WordPress/TECOSM-Plugin' ) );
    $response = wp_remote_get( $url, $args );

    if ( ! is_wp_error( $response ) ) {
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( ! empty( $data ) && isset( $data[0]->lat ) ) {
            update_post_meta( $venue_id, '_VenueLat', sanitize_text_field( $data[0]->lat ) );
            update_post_meta( $venue_id, '_VenueLng', sanitize_text_field( $data[0]->lon ) );
        }
    }
}

// ==========================================
// 4. CAMPI PERSONALIZZATI PER LE CATEGORIE EVENTO
// ==========================================
add_action( 'tribe_events_cat_add_form_fields', 'tecosm_add_category_marker_field', 10, 2 );
function tecosm_add_category_marker_field() {
    ?>
    <div class="form-field">
        <label for="tecosm_marker_url">URL Icona Mappa (Opzionale)</label>
        <input type="text" name="tecosm_marker_url" id="tecosm_marker_url" value="">
        <p class="description">Incolla qui l'URL dell'icona (es. dalla Libreria Media). Dimensioni consigliate: 32x32 pixel.</p>
    </div>
    <?php
}

add_action( 'tribe_events_cat_edit_form_fields', 'tecosm_edit_category_marker_field', 10, 2 );
function tecosm_edit_category_marker_field( $term ) {
    $marker_url = get_term_meta( $term->term_id, 'tecosm_marker_url', true );
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="tecosm_marker_url">URL Icona Mappa</label></th>
        <td>
            <input type="text" name="tecosm_marker_url" id="tecosm_marker_url" value="<?php echo esc_attr( $marker_url ); ?>">
            <p class="description">Incolla qui l'URL dell'icona. Se lasciato vuoto, verrà usato il Pin blu standard.</p>
        </td>
    </tr>
    <?php
}

add_action( 'created_tribe_events_cat', 'tecosm_save_category_marker_field', 10, 2 );
add_action( 'edited_tribe_events_cat', 'tecosm_save_category_marker_field', 10, 2 );
function tecosm_save_category_marker_field( $term_id ) {
    if ( isset( $_POST['tecosm_marker_url'] ) ) {
        update_term_meta( $term_id, 'tecosm_marker_url', sanitize_url( $_POST['tecosm_marker_url'] ) );
    }
}

// ==========================================
// 5. SOSTITUZIONE MAPPA SINGOLA
// ==========================================
add_filter( 'tribe_get_embedded_map', 'tecosm_replace_single_map', 10, 4 );
function tecosm_replace_single_map( $html, $post_id, $width, $height ) {
    $venue_id = tribe_get_venue_id( $post_id ) ? tribe_get_venue_id( $post_id ) : $post_id;
    $lat = get_post_meta( $venue_id, '_VenueLat', true );
    $lng = get_post_meta( $venue_id, '_VenueLng', true );

    if ( empty( $lat ) || empty( $lng ) ) return $html; 

    $height = is_numeric( $height ) ? $height . 'px' : ( empty( $height ) ? '400px' : $height );
    $map_id = 'tecosm-single-' . uniqid();
    $venue_name = get_the_title( $venue_id );

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $map_id ); ?>" style="width: 100%; height: <?php echo esc_attr( $height ); ?>; z-index: 1;"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L !== 'undefined') {
                var map = L.map('<?php echo esc_js( $map_id ); ?>').setView([<?php echo esc_js( $lat ); ?>, <?php echo esc_js( $lng ); ?>], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
                L.marker([<?php echo esc_js( $lat ); ?>, <?php echo esc_js( $lng ); ?>]).addTo(map)
                 .bindPopup('<strong><?php echo esc_js( $venue_name ); ?></strong>');
            }
        });
    </script>
    <?php
    return ob_get_clean();
}

// ==========================================
// 6. SHORTCODE MAPPA GLOBALE CON CLUSTERING E RICERCA
// ==========================================
add_shortcode( 'mappa_eventi_osm', 'tecosm_global_map_shortcode' );
function tecosm_global_map_shortcode() {
    $default_lat   = get_option( 'tecosm_default_lat', '41.9028' );
    $default_lng   = get_option( 'tecosm_default_lng', '12.4964' );
    $default_zoom  = get_option( 'tecosm_default_zoom', '6' );
    $map_height    = get_option( 'tecosm_map_height', '600px' );

    $events = tribe_get_events( array(
        'posts_per_page' => -1,
        'start_date'     => date( 'Y-m-d H:i:s' )
    ));

    $map_data = array();

    foreach ( $events as $event ) {
        $venue_id = tribe_get_venue_id( $event->ID );
        $lat = get_post_meta( $venue_id, '_VenueLat', true );
        $lng = get_post_meta( $venue_id, '_VenueLng', true );

        if ( $lat && $lng ) {
            $marker_url = '';
            $terms = get_the_terms( $event->ID, 'tribe_events_cat' );
            if ( $terms && ! is_wp_error( $terms ) ) {
                $marker_url = get_term_meta( $terms[0]->term_id, 'tecosm_marker_url', true );
            }

            $map_data[] = array(
                'lat'        => $lat,
                'lng'        => $lng,
                'title'      => get_the_title( $event->ID ),
                'url'        => get_permalink( $event->ID ),
                'marker_url' => $marker_url
            );
        }
    }

    $map_id = 'tecosm-global-' . uniqid();
    ob_start();
    ?>
    
    <div style="margin-bottom: 15px;">
        <input type="text" id="tecosm-search" placeholder="Cerca un evento..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; box-sizing: border-box;">
    </div>

    <div id="<?php echo esc_attr( $map_id ); ?>" style="width: 100%; height: <?php echo esc_attr( $map_height ); ?>; z-index: 1; border: 1px solid #ddd; border-radius: 4px;"></div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') return;

            var mapData = <?php echo json_encode( $map_data ); ?>;
            var startLat = <?php echo esc_js( $default_lat ); ?>;
            var startLng = <?php echo esc_js( $default_lng ); ?>;
            var startZoom = <?php echo esc_js( $default_zoom ); ?>;

            var map = L.map('<?php echo esc_js( $map_id ); ?>').setView([startLat, startLng], startZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

            var markers = L.markerClusterGroup();
            map.addLayer(markers);

            function renderMarkers(dataToRender) {
                markers.clearLayers(); 
                
                dataToRender.forEach(function(event) {
                    var iconOptions = {
                        iconSize: [32, 32],
                        iconAnchor: [16, 32],
                        popupAnchor: [0, -32]
                    };

                    if (event.marker_url && event.marker_url !== '') {
                        iconOptions.iconUrl = event.marker_url;
                    } else {
                        iconOptions.iconUrl = 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png';
                        iconOptions.iconSize = [25, 41];
                        iconOptions.iconAnchor = [12, 41];
                        iconOptions.popupAnchor = [1, -34];
                    }

                    var customIcon = L.icon(iconOptions);
                    var marker = L.marker([event.lat, event.lng], { icon: customIcon });
                    
                    marker.bindPopup('<strong><a href="' + event.url + '">' + event.title + '</a></strong>');
                    markers.addLayer(marker);
                });
            }

            renderMarkers(mapData);
            
            if (mapData.length > 0) {
                map.fitBounds(markers.getBounds(), { padding: [50, 50], maxZoom: 15 });
            }

            var searchInput = document.getElementById('tecosm-search');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    var searchTerm = e.target.value.toLowerCase(); 
                    
                    var filteredData = mapData.filter(function(event) {
                        return event.title.toLowerCase().includes(searchTerm);
                    });

                    renderMarkers(filteredData);
                });
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
