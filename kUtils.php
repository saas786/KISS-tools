<?
/**
 * kUtils
 * ======
 * This class provides several helpful methods for different tasks.
 */
class kUtils {
    /**
     * This function takes a array-map to return only the specified keys from the input array and convert their values to a given data type/format.
     * Format the array map like this:
     *
     *     $map = array(
     *         'key' => 'string|trim'
     *     );
     *
     * Add all keys you want to preserve from the input array and apply the desired data type/format and actions.
     * In our example, the command "string|trim" converts the value into a string, and applies a trim.
     *
     * Avaliable data types:
     * string   Convert the value into a string
     * integer  Convert the value into an integer
     * url      If the given value is no URL, it becomes NULL
     * email    If the given value is no e-mail, it becomes NULL
     * array    Convert the value into an array (heads up: its combinable with integer => "array|integer")
     * boolean  Convert the value into boolean true/false. (Will also convert a string "true" or "false" correctly)
     * regex    Apply a regex to the value and use the occurance, or NULL
     *          Use groups in your regex and pass a number as third parameter to the regex call to extract the given group.
     *          Use groups and pass "extract" as third parameter, to get an array with all group contents.
     *
     * Available actions:
     * trim                 For use with string. Will remove whitespaces at the beginning and end of the string.
     * set|a,b,c,...        Will only preserve the value if its in the given set of values. Otherwise, value will become NULL
     * limit|10             Will cut the value after X characters (strings or integers)
     * range|1,10           For use with integer. Will only preserve the value, if its in the given range. Otherwise it becomes NULL
     *
     * Special actions:
     * boolcast|TRUEVALUE   Can be used with the data type "bool/boolean" to set the value to TRUE on the given value, FALSE on any other value.
     * intcast              Can be used with the data type "array" to cast all array values to an integer.
     *
     * NOTE: You can create recursive array maps, if you want to:
     *
     *     $map = array(
     *        'first_name' => 'string|trim',
     *        'last_name' => 'string|trim',
     *        'age' => 'integer|range|18,99',
     *        'mail_address' => 'mail',
     *        'social' => array(
     *            'facebook' => 'url',
     *            'twitter' => 'url'
     *        )
     *     );
     *
     * The special key "{{repeat}}":
     * The array_map function enables you to map arrays of repeating objects as well.
     *
     * $map = array(
     *    'users' => array(
     *       '{{repeat}}' => 0,
     *       'first_name' => 'string|trim',
     *       'last_name' => 'string|trim'
     *    );
     * );
     *
     * The special "{{repeat}}" key tells array_map that you await "users" to be an array containing multiple objects
     * with a "first_name" and "last_name" property.
     * Set "{{repeat}}" to any integer > 0 to limit the amount of objects in that array.
     *
     * @param {Array} $input
     * @param {Array} $map
     * @param {Bool} $objectify (optional) Returns an Object, instead of an associative array. default = false
     * @return {Array|Object}
     */
    function array_map($input, $map, $objectify = FALSE) {
        if (!is_array($input)) {
            $input = array();
        }
        if (!is_array($map)) {
            die('array_map - parameter $map should be an array.');
        }

        $result = array();

        foreach ($map as $k => $v) {
            if (!isset($input[$k])) {
                $value = NULL;
            }
            else {
                $value = $input[$k];
            }

            if (is_array($v)) {
                //Mapping an Object
                if (isset($v['{{repeat}}'])) {
                    $max = (int)$v['{{repeat}}'];
                    if ($v['{{repeat}}'] === TRUE) {
                        $max = 0;
                    }

                    unset($v['{{repeat}}']);
                    $subresult = array();
                    $cnt = 0;
                    foreach ($value as $val) {
                        $cnt++;
                        if ($max && $cnt > $max) {
                            break;
                        }
                        $subresult[] = $this->array_map($val, $v);
                    }
                    $value = $subresult;
                }
                else {
                    $value = $this->array_map($value, $v);
                }

                if($action == 'intcast'){
                    foreach($value as $k => $v){
                        $value[$k] = (int)$v;
                    }
                }
            }
            else {
                //Normal mapping
                $p = explode('|', $v);
                $format = $p[0];
                $action = @$p[1];
                $info = @$p[2];

                switch ($format) {
                    case 'bool':
                    case 'boolean':
                        if($action == 'boolcast'){
                            if($value == $info) $value = TRUE; else $value = FALSE;
                        }

                        if (strtolower($value) == 'true' || $value == 1) {
                            $value = TRUE;
                        }
                        if (strtolower($value) == 'false' || $value == 0) {
                            $value = FALSE;
                        }
                        $value = (bool)$value;
                        break;
                    case 'str':
                    case 'string':
                        $value = (string)$value;
                        if ($action == 'trim') {
                            $value = trim($value);
                        }
                        if ($action == 'expect_length') {
                            if (strlen($value) != $info) {
                                $value = NULL;
                            }
                        }
                        if ($action == 'striptags') {
                            $value = strip_tags($value);
                        }
                        if ($action == 'htmlentities') {
                            $value = htmlentities($value);
                        }
                        break;
                    case 'int':
                    case 'integer':
                        $value = (int)$value;
                        if ($action == 'range') {
                            $boundaries = explode(',', $info);
                            if ($value < (int)$boundaries[0]) {
                                $value = (int)$boundaries[0];
                            }
                            if ($value > (int)$boundaries[1]) {
                                $value = (int)$boundaries[1];
                            }
                        }
                        break;
                    case 'url':
                        $value = (string)$value;
                        if (preg_match('/(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:\/~\+#]*[\w\-\@?^=%&amp;\/~\+#])?/', $value) === 0) {
                            $value = NULL;
                        }
                        break;
                    case 'email':
                    case 'mail':
                        $value = (string)$value;
                        if (preg_match('/^([\+a-zA-Z0-9_.+-])+@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/', $value) === 0) {
                            $value = NULL;
                        }
                        break;
                    case 'array':
                        $value = (array)$value;
                        if ($action) {
                            foreach ($value as $sk => $sv) {
                                switch ($action) {
                                    case 'int':
                                    case 'integer':
                                        $value[$sk] = (int)$sv;
                                        break;
                                    case 'bool':
                                    case 'boolean':
                                        if (strtolower($value) == 'true') {
                                            $value = TRUE;
                                        }
                                        if (strtolower($value) == 'false') {
                                            $value = FALSE;
                                        }
                                        $value = (bool)$value;
                                        break;
                                }
                            }
                        }
                        break;
                    case 'regex':
                        $value = (string)$value;
                        if (preg_match($action, $value, $matches)) {
                            if (is_numeric($info)) {
                                if (isset($matches[(int)$info])) {
                                    $value = $matches[(int)$info];
                                }
                                else {
                                    $value = NULL;
                                }
                            }
                            else {
                                if ($info == 'extract') {
                                    if (count($matches) > 1) {
                                        $value = array_slice($matches, 1);
                                    }
                                    else {
                                        $value = array();
                                    }
                                }
                                else {
                                    $value = $matches[0];
                                }
                            }
                        }
                        else {
                            $value = NULL;
                        }
                }

                //The value should match into the given set. If not, the value becomes null.
                //Thats a global action!
                if ($action == 'set') {
                    $work = null;
                    $set = explode(',', $info);
                    foreach ($set as $set_item) {
                        if ($value == $set_item) {
                            $work = $value;
                            break;
                        }
                    }
                    $value = $work;
                }

                if ($action == 'limit') {
                    $value = substr($value, 0, $info);
                    if ($format == 'int' || $format == 'integer') {
                        $value = (int)$value;
                    }
                }

                $result[$k] = $value;
            }
        }

        if ($objectify) {
            $result = json_decode(json_encode($result));
        }

        return $result;
    }

