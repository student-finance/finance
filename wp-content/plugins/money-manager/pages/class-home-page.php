<?php

namespace MoneyManager\Pages;

defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

use MoneyManager\I18n;
use MoneyManager\Managers\WooCommerce_Manager;
use MoneyManager\Page;
use MoneyManager\Sample_Data;

/**
 * Class Home_Page
 * @package MoneyManager
 */
class Home_Page extends Page
{
    /**
     * Init home page
     */
    public function init()
    {
        if ( isset ( $_GET['money-manager-import'] ) && $_GET['money-manager-import'] == 'sample-data' ) {
            if ( ! Sample_Data::imported() ) {
                $sample_data = new Sample_Data();
                $sample_data->import();
            }
            wp_redirect( remove_query_arg( 'money-manager-import' ) );
            exit;
        }
        $this->add_submenu_page(
            __( 'Home', 'money-manager' ),
            'money-manager-home'
        );
    }

    /**
     * Render home page
     */
    public function render_page()
    {
        echo '<div id="money-manager"></div>';
    }

    /**
     * Enqueue assets for home page
     */
    protected function enqueue_assets()
    {
        // Media Library
        wp_enqueue_media();

        $this->enqueue_script(
            'money-manager-app.js',
            plugins_url( 'js/app.js', MONEY_MANAGER_PLUGIN_FILE ),
            array( 'media-editor' )
        );
        wp_enqueue_style(
            'money-manager-fontawesome.css',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'
        );
        $this->enqueue_style(
            'money-manager-app.css',
            plugins_url( 'css/app.css', MONEY_MANAGER_PLUGIN_FILE )
        );

        wp_localize_script( 'money-manager-app.js', 'MoneyManagerSettings', array(
            'endpoint' => esc_url_raw( rest_url() ) . 'money-manager/v1',
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'meta' => get_user_meta( get_current_user_id(), 'money_manager', true ) ?: array(),
            'locale' => str_replace( '_', '-', get_locale() ),
            'i18n' => I18n::getStrings(),
            'woocommerce' => WooCommerce_Manager::active(),
        ) );
    }
}
