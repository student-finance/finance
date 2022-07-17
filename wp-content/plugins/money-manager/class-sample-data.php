<?php

namespace MoneyManager;

defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

use MoneyManager\Managers\Account_Manager;

/**
 * Class Sample_Data
 * @package MoneyManager
 */
class Sample_Data
{
    /**
     * Check whether the sample data has ever been imported
     *
     * @return bool
     */
    public static function imported()
    {
        return (bool) get_option( 'money_manager_sample_data_imported' );
    }

    /**
     * Import sample data
     */
    public function import()
    {
        if ( get_transient( 'money_manager_importing_sample_data' ) == 'yes' ) {
            return;
        }

        set_transient( 'money_manager_importing_sample_data', 'yes', MINUTE_IN_SECONDS * 5 );

        $eur = new Models\Currency( array(
            'code' => 'GBP',
            'default_quote' => '1.4',
            'color' => '#8187fc',
        ) );
        $eur->save();

        $income = new Models\Category( array(
            'title' => 'Income',
            'color' => '#cc86a6',
        ) );
        $income->save();

        $expenses = new Models\Category( array(
            'title' => 'Expenses',
            'color' => '#84afb3',
        ) );
        $expenses->save();

        $other = new Models\Category( array(
            'title' => 'Other',
            'color' => '#d1d8a2',
        ) );
        $other->save();

        $sales = new Models\Category( array(
            'title' => 'Sales',
            'parent_id' => $income->id,
            'color' => '#8187fc',
        ) );
        $sales->save();

        $rent = new Models\Category( array(
            'title' => 'Rent',
            'parent_id' => $expenses->id,
            'color' => '#ae938c',
        ) );
        $rent->save();

        $insurance = new Models\Category( array(
            'title' => 'Insurance',
            'parent_id' => $expenses->id,
            'color' => '#b6afa6',
        ) );
        $insurance->save();

        $bank_acc_usd = new Models\Account( array(
            'title' => 'Bank of America',
            'type' => 'checking',
            'currency' => 'USD',
            'color' => '#ff7700',
        ) );
        $bank_acc_usd->save();

        $bank_acc_gbp = new Models\Account( array(
            'title' => 'Bank of England',
            'type' => 'checking',
            'currency' => 'GBP',
            'color' => '#00947e',
        ) );
        $bank_acc_gbp->save();

        $visa_card = new Models\Account( array(
            'title' => 'VISA card',
            'type' => 'card',
            'currency' => 'USD',
            'color' => '#296fa8',
        ) );
        $visa_card->save();

        $cash_desk = new Models\Account( array(
            'title' => 'Cash desk',
            'type' => 'cash',
            'currency' => 'USD',
            'color' => '#fba85a',
        ) );
        $cash_desk->save();

        $loan = new Models\Account( array(
            'title' => 'Loan',
            'type' => 'debt',
            'currency' => 'USD',
            'color' => '#d37870',
        ) );
        $loan->save();

        $bank = new Models\Party( array(
            'title' => 'Bank of America',
            'default_category_id' => $other->id,
        ) );
        $bank->save();

        $clients = new Models\Party( array(
            'title' => 'My clients',
            'default_category_id' => $sales->id,
        ) );
        $clients->save();

        $ins_company = new Models\Party( array(
            'title' => 'Insurance Company',
            'default_category_id' => $insurance->id,
        ) );
        $ins_company->save();

        $landlord = new Models\Party( array(
            'title' => 'Landlord',
            'default_category_id' => $rent->id,
        ) );
        $landlord->save();

        $date = current_datetime();
        ( new Models\Transaction( array(
            'account_id' => $loan->id,
            'to_account_id' => $bank_acc_usd->id,
            'party_id' => $bank->id,
            'category_id' => $other->id,
            'date' => $date->modify( '2 months ago' )->format( 'Y-m-d' ),
            'type' => 'transfer',
            'amount' => 30000,
            'to_amount' => 30000,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $bank_acc_usd->id,
            'party_id' => $clients->id,
            'category_id' => $sales->id,
            'date' => $date->modify( '2 months ago' )->format( 'Y-m-d' ),
            'type' => 'income',
            'amount' => 4200,
            'to_amount' => 4200,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $bank_acc_usd->id,
            'party_id' => $ins_company->id,
            'category_id' => $insurance->id,
            'date' => $date->modify( '2 months ago' )->format( 'Y-m-d' ),
            'type' => 'expense',
            'amount' => 2500,
            'to_amount' => 2500,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $bank_acc_usd->id,
            'party_id' => $ins_company->id,
            'category_id' => $insurance->id,
            'date' => $date->modify( '1 month ago' )->format( 'Y-m-d' ),
            'type' => 'expense',
            'amount' => 1200,
            'to_amount' => 1200,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $cash_desk->id,
            'party_id' => $clients->id,
            'category_id' => $sales->id,
            'date' => $date->modify( '1 month ago' )->format( 'Y-m-d' ),
            'type' => 'income',
            'amount' => 2600,
            'to_amount' => 2600,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $bank_acc_usd->id,
            'party_id' => $landlord->id,
            'category_id' => $rent->id,
            'date' => $date->modify( '2 weeks ago' )->format( 'Y-m-d' ),
            'type' => 'expense',
            'amount' => 2500,
            'to_amount' => 2500,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $cash_desk->id,
            'party_id' => $clients->id,
            'category_id' => $sales->id,
            'date' => $date->modify( '2 weeks ago' )->format( 'Y-m-d' ),
            'type' => 'income',
            'amount' => 2500,
            'to_amount' => 2500,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $cash_desk->id,
            'party_id' => $clients->id,
            'category_id' => $sales->id,
            'date' => $date->modify( '1 week ago' )->format( 'Y-m-d' ),
            'type' => 'income',
            'amount' => 3100,
            'to_amount' => 3100,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $cash_desk->id,
            'to_account_id' => $bank_acc_usd->id,
            'category_id' => $other->id,
            'date' => $date->format( 'Y-m-d' ),
            'type' => 'transfer',
            'amount' => 2800,
            'to_amount' => 2800,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $bank_acc_usd->id,
            'to_account_id' => $bank_acc_gbp->id,
            'category_id' => $other->id,
            'date' => $date->format( 'Y-m-d' ),
            'type' => 'transfer',
            'amount' => 2800,
            'to_amount' => 2000,
        ) ) )->save();
        ( new Models\Transaction( array(
            'account_id' => $bank_acc_usd->id,
            'to_account_id' => $visa_card->id,
            'category_id' => $other->id,
            'date' => $date->format( 'Y-m-d' ),
            'type' => 'transfer',
            'amount' => 5000,
            'to_amount' => 5000,
        ) ) )->save();

        Account_Manager::refresh_balance( $bank_acc_usd->id );
        Account_Manager::refresh_balance( $bank_acc_gbp->id );
        Account_Manager::refresh_balance( $visa_card->id );
        Account_Manager::refresh_balance( $cash_desk->id );
        Account_Manager::refresh_balance( $loan->id );

        update_option( 'money_manager_sample_data_imported', true );

        delete_transient( 'money_manager_importing_sample_data' );
    }
}