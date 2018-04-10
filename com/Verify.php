<?php
/**
 * User  : Az
 * Date  : 2017/03/29
 * Time  : 19:00
 * Tool  : PHPStorm
 * Email : 928250641@qq.com or engineeraz@163.com
 *
 * This is a verification class
 * Developers must follow the format of validation rules to invoke
 * Developers are free to add verification methods
 * Validation rules can pass parameters or set rules for verify
 */

namespace com;

use core\ctr\router;
use ext\pdo;

class Verify
{
    public static $tz = [
        'verify' => []
    ];

    //Error message to array
    private static $errorArr = [];
    //Verify rule
    public static $verify = [];
    //Verify result
    private static $verifyRes = [];
    //Request parameters
    public static $parameter = [];
    //pdo connect
    private static $link = null;
    //

    public static function init()
    {
        self::$errorArr = json_decode(file_get_contents(__DIR__ . '/RuleErr.json'),true);
        self::$parameter = router::$data;
        unset(self::$parameter['mysql']);
    }

    /**
     * Verification method
     * @param array $regulation
     * @return array|bool
     */
    public static function verify($regulation = [])
    {
        self::init();
        self::$parameter = router::$data;
        //Check if the rule is empty;
        if(empty($regulation) && empty(self::$verify)) {
            return [
                'code' => 404,
                'param' => 'rule',
                'msg'  => self::$errorArr['rule']
            ];
        }
        //Check if the rule is empty;
        if(!empty($regulation)) self::$verify = $regulation;

        //Start verification;
//        self::run();
//        print_r(self::$verifyRes);
//        return self::run();
        if(self::run()) return empty(self::$verifyRes) ? false : self::$verifyRes ;
    }

    /**
     * Get the params and rule info to Start verification;
     * @return bool
     */
    public static function run():bool
    {
        //Get the params and rule info;
        foreach(self::$verify as $param => $roleStr){
            $datum = explode(' ',str_replace('|',' ',$roleStr));
            foreach ($datum as $key => $role){

                //Get the funcName and rule
                if(strpos($role,':') !== false){
                    $val = explode(' ',str_replace(':',' ',$role));
                    $func = $val[0];
                }else{
                    $func = $role;
                }

                if(strpos($func,'=') !== false){
                    $val = explode(' ',str_replace('=',' ',$role));
                    $func = $val[0];
                }

                //Determine if the method exists
                if(!method_exists(__CLASS__,$func)){
                    self::$verifyRes = [
                        'code' => 404,
                        'func' => $func,
                        'msg'  => self::$errorArr['func']
                    ];
                    return true;
                }
                //Check the mysql connect
                if(is_null(self::$link))  self::$link = pdo::connect();
                //Perform verification
                $data = ['key'=>$param,'rule'=>$val[1] ?? ''];
                if(forward_static_call([__CLASS__,$func],$data)) return true;
            }
        }
        return true;
    }

    /**
     * Check if the parameter is exists
     * @param $param
     * @return bool
     */
    private static function unique($param)
    {
        if('' === self::$parameter[$param['key']]) return self::require(['key'=>$param['key']]);
        $value = self::$parameter[$param['key']];

//        $table = pdo::$prefix.$param['key'];
        $table = 'awt_' . $param['rule'];
        $checkSQL = "SELECT {$param['key']} FROM {$table} WHERE {$param['key']}= '{$value}' LIMIT 1";
        $state = self::$link->prepare($checkSQL);
        $state->execute();
        return $state->fetch(\PDO::FETCH_ASSOC) ? self::assignment($param['key'], __FUNCTION__) : false;
    }

    /**
     * Check if the parameter is empty
     * @param $param
     * @return bool
     */
    private static function require(array $param):bool
    {
        return strlen($param['key']) < 1 || ord(self::$parameter[$param['key']]) == 32? self::assignment($param['key'], __FUNCTION__) : false;
    }

    /**
     * Check if the parameter is greater than the specified length
     * @param $param
     * @return bool
     */
    private static function min(array $param):bool
    {
        return strlen(self::$parameter[$param['key']]) < $param['rule'] ? self::assignment($param['key'], __FUNCTION__) : false;
    }

    /**
     * Check if the parameter is less than the specified length
     * @param $param
     * @return bool
     */
    private static function max(array $param):bool
    {
        return strlen(self::$parameter[$param['key']]) > $param['rule'] ? self::assignment($param['key'], __FUNCTION__) : false;
    }

    /**
     * Check if the parameter is number
     * @param $param
     * @return bool
     */
    private static function number(array $param):bool
    {
        return !is_numeric(self::$parameter[$param['key']]) ? false : self::assignment($param['key'], __FUNCTION__);
    }

    /**
     * Check if the parameter is integer
     * @param $param
     * @return bool
     */
    private static function integer(array $param):bool
    {
        return (int)self::$parameter[$param['key']] ? false : self::assignment($param['key'], __FUNCTION__);
    }


    /**
     * Check if the parameter is email
     * @param $param
     * @return bool
     */
    private static function email(array $param):bool
    {
        return filter_var(self::$parameter[$param['key']],FILTER_VALIDATE_EMAIL) === false ? self::assignment($param['key'], __FUNCTION__) : false;
    }

    /**
     * Check if the parameter is id card
     * @param $param
     * @return bool
     */
    private static function card(array $param):bool
    {
        return preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/",self::$parameter[$param['key']])
            ? false
            : self::assignment($param['key'], __FUNCTION__);
    }

    /**
     * Check if the parameter is phone
     * @param $param
     * @return bool
     */
    private static function phone(array $param):bool
    {
        return preg_match("/^1[34578]{1}\d{9}$/",self::$parameter[$param['key']]) ? false : self::assignment($param['key'], __FUNCTION__);
    }

    /**
     * Set the verification result
     * @param $param
     * @param $func
     * @return bool
     */
    private static function assignment(string $param,string $func):bool
    {
        self::$verifyRes = [
            'code' => 422,
            'param' => $param,
            'msg'  => self::$errorArr[$func]
        ];
        return true;
    }
}