    /**
     * Returns a new array where the value from given field from the input array is used as the key.
     * This function is mainly used to make the id of a dataset the array key.
     *
     * Example:
     * $in = array(
     *      array('id' => 1, 'title' => 'peter'),
     *      array('id' => 2, 'title' => 'paul'),
     *      array('id' => 3, 'title' => 'mary'),
     * );
     * $out = array_id_to_key($in);
     * $out = array(
     *      '1' => array('id' => 1, 'title' => 'peter'),
     *      '2' => array('id' => 2, 'title' => 'paul'),
     *      '3' => array('id' => 3, 'title' => 'mary'),
     * );
     * @param {Array} $array
     * @param {String} $key_field
     * @return {Array}
     */
    function array_id_to_key($array, $key_field = 'id') {
        $result = array();
        foreach ($array as $v) {
            $result[$v[$key_field]] = $v;
        }

        return $result;
    }

    /**
     * Will sort a multidimensional array by the values of a specific key.
     * Pass an array of strings to $key_field to order by multiple keys.
     * Prepend a "+" or "-" to a key to order ascending or descending. Default: ascending.
     *
     * @param {Array} $array
     * @param {String} $key
     * @return {Array}
     */
    function array_sort_by_key($array, $key) {
        if (!is_array($key)) {
            $key = array($key);
        }

        $this->sortkey = $key;
        uasort($array, array(
                            $this,
                            'cmp'
                       ));
        return $array;
    }

    private $sortkey;

    function cmp($a, $b) {
        foreach ($this->sortkey as $v) {
            $f = substr($v, 0, 1);
            if ($f == '+' || $f == '-') {
                $v = substr($v, 1);
            }
            if ($a[$v] == $b[$v]) {
                continue;
            }
            if ($f == '-') {
                return ($a[$v] > $b[$v]) ? -1 : 1;
            }
            else {
                return ($a[$v] < $b[$v]) ? -1 : 1;
            }
        }
        return 0;
    }

    /**
     * This will load a simple template file, parse it and replace tags with data.
     *
     * Tags are written like this:
     * {{mytag}}
     *
     * Pass a associative array to the function, where array-keys are applyable to tag names.
     *
     * @param {String} $template_file Filename of the Template to load (plain text, no PHP)
     * @param {Array} $template_data Associative (key/value) Array with replacement data
     * @return {String}
     */
    function template($template_string, $template_data = array()) {

        foreach ($template_data as $k => $v) {
            $template_string = str_replace('{{' . $k . '}}', $v, $template_string);
        }

        //Remove all missing tags from the template.
        $template_string = preg_replace('#\{\{.+?\}\}#ms', '', $template_string);

        return $template_string;
    }

    /**
     * Returns a hashed password. String Length: 72 characters.
     * @param {Sting} $inString
     * @param {String} $salt (optional)
     * @return {String}
     */
    function hash_password($password, $salt = '') {
        if (!$salt) {
            $salt = substr(md5(uniqid('')), 0, 8);
        }
        else {
            $salt = substr($salt, 0, 8);
        }

        return $salt . hash('sha256', $salt . $password);
    }
}