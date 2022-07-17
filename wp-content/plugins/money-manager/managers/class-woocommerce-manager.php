<?php

namespace MoneyManager\Managers;

defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

use MoneyManager\Models\Account;
use MoneyManager\Models\Category;
use MoneyManager\Models\Party;
use MoneyManager\Models\Transaction;

/**
 * Class WooCommerce_Manager
 * @package MoneyManager\Managers
 */
class WooCommerce_Manager
{
    /**
     * Check whether WooCommerce is active (including network activated)
     *
     * @return bool
     */
    public static function active()
    {
        $plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

        return in_array( $plugin_path, wp_get_active_and_valid_plugins() ) ||
            is_multisite() && in_array( $plugin_path, wp_get_active_network_plugins() );
    }

    /**
     * Init WooCommerce integration
     */
    public static function init()
    {
        add_action( 'woocommerce_order_status_completed', array( self::class, 'handle_completed_order' ) );
    }

    /**
     * Handle WooCommerce order 'Completed' status
     *
     * @param $order_id
     */
    public static function handle_completed_order( $order_id )
    {
        $integration = self::settings();
        if ( $integration['enabled'] ) {
            $order = wc_get_order( $order_id );
            $today = current_time( 'Y-m-d' );
            $account = $integration['account_id'] ? Account::find( $integration['account_id'] ) : null;
            if ( $account ) {
                $party = $integration['party_id'] ? Party::find( $integration['party_id'] ) : null;
                $category = $integration['category_id'] ? Category::find( $integration['category_id'] ) : null;

                $transaction = new Transaction();
                $transaction->account_id = $account->id;
                $transaction->party_id = $party ? $party->id : null;
                $transaction->category_id = $category ? $category->id : null;
                $transaction->date = $today;
                $transaction->type = 'income';
                $transaction->amount = $order->get_total();
                $transaction->notes = sprintf( __( 'WooCommerce order #%d', 'money-manager' ), $order_id );

                if ( $transaction->save() ) {
                    Account_Manager::refresh_balance( $transaction->account_id );
                }
            }
        }
    }

    /**
     * Get WooCommerce integration settings
     *
     * @return array
     */
    public static function settings()
    {
        return get_option( 'money_manager_woocommerce', array(
            'enabled' => false,
            'account_id' => null,
            'party_id' => null,
            'category_id' => null,
        ) );
    }
}