<?php
/**
 * This file contains the most common used functions for KoolReport.
 *
 * @category  Core
 * @package   KoolReport
 * @author    KoolPHP Inc <support@koolphp.net>
 * @copyright 2017-2028 KoolPHP Inc
 * @license   MIT License https://www.koolreport.com/license#mit-license
 * @link      https://www.koolphp.net
 */

namespace koolreport\core;

/**
 * Utility class contain utility static function supporting report
 *
 * @category  Core
 * @package   KoolReport
 * @author    KoolPHP Inc <support@koolphp.net>
 * @copyright 2017-2028 KoolPHP Inc
 * @license   MIT License https://www.koolreport.com/license#mit-license
 * @link      https://www.koolphp.net
 */
class Utility
{
    /**
     * The unique id that use to generate unique name of widget
     * 
     * @var integer $_uniqueId The unique id that use to generate 
     *                         unique name of widget
     */
    static $_uniqueId;
    
    /**
     * Return unique id each time is called
     * 
     * @return string Unqiue id generated
     */
    static function getUniqueId()
    {
        Utility::$_uniqueId++;
        return uniqid().Utility::$_uniqueId;
    }

    /**
     * Try to get type of a value
     *
     * @param mixed $value A value needed to guess type
     *  
     * @return string Possible type of a value
     */
    static function guessType($value)
    {
        $map = array(
            "float"=>"number",
            "double"=>"number",
            "int"=>"number",
            "integer"=>"number",
            "bool"=>"number",
            "numeric"=>"number",
            "string"=>"string",
        );

        $type = strtolower(gettype($value));
        foreach ($map as $key=>$value) {
            if (strpos($type, $key)!==false) {
                return $value;
            }
        }
        return "unknown";
    }

