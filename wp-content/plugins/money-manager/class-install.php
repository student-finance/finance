<?php

namespace MoneyManager;

defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

use MoneyManager\Managers\WooCommerce_Manager;

/**
 * Class Install
 * @package MoneyManager
 */
class Install
{
    /**
     * Check whether the plugin has ever been installed
     *
     * @return bool
     */
    public function installed()
    {
        return get_option( 'money_manager_version' ) !== false;
    }

    /**
     * Install the plugin
     */
    public function install()
    {
        if ( get_transient( 'money_manager_installing' ) == 'yes' ) {
            return;
        }

        set_transient( 'money_manager_installing', 'yes', MINUTE_IN_SECONDS * 10 );

        $this->create_tables();
        $this->create_options();
        $this->create_fixtures();

        delete_transient( 'money_manager_installing' );
    }

    /**
     * Uninstall the plugin
     */
    public function uninstall()
    {
        $this->drop_tables();
        $this->drop_options_and_meta();
    }

    /**
     * Create tables in database
     */
    protected function create_tables()
    {
        global $wpdb;

        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        $wpdb->query( "
            create table {$wpdb->prefix}money_manager_categories (
                id bigint unsigned not null auto_increment primary key,
                parent_id bigint unsigned null,
                title varchar(255) not null,
                color varchar(7) not null,
                created_at timestamp null,
                updated_at timestamp null,
                key categories_title_index (title),
                constraint categories_parent_id_foreign
                    foreign key (parent_id) references {$wpdb->prefix}money_manager_categories (id)
                        on delete cascade
            ) $collate" );
        $wpdb->query( "create table {$wpdb->prefix}money_manager_parties (
                id bigint unsigned auto_increment primary key,
                title varchar(255) not null,
                default_category_id bigint unsigned null,
                created_at timestamp null,
                updated_at timestamp null,
                key parties_title_index (title),
                constraint parties_default_category_id_foreign
                    foreign key (default_category_id) references {$wpdb->prefix}money_manager_categories (id)
                        on delete set null
            ) $collate" );
        $wpdb->query( "
            create table {$wpdb->prefix}money_manager_accounts (
                id bigint unsigned auto_increment primary key,
                title varchar(255) null,
                type enum('checking', 'card', 'cash', 'debt', 'crypto') not null,
                currency varchar(8) not null,
                balance decimal(15,3) default 0.000 null,
                initial_balance decimal(15,3) default 0.000 not null,
                notes text null,
                color varchar(7) not null,
                created_at timestamp null,
                updated_at timestamp null,
                key accounts_title_index (title)
            ) $collate" );
        $wpdb->query( "
            create table {$wpdb->prefix}money_manager_transactions (
                id bigint unsigned auto_increment primary key,
                account_id bigint unsigned not null,
                to_account_id bigint unsigned null,
                party_id bigint unsigned null,
                category_id bigint unsigned null,
                date date not null,
                type enum('transfer', 'income', 'expense') not null,
                amount decimal(15,3) default 0.000 not null,
                to_amount decimal(15,3) null,
                notes text null,
                created_at timestamp null,
                updated_at timestamp null,
                key transactions_date_index (date),
                constraint transactions_account_id_foreign
                    foreign key (account_id) references {$wpdb->prefix}money_manager_accounts (id)
                        on delete cascade,
                constraint transactions_to_account_id_foreign
                    foreign key (to_account_id) references {$wpdb->prefix}money_manager_accounts (id)
                        on delete cascade,
                constraint transactions_party_id_foreign
                    foreign key (party_id) references {$wpdb->prefix}money_manager_parties (id)
                        on delete set null,
                constraint transactions_category_id_foreign
                    foreign key (category_id) references {$wpdb->prefix}money_manager_categories (id)
                        on delete set null
            ) $collate" );
        $wpdb->query( "
            create table {$wpdb->prefix}money_manager_currencies (
                id bigint unsigned auto_increment primary key,
                code varchar(8) not null,
                is_base tinyint(1) default 0 not null,
                default_quote double unsigned default '1' not null,
                color varchar(7) not null,
                created_at timestamp null,
                updated_at timestamp null,
                constraint currencies_code_unique unique (code)
            ) $collate" );
        $wpdb->query( "
            create table {$wpdb->prefix}money_manager_quotes (
                id bigint unsigned auto_increment primary key,
                currency varchar(8) not null,
                date date not null,
                value double unsigned default '1' not null,
                created_at timestamp null,
                updated_at timestamp null,
                key quotes_currency_date_index (currency, date),
                constraint quotes_currency_foreign
                    foreign key (currency) references {$wpdb->prefix}money_manager_currencies (code)
                        on delete cascade
            ) $collate" );
        $wpdb->query( "
            create table {$wpdb->prefix}money_manager_files (
                id bigint unsigned auto_increment primary key,
                account_id bigint unsigned null,
                transaction_id bigint unsigned null,
                attachment_id bigint unsigned not null,
                filename varchar(255) not null,
                description text null,
                url varchar(255) not null,
                created_at  timestamp null,
                updated_at  timestamp null,
                key files_attachment_id_index (attachment_id),
                constraint files_account_id_foreign
                    foreign key (account_id) references {$wpdb->prefix}money_manager_accounts (id)
                        on delete cascade,
                constraint files_transaction_id_foreign
                    foreign key (transaction_id) references {$wpdb->prefix}money_manager_transactions (id)
                        on delete cascade
            ) $collate" );
    }

    /**
     * Create options
     */
    protected function create_options()
    {
        add_option( 'money_manager_version', MoneyManager()->version );
        add_option( 'money_manager_woocommerce', WooCommerce_Manager::settings() );
    }

    /**
     * Create fixtures in database
     */
    protected function create_fixtures()
    {
        $usd = new Models\Currency( array(
            'code' => 'USD',
            'is_base' => true,
            'color' => '#cc86a6',
        ) );
        $usd->save();
    }

    /**
     * Drop tables in database
     */
    protected function drop_tables()
    {
        global $wpdb;

        $wpdb->query( "drop table if exists {$wpdb->prefix}money_manager_files" );
        $wpdb->query( "drop table if exists {$wpdb->prefix}money_manager_quotes" );
        $wpdb->query( "drop table if exists {$wpdb->prefix}money_manager_currencies" );
        $wpdb->query( "drop table if exists {$wpdb->prefix}money_manager_transactions" );
        $wpdb->query( "drop table if exists {$wpdb->prefix}money_manager_accounts" );
        $wpdb->query( "drop table if exists {$wpdb->prefix}money_manager_parties" );
        $wpdb->query( "drop table if exists {$wpdb->prefix}money_manager_categories" );
    }

    /**
     * Delete options and user meta
     */
    protected function drop_options_and_meta()
    {
        global $wpdb;

        $wpdb->query( "delete from {$wpdb->options} where option_name like 'money\\_manager%'" );
        $wpdb->query( "delete from {$wpdb->usermeta} where meta_key like 'money\\_manager%'" );
    }
}