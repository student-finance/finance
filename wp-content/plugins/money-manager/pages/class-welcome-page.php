<?php

namespace MoneyManager\Pages;

defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

use MoneyManager\Page;

/**
 * Class Welcome_Page
 * @package MoneyManager
 */
class Welcome_Page extends Page
{
    /**
     * Init welcome page
     */
    public function init()
    {
        if ( isset ( $_GET['page'] ) && $_GET['page'] == 'money-manager-welcome' ) {
            $this->add_submenu_page(
                __( 'Welcome', 'money-manager' ),
                'money-manager-welcome'
            );
        }
    }

    /**
     * Render welcome page
     */
    public function render_page()
    {
        require dirname( MONEY_MANAGER_PLUGIN_FILE ) . '/views/welcome.php';
    }

    /**
     * Enqueue assets for welcome page
     */
    protected function enqueue_assets()
    {
        wp_enqueue_style(
            'money-manager-fontawesome.css',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'
        );
        $this->enqueue_style(
            'money-manager-app.css',
            plugins_url( 'css/app.css', MONEY_MANAGER_PLUGIN_FILE )
        );
    }
}