    /**
     * Recursive copy content of folder to destination
     * 
     * @param string $src Path to source
     * @param string $dst The destination you want to copy to 
     * 
     * @return null
     */
    static function recurse_copy($src,$dst)
    { 
        $dir = opendir($src); 
        @mkdir($dst); 
        while (false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if (is_dir($src . '/' . $file)) { 
                    Utility::recurse_copy($src . '/' . $file, $dst . '/' . $file); 
                } else { 
                    copy($src . '/' . $file, $dst . '/' . $file); 
                } 
            } 
        } 
        closedir($dir); 
    }

    /**
     * Format the value with provided format settings
     * 
     * @param mixed $value  Value we want to format
     * @param array $format The format settings of the value
     * 
     * @return string Formatted value in string 
     */
    static function format($value,$format)
    {
        $f = Utility::get($format, "format", true);
        if ($f===false) {
            return $value;
        }
        
        $type = Utility::get($format, "type", "unknown");
        switch($type)
        {
        case "number":
            $decimals = Utility::get($format, "decimals", 0);
            $dec_point = Utility::get(
                $format,
                "decPoint",
                Utility::get(
                    $format,
                    "decimalPoint",
                    Utility::get(
                        $format,
                        "dec_point",
                        "."
                    )
                )
            );

            $thousand_sep = Utility::get(
                $format,
                "thousandSep",
                Utility::get(
                    $format,
                    "thousandSeparator",
                    Utility::get(
                        $format,
                        "thousand_sep",
                        ","
                    )
                )
            );
            $prefix = Utility::get($format, "prefix", "");
            $suffix = Utility::get($format, "suffix", "");
            return $prefix
                .number_format($value, $decimals, $dec_point, $thousand_sep)
                .$suffix;
            break;
        case "string":
            $prefix = Utility::get($format, "prefix", "");
            $suffix = Utility::get($format, "suffix", "");
            return $prefix.$value.$suffix;
            break;
        case "datetime":
            $dateFormat = Utility::get($format, "format", "Y-m-d H:i:s");
        case "date":
            $dateFormat = isset($dateFormat)
                ?$dateFormat
                :Utility::get($format, "format", "Y-m-d");
        case "time":
            $dateFormat = isset($dateFormat)
                ?$dateFormat
                :Utility::get($format, "format", "H:i:s");
            $displayFormat = Utility::get($format, "displayFormat");
            if ($displayFormat && $value) {
                if ($fvalue = \DateTime::createFromFormat($dateFormat, $value)) {
                    return $fvalue->format($displayFormat);
                }
            }
            break;
        }
        return $value;
    }
    
    /**
     * Get the short name of a class
     * 
     * The method will return only the name of the class and ignore its namespace
     * 
     * @param object $obj The object you want to get classname
     * 
     * @return string The shortname of class
     */
    static function getClassName($obj)
    {
        $reflection = new \ReflectionClass($obj);
        return $reflection->getShortName();
    }
    
    /**
     * Traverse through the structure of object and find the js function
     * 
     * @param object $obj   The object
     * @param array  $marks The mark
     * 
     * @return array The new marks
     */
    static function mark_js_function(&$obj,&$marks=array())
    {
        foreach ($obj as $k=>&$v) {
            switch(gettype($v))
            {
            case "object":
            case "array":
                Utility::mark_js_function($v, $marks);
                break;
            case "string":
                $tsv = trim(strtolower($v));
                if (strpos($tsv, "function")===0  
                    && (strrpos($tsv, "}")===strlen($tsv)-1 
                    || strrpos($tsv, "()")===strlen($tsv)-2)
                ) {
                    $marks[] = trim($v);
                    $obj[$k] = "--js(".(count($marks)-1).")";
                }
                break;
            }
        }
        return $marks;
    }

    /**
     * Get the json of an object
     * 
     * @param object $object The object needs to be encoded
     * @param int    $option The json_encode() additional option
     * 
     * @return string The json string of objects
     */
    static function jsonEncode($object,$option=0)
    {
        $marks = Utility::mark_js_function($object);
        $text = json_encode($object, $option);
        foreach ($marks as $i=>$js) {
            $text = str_replace("\"--js($i)\"", $js, $text);
        }
        return $text;
    }
    
    /**
     * Get wether an array is an associate array
     * 
     * @param array $arr The array that you want to test
     * 
     * @return bool Whether the array is an associate array
     */
    static function isAssoc($arr)
    {
        if (gettype($arr)!="array") {
            return false;
        }
        if ($arr===null || $arr===array()) {
            return false;
        }
        if (array_keys($arr)===range(0, count($arr)-1)) {
            return false;
        }
        return true;
    }

    /**
     * Get value from array with keys, return default if not found
     * 
     * The function support the list of keys in order as well
     * 
     * @param array $arr     The array that you want to test
     * @param mixed $keys    Could be name of key or an array containing list of key path
     * @param mixed $default Default value if no value for key is found
     * 
     * @return mixed Value at key path
     */
    static function get($arr,$keys,$default=null)
    {
        if (! is_array($arr)) {
            return $default;
        }
        if (is_array($keys) and count($keys) > 0) {
            foreach ($keys as $key) {
                $arr = self::get($arr, $key, $default);
            }
            return $arr;
        }
        if (is_string($keys) || is_int($keys)) {
            return isset($arr[$keys]) ? $arr[$keys] : $default;
        } 
        return $default;
    }
    /**
     * Init an key value inside an array
     * 
     * @param array  $arr     The array
     * @param string $key     The key
     * @param mixed  $default The default value to fill if key is not found
     * 
     * @return array The array
     */
    static function init(&$arr, $keys, $default = null) 
    {
        if (is_array($keys)) {
            if (count($keys) === 0) return $default;
            $fKey = $keys[0];
            if (count($keys) === 1) return self::init($arr, $fKey, $default);
            if (! is_array($arr[$fKey])) $arr[$fKey] = [];
            $restKeys = array_slice($keys, 1);
            return self::init($arr[$fKey], $restKeys, $default);
        } else {
            if (! isset($arr[$keys])) {
                $arr[$keys] = $default;
            }
            return $arr[$keys];
        }
    }

    /**
     * Get array if the value inside an array is a string
     * 
     * @param array  $arr     The array
     * @param string $key     The key
     * @param mixed  $default The default value
     * 
     * @return array Return array result
     */
    static function getArray($arr,$key,$default=array())
    {
        $value = Utility::get($arr, $key);
        return ($value!=null)?explode(',', $value):$default;
    }

    /**
     * Get only some of the keys from an array
     * 
     * @param array  $arr  The array
     * @param string $keys List of keys in string and separate with comma(,)
     * 
     * @return array The filtered array with only specified keys
     */
    static function filterIn($arr,$keys)
    {
        $keys = explode(",", $keys);
        $result = array();
        foreach ($arr as $key=>$value) {
            if (in_array($key, $keys)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    /**
     * Remove some specific keys from an array
     * 
     * @param array  $arr  The array
     * @param string $keys The keys in string format seperated by comma
     * 
     * @return array New array excluding selected keys
     */
    static function filterOut($arr,$keys)
    {
        $keys = explode(",", $keys);
        $result = array();
        foreach ($arr as $key=>$value) {
            if (!in_array($key, $keys)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * A mass string replace with parameters
     * 
     * @param string $str    The template string
     * @param array  $params An associate array containing key value to replace
     * 
     * @return string The string that is replaced key with value
     */
    static function strReplace($str,$params)
    {
        foreach ($params as $k=>$v) {
            $str = str_replace($k, $v, $str);
        }
        return $str;
    }

    /**
     * Return the full path to class of an object
     * 
     * @param object $obj The object
     * 
     * @return string The full path to the class of an object
     */
    static function getClassPath($obj)
    {
        $class_info = new \ReflectionClass($obj);
        return $class_info->getFileName();
    }

    /**
     * Print nicely value of an array. This method is useful for debugging
     * 
     * @param array $arr The array
     * 
     * @return null
     */
    static function prettyPrint($arr)
    {
        echo '<pre>';
        echo json_encode($arr, JSON_PRETTY_PRINT), PHP_EOL;
        echo '</pre>';  
    }

    /**
     * Return string with replaced first occurerence only
     * 
     * @param string $from    The needle
     * @param string $to      The replacement
     * @param string $content The haystack
     * 
     * @return string String with replaced first occurerence only
     */
    static function str_replace_first($from, $to, $content)
    {
        $from = '/'.preg_quote($from, '/').'/';
        return preg_replace($from, $to, $content, 1);
    }    

    /**
     * Get the doument root
     * 
     * @return string the document root path
     */
    static function getDocumentRoot()
    {
        //The old method is to use the document_root from $_SERVER
        //Howerver in some hosting the document root is not the same
        //with the root folder of the website, so we add backup with
        //second way  to calculate the document root with script_name 
        //and script_filename
        $old_way = str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"]));
        $script_filename = str_replace(
            "\\",
            "/",
            realpath($_SERVER["SCRIPT_FILENAME"])
        );
        $script_name = str_replace("\\", "/", realpath($_SERVER["SCRIPT_NAME"]));
        $new_way = str_replace($script_name, "", $script_filename);
        
        if ($old_way==$new_way) {
            return $old_way;
        } else if (is_dir($old_way)) {
            return $old_way;
        } else {
            return $new_way;
        }
    }

    /**
     * Convert path to use forward slash format
     * 
     * @param string $path The path you want to covnvert
     * 
     * @return string The converted path which use forward slash as standard
     */
    static function standardizePathSeparator($path)
    {
        //We use "/" for all system
        return str_replace("\\", "/", $path);
    }
    
    /**
     * Get the dirname
     * 
     * @param string $path The path of file or folder
     * 
     * @return string The parent folder
     */
    static function getDir($path) 
    {
        return substr($path, 0, strrpos($path, '/'));
    }
    
    
    /**
     * Get the dirname
     * 
     * @param string $realpath The real path
     * 
     * @return string Return the symbolic path
     */
    static function getSymbolicPath($realpath)
    {
        $root = $_SERVER['DOCUMENT_ROOT'];
        $script = $_SERVER['SCRIPT_FILENAME'];
        $root = str_replace('\\', '/', $root);
        $script = str_replace('\\', '/', $script);
        $realpath = str_replace('\\', '/', $realpath);
        
        $dir = str_replace($root, '', $script);
        $pos = false;
        $dir = self::getDir($dir);
        while (! empty($dir)) {
            $pos = strpos($realpath, $dir);
            if ($pos) {
                break;
            }
            $dir = self::getDir($dir);
        }
        if ($pos) {
            $realpath = $root . substr($realpath, $pos);
        }
        return $realpath;
    }

    /**
     * Merge array recursively
     * 
     * @return array The merged array
     */
    static function arrayMergeRecursive($array1,$array2)
    {
        $merged = $array1;
    
        foreach ($array2 as $key => & $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursive($merged[$key], $value);
            } else if (is_numeric($key)) {
                 if (!in_array($value, $merged)) {
                    $merged[] = $value;
                 }
            } else {
                $merged[$key] = $value;
            }
        }
    
        return $merged;
    }

    static function map($funcOrArray, $args, $defaultValue = null)
    {
        // if ($defaultValue === "{{identical}}") $defaultValue = $args;
        if (is_array($funcOrArray)) {
            return self::get($funcOrArray, $args, $defaultValue);
        }
        else if (is_callable($funcOrArray)) {
            if (! is_array($args)) $args = [$args];
            return call_user_func_array($funcOrArray, $args);
        }
        return $defaultValue;
    }

    public static function formatValue($value, $meta, $row = null)
    {
        $formatValue = self::get($meta, "formatValue", null);

        if (is_string($formatValue)) {
            eval('$fv="' . str_replace('@value', '$value', $formatValue) . '";');
            return $fv;
        } else if (is_callable($formatValue)) {
            return $formatValue($value, $row);
        } else {
            return self::format($value, $meta);
        }
    }
}
