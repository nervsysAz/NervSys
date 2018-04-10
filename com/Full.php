<?php
/**
 * User : Az
 * Date : 2017/03/29
 * Time : 19:00
 */

namespace com;

use core\ctr\router;

class Full
{
    public static $tz = [
        'full' => [],
    ];

    private static $link;

    //The type of text of the table
    private static $text = [
        'char','varchar','text',
    ];

    //The type of number of the table
    private static $number = [
        'int','tinyint','float','double'
    ];

    //The data table to be filled  or old table
    private static $table;

    //Get the table fields
    private static $fieldInfoArr;

    //The cut field string
    private static $fieldStr = '';

    public static function init()
    {
        router::$data['table'] = pdo::$prefix . router::$data['table'];
        self::$link = router::$data['mysql'];
        self::$table = router::$data['table'];
    }

    /**
     * The table data with fill
     * @return string
     */
    public static function full()
    {
        //'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_name=\'' .$table. '\'  AND table_schema= \'' . pdo::$db_name .'\'';
        self::$fieldInfoArr = self::getFields(self::$table);

        if(self::insertSQL()){
            $rows = self::copy(self::$fieldStr,self::$table);
            return $rows ? '成功插入 ' . $rows . ' 条数据!!!' : '操作失败';
        }else {
            return '插入失败';
        }
    }

    /**
     * Get the table field info and shift the primary key
     * @return array
     */
    private static function getFields()
    {
        $getFieldSQL = 'DESC '.self::$table;
        var_dump($getFieldSQL);
        $statement = self::$link->prepare($getFieldSQL);
        $statement->execute();
        $actionRes = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $fieldInfoArr = array_column($actionRes,'Type','Field');
        array_shift($fieldInfoArr);
        return $fieldInfoArr;
    }

    /**
     * Assembly needs to execute inserted SQL
     * @return bool|mixed
     */
    private static function insertSQL()
    {
        $str = implode(range('A','Z'),'');
        $value = '';

        foreach(self::$fieldInfoArr as $field => $length){

            //Get the field type;
            $fieldType = substr($length,0,strpos($length,'('));

            //Get the field length;
            $fieldLength = rtrim(ltrim(strstr($length,'('),'('),')');

            //Splice field string
            self::$fieldStr .= $field . ',';

            //Splice SQL
            $value .= !in_array($fieldType,self::$text) ? 9 . ',' : '\''.substr(str_shuffle($str) ,0,$fieldLength) . '\',';
        }

        //Store the fields
        self::$fieldStr =  rtrim(self::$fieldStr,',');

        //Splice the insert sql
        $insertSQL = 'INSERT INTO ' . self::$table . '(' . self::$fieldStr. ') VALUE(' . rtrim($value,',') . ')';
        return self::query($insertSQL) ?? false;

    }

    /**
     * Send the sql
     * @param $sql      [execution sql]
     * @return mixed
     */
    private static function query($sql)
    {
        $statement = self::$link->prepare($sql);
        $statement->execute();
        return $statement->rowCount();
    }

    /**
     * Copy the table's info
     * @param string $fieldStr     [Required fields]
     * @param string $toTable      [New table]
     * @return mixed
     */
    private static function copy(string $fieldStr , string $toTable)
    {
        $copySQL = 'INSERT INTO ' . self::$table . " ({$fieldStr}) SELECT {$fieldStr} FROM {$toTable}";
        $statement = self::$link->prepare($copySQL);
        $statement->execute();
        return $statement->rowCount();
    }
}