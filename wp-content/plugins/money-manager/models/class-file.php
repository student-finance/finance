<?php

namespace MoneyManager\Models;

defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

/**
 * Class File
 * @package MoneyManager\Models
 */
class File extends Base
{
    protected static $table = 'money_manager_files';

    protected static $fillable = [
        'attachment_id',
        'filename',
        'description',
        'url',
    ];

    protected static $hidden = [
        'created_at',
        'updated_at',
    ];

    protected static $casts = [
        'account_id' => 'int',
        'transaction_id' => 'int',
        'attachment_id' => 'int'
    ];

    /**
     * Destroy all files except those with given IDs
     *
     * @param array $ids_to_keep
     * @param array $where
     * @return void
     */
    public static function destroyExcept( array $ids_to_keep, array $where )
    {
        global $wpdb;

        $table_name = static::table_name();
        $query = "delete from $table_name";

        $criteria = array();
        $values = array();
        foreach ( $where as $field => $value ) {
            $criteria[] = "$field = %d";
            $values[] = $value;
        }
        if ( ! empty ( $ids_to_keep ) ) {
            $ids_placeholders = implode( ',', array_fill( 0, count( $ids_to_keep ), '%d' ) );
            $criteria[] = "id not in ($ids_placeholders)";
            $values = array_merge( $values, $ids_to_keep );
        }
        $clause = implode( ' and ', $criteria );
        if ( $clause != '' ) {
            $query .= ' where ' . $wpdb->prepare( $clause, $values );
        }

        $wpdb->query( $query );
    }
}