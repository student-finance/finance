<?php

namespace MoneyManager;

defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

/**
 * Class Update
 * @package MoneyManager
 */
class Update
{
    /**
     * Updater v1.15.0
     */
    public function update_1_15_0()
    {
        global $wpdb;

        $wpdb->query( "
            drop index files_ref_ref_id_index on {$wpdb->prefix}money_manager_files;
        " );
        $wpdb->query( "
            alter table {$wpdb->prefix}money_manager_files
                add account_id bigint unsigned null after id,
                add transaction_id bigint unsigned null after account_id,
                add attachment_id bigint unsigned not null after transaction_id,
                rename column path to url,
                drop column ref,
                drop column ref_id,
                add constraint files_account_id_foreign
                    foreign key (account_id) references {$wpdb->prefix}money_manager_accounts (id)
                        on delete cascade,
                add constraint files_transaction_id_foreign
                    foreign key (transaction_id) references {$wpdb->prefix}money_manager_transactions (id)
                        on delete cascade
        " );
        $wpdb->query( "
            create index files_attachment_id_index
                on {$wpdb->prefix}money_manager_files (attachment_id)
        " );
        $wpdb->query( "
            alter table {$wpdb->prefix}money_manager_accounts
                modify type enum ('checking', 'card', 'cash', 'debt', 'crypto') not null
        " );
        $wpdb->query( "
            update {$wpdb->prefix}money_manager_accounts
                set type = 'crypto' where type = ''
        " );
    }

    /**
     * Updater v1.12.0
     */
    public function update_1_12_0()
    {
        global $wpdb;

        $wpdb->query( "
            alter table {$wpdb->prefix}money_manager_quotes
                drop foreign key quotes_currency_foreign,
                modify currency varchar(8) null
        " );
        $wpdb->query( "
            alter table {$wpdb->prefix}money_manager_currencies
                modify code varchar(8) not null
        " );
        $wpdb->query( "
            alter table {$wpdb->prefix}money_manager_accounts
                modify currency varchar(8) not null
        " );
        $wpdb->query( "update {$wpdb->prefix}money_manager_quotes set currency = upper(currency)" );
        $wpdb->query( "update {$wpdb->prefix}money_manager_currencies set code = upper(code)" );
        $wpdb->query( "update {$wpdb->prefix}money_manager_accounts set currency = upper(currency)" );
        $wpdb->query( "
            alter table {$wpdb->prefix}money_manager_quotes
                add constraint quotes_currency_foreign
                    foreign key (currency) references {$wpdb->prefix}money_manager_currencies (code)
                        on delete cascade
        " );

        $entries = $wpdb->get_results( "select * from {$wpdb->usermeta} where meta_key = 'money_manager'" );
        foreach ( $entries as $entry ) {
            $value = unserialize( $entry->meta_value );
            $value['displayCurrency'] = strtoupper( $value['displayCurrency'] );
            $wpdb->update(
                $wpdb->usermeta,
                array( 'meta_value' => serialize( $value ) ),
                array( 'umeta_id' => $entry->umeta_id )
            );
        }
    }

    /**
     * Updater v1.10.1
     */
    public function update_1_10_1()
    {
        global $wpdb;

        $wpdb->query( "
            create index quotes_currency_date_index
                on {$wpdb->prefix}money_manager_quotes (currency, date)
        " );
        $wpdb->query( "drop index quotes_currency_index on {$wpdb->prefix}money_manager_quotes" );
        $wpdb->query( "drop index quotes_date_index on {$wpdb->prefix}money_manager_quotes" );
    }

    /**
     * Updater v1.8.0
     */
    public function update_1_8_0()
    {
        add_option( 'money_manager_woocommerce', array(
            'enabled' => false,
            'account_id' => null,
            'party_id' => null,
            'category_id' => null,
        ) );
    }

    /**
     * Check whether database version is up-to-date
     *
     * @return bool
     */
    public function up_to_date()
    {
        return get_option( 'money_manager_version' ) === MoneyManager()->version;
    }

    /**
     * Run updates
     */
    public function update()
    {
        if ( get_transient( 'money_manager_updating' ) == 'yes' ) {
            return;
        }

        set_transient( 'money_manager_updating', 'yes', MINUTE_IN_SECONDS * 10 );
        set_time_limit( 0 );

        $updates = array_filter(
            get_class_methods( $this ),
            function ( $method ) { return strpos( $method, 'update_' ) === 0; }
        );
        usort( $updates, 'strnatcmp' );

        $db_version = get_option( 'money_manager_version' );

        foreach ( $updates as $method ) {
            $version = str_replace( '_', '.', substr( $method, 7 ) );
            if ( strnatcmp( $version, $db_version ) > 0 && strnatcmp( $version, MoneyManager()->version ) <= 0 ) {
                // Do update
                call_user_func( array( $this, $method ) );
                // Update plugin version
                update_option( 'money_manager_version', $version );
            }
        }

        // Update plugin version in case no updates were made
        update_option( 'money_manager_version', MoneyManager()->version );

        delete_transient( 'money_manager_updating' );
    }
}