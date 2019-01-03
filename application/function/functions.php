<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/12/28
 * Time: 10:06
 */


/**
 * 生成session key
 *
 * @access public
 * @param  string $session_id session id
 * @return string    $session_key   session key
 */
function gen_session_key($session_id)
{
    return sprintf('%08x', crc32(ROOT_FW_PATH . $session_id));
}

/**
 * 按字段重新排序数组
 *
 * @access public
 * @param  array $arr 数组
 * @param  array $field 字段
 * @param  int $mod 模式
 * @return array $newarr    新数组
 */
function restruct_arr2_by_field($arr, $field, $mod = 0)
{
    $newarr = array();
    if (!empty($arr) && $field) {
        foreach ($arr as $key => $val) {
            if ($mod == 1) {
                $newarr[$val[$field]] = $val;
            } else {
                $newarr[$val[$field]][] = $val;
            }
        }
    } else {
        $newarr = $arr;
    }
    return $newarr;
}

/**
 * 获取当天的临时文件夹的地址
 *
 * @access public
 * @param bool $absolute 是否返回绝对路径
 * @return mixed|string
 */
function get_temp_path($absolute = false)
{
    $abs = ROOT_FW_PATH . "data/";
    if ($absolute) {
        $return = $abs;
    } else {
        $f_path = stristr(str_replace("\\", "/", __FILE__), FW_NAME);
        $project_path = str_replace($f_path, "", str_replace("\\", "/", __FILE__));
        $return = str_replace($project_path, "", ROOT_FW_PATH) . "data/";
    }
    $temp_path = "temp/" . $GLOBALS["header"]->route->application . "/" . date("Y-m-d");
    $path = array(
        "temp",
        $GLOBALS["header"]->route->application,
        date("Y-m-d")
    );
//    die($return);
    #不存在就创建
    if (!is_dir($return . $temp_path)) {
        foreach ($path as $key => $value) {
            if (!is_dir($abs . $value)) {
                mkdir($abs . $value);
            }
            $abs .= $value . "/";
        }
    }
    return $return . $temp_path . "/";
}

/**
 * 生成随机的16位随机文件名
 *
 * @return string
 */
function create_temp_file_name()
{
    $letters = range("a", "z");
    $seed = microtime_float() . $letters[rand(0, 25)] . real_ip();
    return $letters[rand(0, 25)] . substr(md5($seed), 15);
}

