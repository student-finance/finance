<?php

namespace MoneyManager\Pages;

defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

use MoneyManager\I18n;
use MoneyManager\Page;

/**
 * Class Addons_Page
 * @package MoneyManager
 */
class Addons_Page extends Page
{
    /**
     * Init add-ons page
     */
    public function init()
    {
        $this->add_submenu_page(
            __( 'Add-ons', 'money-manager' ),
            'money-manager-addons'
        );
    }

    /**
     * Render add-ons page
     */
    public function render_page()
    {
        echo '<div id="money-manager"></div>';
    }

    /**
     * Enqueue assets for add-ons page
     */
    protected function enqueue_assets()
    {
        $this->enqueue_script(
            'money-manager-addons.js',
            plugins_url( 'js/addons.js', MONEY_MANAGER_PLUGIN_FILE ),
            array( 'jquery' )
        );
        wp_enqueue_style(
            'money-manager-fontawesome.css',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'
        );
        $this->enqueue_style(
            'money-manager-app.css',
            plugins_url( 'css/app.css', MONEY_MANAGER_PLUGIN_FILE )
        );
        $this->enqueue_style(
            'money-manager-admin.css',
            plugins_url( 'css/admin.css', MONEY_MANAGER_PLUGIN_FILE ),
            array( 'money-manager-app.css' )
        );

        wp_localize_script( 'money-manager-addons.js', 'MoneyManagerAddonsSettings', array(
            'endpoint' => esc_url_raw( rest_url() ) . 'money-manager/v1',
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'meta' => get_user_meta( get_current_user_id(), 'money_manager', true ) ?: array(),
            'locale' => str_replace( '_', '-', get_locale() ),
            'i18n' => I18n::getStrings(),
            'installed_addons' => $this->get_installed_addons(),
        ) );
    }

    /**
     * Get array of already installed add-ons
     *
     * @return array
     */
    protected function get_installed_addons()
    {
        $addons = array();
        $active_plugins = get_option( 'active_plugins' );

        foreach ( get_plugins() as $plugin => $data ) {
            if ( substr( $plugin, 0, 14 ) === 'money-manager-' ) {
                $slug = substr( $plugin, 0, -9 );
                $addons[ $slug ] = array(
                    'version' => $data['Version'],
                    'active' => in_array( $plugin, $active_plugins ),
                );
            }
        }

        return $addons;
    }
}
