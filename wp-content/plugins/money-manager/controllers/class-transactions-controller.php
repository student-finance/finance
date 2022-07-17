<?php

namespace MoneyManager\Controllers;

defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

use MoneyManager\Managers\Account_Manager;
use MoneyManager\Models\Account;
use MoneyManager\Models\Category;
use MoneyManager\Models\File;
use MoneyManager\Models\Party;
use MoneyManager\Models\Transaction;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Transactions_Controller
 * @package MoneyManager\Controllers
 */
class Transactions_Controller extends Base_Controller
{
    /**
     * Register routes
     */
    public function register_routes()
    {
        $this->post( '/transactions/list', 'list_transactions' );
        $this->post( '/transactions/save', 'save_transaction' );
        $this->post( '/transactions/remove', 'remove_transactions' );
        $this->post( '/transactions/import', 'import_transactions' );
    }

    /**
     * Get list of transactions
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function list_transactions( WP_REST_Request $request )
    {
        global $wpdb;

        $account = Account::find( $request->get_param( 'account_id' ) );

        // Range filter
        $date = current_datetime();
        $date_condition = '1';
        $party_condition = null;
        $category_condition = null;
        switch ( $request->get_param( 'range' ) ) {
            case 'today':
                $date_condition = $wpdb->prepare( 'date = %s', $date->format( 'Y-m-d' ) );
                break;
            case 'this_month':
                $date_condition = $wpdb->prepare(
                    'date between %s and %s',
                    $date->modify( 'first day of this month' )->format( 'Y-m-d' ),
                    $date->modify( 'last day of this month' )->format( 'Y-m-d' )
                );
                break;
            case 'recent_30_days':
                $date_condition = $wpdb->prepare(
                    'date > %s',
                    $date->modify( '-30 days' )->format( 'Y-m-d' )
                );
                break;
            case 'recent_90_days':
                $date_condition = $wpdb->prepare(
                    'date > %s',
                    $date->modify( '-90 days' )->format( 'Y-m-d' )
                );
                break;
            case 'last_month':
                $date_condition = $wpdb->prepare(
                    'date between %s and %s',
                    $date->modify( 'first day of last month' )->format( 'Y-m-d' ),
                    $date->modify( 'last day of last month' )->format( 'Y-m-d' )
                );
                break;
            case 'recent_3_months':
                $date_condition = $wpdb->prepare(
                    'date >= %s',
                    $date->modify( 'first day of 2 months ago' )->format( 'Y-m-d' )
                );
                break;
            case 'recent_12_months':
                $date_condition = $wpdb->prepare(
                    'date >= %s',
                    $date->modify( 'first day of 12 months ago' )->format( 'Y-m-d' )
                );
                break;
            case 'this_year':
                $date_condition = $wpdb->prepare(
                    'date between %s and %s',
                    $date->modify( 'first day of January this year' )->format( 'Y-m-d' ),
                    $date->modify( 'last day of December this year' )->format( 'Y-m-d' )
                );
                break;
            case 'last_year':
                $date_condition = $wpdb->prepare(
                    'date between %s and %s',
                    $date->modify( 'first day of January last year' )->format( 'Y-m-d' ),
                    $date->modify( 'last day of December last year' )->format( 'Y-m-d' )
                );
                break;
            case 'advanced_filter':
                $criteria = $request->get_param( 'criteria' ) ?: array();
                if ( in_array( 'date_range', $criteria ) ) {
                    $date_condition = $wpdb->prepare(
                        'date between %s and %s',
                        $request->get_param( 'date_from' ),
                        $request->get_param( 'date_to' )
                    );
                }
                if ( in_array( 'party', $criteria ) ) {
                    $ids = $request->get_param( 'party_ids' ) ?: array();
                    $ids_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
                    $party_condition = $wpdb->prepare( "party_id in ($ids_placeholders)", $ids );
                }
                if ( in_array( 'category', $criteria ) ) {
                    $ids = $request->get_param( 'category_ids' ) ?: array();
                    $ids_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
                    $category_condition = $wpdb->prepare( "category_id in ($ids_placeholders)", $ids );
                }
                break;
        }

        $transactions = Transaction::rows( function ( $query ) use (
            $wpdb,
            $account,
            $date_condition,
            $party_condition,
            $category_condition
        ) {
            $query .= " where $date_condition";
            if ( $account ) {
                $query .= $wpdb->prepare(
                    ' and (account_id = %1$d or to_account_id = %1$d)',
                    $account->id
                );
            }
            if ( $party_condition ) {
                $query .= " and $party_condition";
            }
            if ( $category_condition ) {
                $query .= " and $category_condition";
            }
            return "$query order by date";
        } );

        if ( ! empty ( $transactions ) ) {
            $files = File::rows( function ( $query ) use ( $transactions ) {
                $ids = implode( ',', array_column( $transactions, 'id' ) );
                return $query . " where transaction_id in ($ids) order by filename";
            } );

            if ( $account ) {
                $delta = Account_Manager::get_delta( $account->id, $transactions[0]['date'] );
                $balance = $account->initial_balance + $delta;

                foreach ( $transactions as &$transaction ) {
                    if ( $transaction['type'] == 'expense' || $transaction['type'] == 'transfer' && $transaction['account_id'] == $account->id ) {
                        $balance -= $transaction['amount'];
                    } else {
                        $balance += $transaction['type'] == 'transfer' ? $transaction['to_amount'] : $transaction['amount'];
                    }
                    $transaction['balance'] = $balance;
                    $transaction['files'] = $this->get_files_for_transaction( $files, $transaction );
                }
            } else {
                foreach ( $transactions as &$transaction ) {
                    $transaction['files'] = $this->get_files_for_transaction( $files, $transaction );
                }
            }
        }

        return rest_ensure_response( array( 'result' => $transactions ) );
    }

    /**
     * Save transaction
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function save_transaction( WP_REST_Request $request )
    {
        global $wpdb;

        $input = $request->get_param( 'item' ) ?: array( 'files' => array() );

        $refresh = [];
        if ( isset ( $input['id'] ) ) {
            $transaction = Transaction::find( $input['id'] );
            if ( ! $transaction ) {
                return rest_ensure_response( array( 'error' => array( 'code' => 'RECORD_NOT_FOUND' ) ) );
            }
            $refresh[] = $transaction->account_id;
            $refresh[] = $transaction->to_account_id;
            $transaction->fill( $input );
        } else {
            $transaction = new Transaction( $input );
        }
        if ( $transaction->type !== 'transfer' ) {
            $transaction->to_account_id = null;
            $transaction->to_amount = null;
        }

        if ( $transaction->save() ) {
            // Refresh accounts' balances
            $refresh[] = $transaction->account_id;
            $refresh[] = $transaction->to_account_id;
            foreach ( array_filter( array_unique( $refresh ) ) as $account_id ) {
                Account_Manager::refresh_balance( $account_id );
            }

            // Save files
            $ids_to_keep = array();
            foreach ( $input['files'] as $input_file ) {
                $file = isset ( $input_file['id'] ) ? File::find( $input_file['id'] ) : new File();
                if ( $file ) {
                    $file->fill( $input_file );
                    $file->account_id = null;
                    $file->transaction_id = $transaction->id;
                    $file->save();
                    $ids_to_keep[] = $file->id;
                }
            }
            File::destroyExcept( $ids_to_keep, array( 'transaction_id' => $transaction->id ) );

            return rest_ensure_response( array( 'result' => 'ok' ) );
        }

        return rest_ensure_response( array( 'error' => array( 'code' => 'ERROR_SAVING' ) ) );
    }

    /**
     * Remove transactions
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function remove_transactions( WP_REST_Request $request )
    {
        global $wpdb;

        $ids = $request->get_param( 'ids' );

        if ( ! empty ( $ids ) ) {
            // Find out which accounts will be affected
            $table_name = Transaction::table_name();
            $ids_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
            $refresh = $wpdb->get_col( $wpdb->prepare(
                "select account_id from $table_name where id in ($ids_placeholders)
                    union
                    select to_account_id from $table_name where id in ($ids_placeholders) and to_account_id is not null",
                array_merge( $ids, $ids )
            ) );

            // Remove transactions
            Transaction::destroy( $request->get_param( 'ids' ) );

            // Refresh accounts' balances
            foreach ( $refresh as $account_id ) {
                Account_Manager::refresh_balance( $account_id );
            }
        }

        return rest_ensure_response( array( 'result' => 'ok' ) );
    }

    /**
     * Import transactions
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function import_transactions( WP_REST_Request $request )
    {
        $data = $request->get_param( 'data' ) ?: array();

        $account = Account::find( $request->get_param( 'account_id' ) );
        $party = Party::find( $request->get_param( 'party_id' ) );
        $category = Category::find( $request->get_param( 'category_id' ) );

        $today = current_time( 'Y-m-d' );

        if ( $account ) {
            foreach ( $data as $row ) {
                $amount = isset ( $row['amount'] ) ? $row['amount'] : 0;
                $transaction = new Transaction( array(
                    'account_id' => $account->id,
                    'party_id' => $party ? $party->id : null,
                    'category_id' => $category ? $category->id : null,
                    'date' => isset ( $row['date'] ) ? $row['date'] : $today,
                    'type' => $amount >= 0 ? 'income' : 'expense',
                    'amount' => abs( $amount ),
                    'notes' => isset ( $row['notes'] ) ? $row['notes'] : '',
                ) );
                $transaction->save();
            }
        }

        Account_Manager::refresh_balance( $account->id );

        return rest_ensure_response( array( 'result' => 'ok' ) );
    }

    /**
     * Filter files which belong to given transaction
     *
     * @param array $files
     * @param array $transaction
     * @return array
     */
    protected function get_files_for_transaction( array $files, array $transaction )
    {
        $result = array();
        foreach ( $files as $file ) {
            if ( $file['transaction_id'] == $transaction['id'] ) {
                $result[] = $file;
            }
        }

        return $result;
    }
}