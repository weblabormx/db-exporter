<?php 
namespace Elimuswift\DbExporter;

use DB;

abstract class DbExporter
{
    /**
     * Contains the ignore tables
     * @var array $ignore
     */
    public static $ignore = array('migrations');
    public static $remote;

    /**
     * Get all the tables
     * @return mixed
     */
    public $database;

    /**
     * Select fields
     *
     * @var array $selects
     **/
    protected $selects = array(
        'column_name as Field',
        'column_type as Type',
        'is_nullable as Null',
        'column_key as Key',
        'column_default as Default',
        'extra as Extra',
        'data_type as Data_Type'
    );
    /**
     * Select fields from  constraints
     *
     * @var array $constraints
     **/
    protected $constraints = array(
        'key_column_usage.table_name as Table',
        'key_column_usage.column_name as Field',
        'key_column_usage.referenced_table_name as ON',
        'key_column_usage.referenced_column_name as References',
        'REFERENTIAL_CONSTRAINTS.UPDATE_RULE as onUpdate',
        'REFERENTIAL_CONSTRAINTS.DELETE_RULE as onDelete',
    );
    protected function getTables()
    {
        $pdo = DB::connection()->getPdo();
        return $pdo->query('SELECT table_name FROM information_schema.tables WHERE table_schema="' . $this->database . '"');
    }

    public function getTableIndexes($table)
    {
        $pdo = DB::connection()->getPdo();
        return $pdo->query('SHOW INDEX FROM ' . $table . ' WHERE Key_name != "PRIMARY"');
    }

    /**
     * Get all the columns for a given table
     * @param $table
     * @return mixed
     */
    protected function getTableDescribes($table)
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', '=', $this->database)
            ->where('table_name', '=', $table)
            ->get($this->selects);
    }

    /**
     * Get all the foreign key constraints for a given table
     * @param $table
     * @return mixed
     */
    protected function getTableConstraints($table)
    {
        return DB::table('information_schema.key_column_usage')
            ->distinct()
            ->join('information_schema.REFERENTIAL_CONSTRAINTS', 'REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME','=','key_column_usage.CONSTRAINT_NAME')
            ->where('key_column_usage.table_schema', '=', $this->database)
            ->where('key_column_usage.table_name', '=', $table)
            ->get($this->constraints);
    }

    /**
     * Grab all the table data
     * @param $table
     * @return mixed
     */
    protected function getTableData($table)
    {
        return DB::table($this->database.'.'.$table)->get();
    }

    /**
     * Write the file
     * @return mixed
     */
    abstract public function write();

    /**
     * Convert the database to a usefull format
     * @param null $database
     * @return mixed
     */
    abstract public function convert($database = null);

    /**
     * Put the converted stub into a template
     * @return mixed
     */
    abstract protected function compile();
}