function getAgeByID($id)
{

//过了这年的生日才算多了1周岁
    if (empty($id)) return '';
    $date = strtotime(substr($id, 6, 8));
//获得出生年月日的时间戳
    $today = strtotime('today');
//获得今日的时间戳
    $diff = floor(($today - $date) / 86400 / 365);
//得到两个日期相差的大体年数

//strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
    $age = strtotime(substr($id, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;

    return $age;
}

function get_gender($cid)
{
    //根据身份证号，自动返回性别
    $sexint = (int)substr($cid, 16, 1);
    return $sexint % 2 === 0 ? '0' : '1';
}

/**
 * 记录debug日志
 *
 * @access public
 * @param $str
 * @param string $flag
 * @return bool
 */
function debug($str, $flag = 'debug')
{
    is_array($str) && $str = print_r($str, true);
    $dir = ROOT_FW_PATH . '/logs/' . $flag . '/';
    $str .= "\r\n\r\nClientIP:" . real_ip();
    !is_dir($dir) && @mkdir($dir, 0755, true);
    $file = $dir . date('Ymd') . '.log.txt';
    $fp = fopen($file, 'a');
    if (flock($fp, LOCK_EX)) {
        $content = "[" . date('Y-m-d H:i:s') . "]\r\n";
        $content .= $str . "\r\n\r\n";
        fwrite($fp, $content);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    } else {
        fclose($fp);
        return false;
    }
}

/**
 * 获取当前时间戳 精确到毫秒
 * @return int
 */
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * 保存base64格式的图片
 *
 * @access public
 * @param $pic
 * @param string $dir 保存地址
 * @param string $img_name 图片名
 * @param int $maxWidth 最大宽度
 * @param int $maxHeight 最大高度
 * @param string $type 图片格式
 * @return array
 */
function saveBase64Image($pic, $dir = '', $img_name = '', $maxWidth = 0, $maxHeight = 0, $type = 'jpg')
{
    /* 没有指定目录默认为根目录images */
    $return_result = array('code' => 0, 'msg' => '');
    if (empty($dir)) {
        /* 创建当月目录 */
        $dir = date('Ym');
        $dir = IMAGE_DIR . '/' . $dir . '/';
    } else {
        /* 创建目录 */
        $dir = DATA_DIR . '/' . $dir . '/';
        if ($img_name) {
            $img_name = $dir . $img_name; // 将图片定位到正确地址
        }
    }
    $realPath = ROOT_FW_PATH . $dir;
    /* 如果目标目录不存在，则创建它 */
    if (!file_exists($realPath)) {
        if (!make_dir($realPath)) {
            /* 创建目录失败 */
            $return_result['code'] = -1;
            $return_result['msg'] = '创建目录失败';
            return $return_result;
        }
    }
    if (empty($img_name)) {
        $img_name = $dir . sha1($pic) . '.' . $type;
    }
    $imgsize = round(strlen($pic) / 1048576 * 100) / 100;
    if ($imgsize > 10) {//10M
//        return self::setMsg('图片过大',1020);
        $return_result['code'] = 1020;
        $return_result['msg'] = '图片大小超过10M';
        return $return_result;
    }
    if (!empty($maxHeight) || !empty($maxWidth)) {
        $size = getimagesize('data://image/jpeg;base64,' . $pic);
        $_width = $size[0];
        $_height = $size[1];
        if ($maxWidth && ($_width > $maxWidth)) {
            $return_result['code'] = -2;
            $return_result['msg'] = '宽度超出了最高像素：' . intval($maxWidth);
            return $return_result;
        }
        if ($maxHeight && ($_height > $maxHeight)) {
            $return_result['code'] = -3;
            $return_result['msg'] = '宽度超出了最高像素：' . intval($maxHeight);
            return $return_result;
        }
    }
    ini_set('memory_limit', '100M');
    if (!file_exists(ROOT_FW_PATH . $img_name)) {
        $img = base64_decode($pic);
        if (empty($img)) {
            $return_result['code'] = -4;
            $return_result['msg'] = '不支持该图片';
            return $return_result;
        }
        file_put_contents(ROOT_FW_PATH . $img_name, $img);
        $im = imagecreatefromjpeg(ROOT_FW_PATH . $img_name);
        ImageJpeg($im, ROOT_FW_PATH . $img_name);
    }
    $return_result['code'] = 0;
    $return_result['msg'] = $img_name;
    return $return_result;
}

/**
 * 检查是否存在非法字符
 *
 * @access public
 * @param $str      需检查的字符串
 * @return bool     返回值
 */
function is_bad_chars($str)
{
    $bad_chars = array("\\", ' ', "'", '"', '/', '*', ',', '<', '>', "\r", "\t", "\n", '$', '(', ')', '%', '+', '?', ';', '^', '#', ':', '　', '`', '=', '|', '-');
    foreach ($bad_chars as $value) {
        if (strpos($str, $value) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * 检查邮箱格式是否合法
 *
 * @param $user_email
 * @return bool
 */
if (!function_exists("is_email")) {
    function is_email($user_email)
    {
        $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
        if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
            if (preg_match($chars, $user_email)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}


/**
 * 生成以时间戳的唯一订单号
 */
function order_out_trade_no()
{
    return date("YmdHis", time()) . base_convert(uniqid(), 16, 10);
}


/**
 * 小柜后台的退出登录
 */
function logout()
{
    /**
     * 删除login_pool表的session池
     */
    /**
     * @var $db cls_mysql
     */
    $db = $GLOBALS['db'];

    $db->delete([
        'user_id'   =>  $_SESSION['user']['id'],
        'from_platform' => $_SESSION['platform'],
        ], 'login_pool');


    /**
     * 删除session和cookie
     */
    // 重置会话中的所有变量
    $_SESSION = array();

    // 如果要清理的更彻底，那么同时删除会话 cookie
    // 注意：这样不但销毁了会话中的数据，还同时销毁了会话本身
    if (@ini_get("session.use_cookies")) {
        $params = @session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }


    //销毁会话
    session_destroy();
}

/**
 * 获取数组特定的项目
 * @param array $array
 * @param array $fields
 * @return array
 */
function array_only(array $array, array $fields)
{
    return array_intersect_key($array, array_flip($fields));
}

/**
 * 判断字母是不是大写
 * @param  string $str
 * @return bool
 */
function is_uppercase_letter($str){
    $str =ord($str);
    if($str>64&&$str<91){
        return true;
    }
    if($str>96&&$str<123){
        return false;
    }
    return false;
}

/**
 * 驼峰式命名转成下划线命名
 * @param $str
 * @param string $separator
 * @return string
 */
function snake_case($str, $separator='_')
{
    $value = preg_replace('/\s+/u', '', ucwords($str));
    return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$separator, $value));
}

/**
 * 将下划线分割变成驼峰式命名
 * @param $str
 * @param string $separator
 * @return string
 */
function camel_case($str, $separator='_')
{
    $value = ucwords(str_replace((array) $separator, ' ', $str));
    return lcfirst(str_replace(' ', '', $value));
}

/**
 * 替换字符串中的一部分
 * 例如替换身份证:
 *      474921199511256534(替换前) ->
 *      substr_repeat_replace('474921199511256534', 4, -4) ->
 *      4749**********6534(替换后)
 *
 * @param string $str 要替换的字符串
 * @param int $start 替换开始位置
 * @param null|int $length 替换长度
 * @param int $maxReplace  最大替换字符数
 * @param string $separator 要替换的字符串
 * @param string $encoding 字体编码
 * @return string 替换后的字符串
 */
function substr_repeat_replace($str, $start, $length=null, $maxReplace=null, $separator='*', $encoding = 'UTF-8')
{
    $maxReplace = ($maxReplace < 0) ? null : $maxReplace;
    //针对数字进行优化
    if(is_numeric($str)) {
        $replacement = str_repeat($separator, strlen(substr($str, $start, $length)));
        $replacement = empty($maxReplace) ? $replacement : substr($replacement, 0, $maxReplace);
        return substr_replace($str, $replacement, $start, $length);
    }

    $replacement = str_repeat($separator, mb_strlen(mb_substr($str, $start, $length, $encoding), $encoding));
    $replacement = empty($maxReplace) ? $replacement : mb_substr($replacement, 0, $maxReplace, $encoding);
    $begin = mb_substr($str, 0, $start, $encoding);
    if(empty($length)) {
        $end = '';
    }elseif($length > 0){
        $end = mb_substr($str, $start+(int)$length, null, $encoding);
    }else{
        $end = mb_substr($str, $length, null, $encoding);
    }
    return $begin . $replacement . $end;
}





