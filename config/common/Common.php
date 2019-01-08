<?php

trait Common
{
    /**
     * 获取根路径
     * @param null $path
     * @param bool $returnDomain
     * @return mixed|string
     */
    public function base_path($path=null, $returnDomain=false)
    {
        if(!$returnDomain){
            $return = $path ? ROOT_FW_PATH . '/' . ltrim($path, '/') : ROOT_FW_PATH;

            //尝试创建目录
            $dir = pathinfo($path, PATHINFO_EXTENSION) ? dirname($return) : $return;
            is_dir($dir) or @mkdir($dir, 0777, true);

            return $return;
        }

        $httpType = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $parse = parse_url($httpType . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI']);

        $domain = $parse['scheme'] . '://' . $parse['host'] .
            (isset($parse['port']) && (strpos($parse['host'], ':') === false) ? (':' . $parse['port']) : '') .
            dirname($parse['path']);
        $fullDomail = $path ? $domain . '/' . ltrim($path, '/') : $domain;
        return str_replace('\\', '/', $fullDomail);
    }


    /**
     * 缓存目录
     * @param null $path
     * @param bool $returnDomain
     * @return mixed|string
     */
    public function cache_path($path=null, $returnDomain=false)
    {
        return $returnDomain ?
            $this->base_path('static/cache/tc/' . $path, true) :
            $this->base_path('static/cache/tc/' . $path);
    }

    /**
     * 图片目录
     * @param null $path
     * @param bool $returnDomain
     * @return mixed|string
     */
    public function image_path($path=null, $returnDomain=false)
    {
        return $returnDomain ?
            $this->base_path('static/image/tc/' . $path, true) :
            $this->base_path('static/image/tc/' . $path);
    }

    /**
     * 模板目录
     * @param null $path
     * @param bool $returnDomain
     * @return mixed|string
     */
    public function template_path($path=null, $returnDomain=false)
    {
        return $returnDomain ?
            $this->base_path('static/template/tc/' . $path, true) :
            $this->base_path('static/template/tc/' . $path);
    }
     /*
     * 获取地区下面的所有子地区
     * @param int $id 地区id
     * @param string $select 查询内容，默认查询所有
     * @return mixed
     */
    function getRegionList($id=0,$select='*'){
        $table = $GLOBALS['ecs']->table('region');
        $db = $GLOBALS['db'];
        $sql='select '.$select.' from '.$table.' where parent_id='.$id;
        return $db->getAll($sql);
    }


    public function getIp() {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return ($ip);
    }

    /**
     * 根据ip地址返回定位地址
     * @param string $ip
     * @return array|bool|mixed
     */
    public function getCity($ip = '')
    {
        if($ip == ''){
            $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
            $ip=json_decode(file_get_contents($url),true);
            $data = $ip;
        }else{
            $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
            $ip=json_decode(file_get_contents($url));
            if((string)$ip->code=='1'){
                return false;
            }
            $data = (array)$ip->data;
        }
        return $data;
    }

    /**
     * 获取地区
     * @return array|bool
     */
    public function toArea()
    {
        $table = $GLOBALS['ecs']->table('area');
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $select = implode(',', array(
            'id',
            'name'
        ));
        $sql = "select {$select} from {$table}";
        return $db->getAll($sql);
    }

    /**
     * 获取地区
     * @param int $areaId       地区id
     * @param array $select
     * @return array|bool
     */
    public function getArea($areaId, $select=['*'])
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $table = $GLOBALS['ecs']->table('area');
        $select = implode(',', $select);
        $sql = "select {$select} from {$table} where id={$areaId}";
        return $db->getRow($sql);
    }

    /**
     * 获取省
     * @param int $areaId       地区id
     * @return array|bool
     */
    public function toProvince($areaId)
    {
        $areaTable = $GLOBALS['ecs']->table('area');
        $table = $GLOBALS['ecs']->table('region');
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $select = implode(',', array(
            'province_id'
        ));
        $sql = "select {$select} from {$areaTable} where id={$areaId}";
        $provinceIds = $db->getOne($sql);
        $provinceIds = implode(',', json_decode($provinceIds, true));
        $select = implode(',', array(
            'region_id',
            'region_name'
        ));
        $where = "region_type=1 and region_id in ($provinceIds)";

        $sql = "select {$select} from {$table} where {$where}";
        return $db->getAll($sql);
    }

    /**
     * 获取市
     * @param int $provinceId   省id
     * @return array|bool
     */
    public function toCity($provinceId)
    {
        $table = $GLOBALS['ecs']->table('region');
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $select = implode(',', array(
            'region_id',
            'region_name'
        ));
        $where = 'region_type = 2 and parent_id = ' . $provinceId;

        $sql = "select {$select} from {$table} where {$where}";
        return $db->getAll($sql);
    }

    /**
     * 获取乡
     * @param int $cityId       市id
     * @return array|bool
     */
    public function toCounty($cityId)
    {
        $table = $GLOBALS['ecs']->table('region');
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $select = implode(',', array(
            'region_id',
            'region_name'
        ));
        $where = 'region_type = 3 and parent_id = ' . $cityId;

        $sql = "select {$select} from {$table} where {$where}";
        return $db->getAll($sql);
    }

    /**
     * 根据id获取地区表信息
     * @param string $id           region表id
     * @param string $select       获取的列
     * @return string
     */
    public function byIdToRegion($id, $select = 'region_name')
    {
        $table = $GLOBALS['ecs']->table('region');
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $where = "region_id={$id}";

        $sql = "select {$select} from {$table} where {$where} limit 1";
        $result = $db->getRow($sql);
        return (is_array($result)) ? current($result) : $result;
    }

    /**
     * 根据地区名获取地区id(仅支持region表, 不支持area表)
     * @param string $regionName       地区名
     * @return bool|string             地区id
     */
    public function byRegionNameToId($regionName)
    {
        $table = $GLOBALS['ecs']->table('region');
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $where = "region_name='{$regionName}'";
        $select = implode(',', array(
            'region_id'
        ));
        $sql = "select {$select} from {$table} where {$where} limit 1";
        return $db->getOne($sql);
    }

    /**
     * 流水号
     * @param string $table    存放流水号的表名
     * @param string $field    存放流水号的字段名
     * @param string $prefix   流水号前缀
     * @param int $strpad      数字位位数
     * @param string $pk       排序的键
     * @return string          流水号
     */
    public function water($table, $field, $prefix, $strpad=8, $pk='id')
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $table = $GLOBALS['ecs']->table($table);
        $sql = "select {$field} from {$table} where {$field} IS NOT NULL AND {$field} != '' order by {$pk} desc limit 1";
        $result = $db->getOne($sql);

        $oldWater = $result ? str_replace($prefix, '', $result) : 0;
        return $prefix . str_pad($oldWater + 1, $strpad, '0', STR_PAD_LEFT);
    }

    /**
     * 添加信息到用户表, 并发送设置密码的邮件
     * @param int $email 邮件
     * @param int $phone
     * @param int $type 用户类型(0:超级管理员,1:资方,2:服务商,3:商家,4:业务员,5:平台操作员,6:资方操作员,7:服务商操作员,8:商家操作员)
     * @param string $relationId
     * @param string $charset
     * @return mixed
     */
    public function toUser($email, $phone, $type, $relationId='', $charset='UTF-8')
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $token = $this->token();
        $data = [
            'email' => $email,
            'type' => $type,
            'relation_id' => $relationId,
            'phone' => $phone,
            'token' => $token,
            'status' => 1,
        ];

        $title = '时间小柜邮箱激活';
        $url = FRONT_DOMAIN . "/setPsw?token={$token}";
        $content = file_get_contents(ROOT_FW_PATH.'static/template/tc/send_email_template.html');
ENV_TYPE == 'test' && $email = '254659633@qq.com';
        //填充html内容
        $content = sprintf($content, $charset, $email, $url, $url);
        //发送设置密码的邮件
        $this->sendMail($email, $title, $content, $charset);

        return $db->add($data, 'user');
    }

    /**
     * 判断用户是否注册
     * @param $phone
     * @param $email
     * @param array $where
     * @return bool
     */
    public function hasRegister($phone, $email, $where=[1])
    {
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];
        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];
        $where = implode(' and ', $where);

        $sql = "select count(*) from {$ecs->table('user')} where phone='{$phone}' and email='{$email}' and {$where}";
        return !! $db->getOne($sql);
    }

    /**
     * 修改用户信息(更新用户邮箱)
     * @param string $email
     * @param int $phone
     * @param $relationId
     * @return bool
     */
    public function updateUser($email, $phone, $relationId)
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $update = [
            'email' => $email,
            'phone' => $phone,
        ];
        $where = [
            'id' => $relationId
        ];
        $table = 'user';
        return $db->edit($update, $where, $table);
    }

    /**
     * 获取随机token
     * @return string
     */
    protected function token()
    {
        return md5('shijianxiaogui'.time().rand(100,999));
    }


    /**
     * 获取指定服务商信息
     * @param int $id           服务商id
     * @param array $select     获取字段
     * @return bool
     */
    public function toService($id, $select=['*'])
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $table = $GLOBALS['ecs']->table('service_provider');
        $select = implode(',', $select);
        $sql = "select {$select} from {$table} where id={$id}";
        return $db->getRow($sql);
    }
    /**
     * 获取指定供应商信息
     * @param int $id           商家id
     * @param array $select     获取字段
     * @return bool
     */
    public function toDeviceService($id, $select=['*'])
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $table = $GLOBALS['ecs']->table('device_supplier');
        $select = implode(',', $select);
        $sql = "select {$select} from {$table} where ds_id={$id}";
        return $db->getRow($sql);
    }
    /**
     * 获取指定商家信息
     * @param int $id           商家id
     * @param array $select     获取字段
     * @return bool
     */
    public function toBusiness($id, $select=['*'])
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $table = $GLOBALS['ecs']->table('business');
        $select = implode(',', $select);
        $sql = "select {$select} from {$table} where id={$id}";
        return $db->getRow($sql);
    }

    /**
     * 获取服务商信息
     * @param array $select
     * @return bool
     */
    public function getServiceList($select=['*'])
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $table = $GLOBALS['ecs']->table('service_provider');
        $select = implode(',', $select);
        $sql = "select {$select} from {$table}";
        return $db->getAll($sql);
    }

    /**
     * 获取设备供应商集合
     * @param array $select
     * @return bool
     */
    public function getDeviceSupplier($select=['*'])
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $table = $GLOBALS['ecs']->table('device_supplier');
        $select = implode(',', $select);
        $sql = "select {$select} from {$table}";
        return $db->getAll($sql);
    }

    /**
     * 获取商家集合
     * @param array $select
     * @return bool
     */
    public function getBusiness($select=['*'])
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $table = $GLOBALS['ecs']->table('business');
        $select = implode(',', $select);
        $sql = "select {$select} from {$table}";
        return $db->getAll($sql);
    }

    /**
     * 获取设备归属集合
     * @param array $select
     * @return bool
     */
    public function getDrr($select=['*'])
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $table = $GLOBALS['ecs']->table('device_role_relationship');
        $select = implode(',', $select);
        $sql = "select {$select} from {$table}";
        return $db->getAll($sql);
    }

    /**
     * 获取商家关联设备的设备号
     * @param $businessId
     * @return array
     */
    public function getBusinessDeviceNo($businessId)
    {
        $db = $GLOBALS['db'];
        $sql = "SELECT device_id FROM tc_device_role_relationship WHERE shangjia_id = {$businessId}";
        $deviceIdList = $db->getAll($sql);
        $deviceNoList = array();
        $index = 0;
        foreach ($deviceIdList as $key => $value) {
            $sql = 'SELECT device_no FROM tc_device WHERE id = '.$deviceIdList[$key]['device_id'];
            $deviceNoList[$index++] = $db->getOne($sql);
        }
        $result = array(
            'deviceIdList' => $deviceIdList,
            'deviceNoList' => $deviceNoList
        );
        return $result;
    }

    /**
     * 获取服务商关联设备的设备号
     * @param $serviceId
     * @return array
     */
    public function getServiceDeviceNo($serviceId)
    {
        $db = $GLOBALS['db'];
        $sql = "SELECT device_id FROM tc_device_role_relationship WHERE fuwushang_id = {$serviceId}";
        $deviceIdList = $db->getAll($sql);
        $deviceNoList = array();
        $index = 0;
        foreach ($deviceIdList as $key => $value) {
            $sql = 'SELECT device_no FROM tc_device WHERE id = '.$deviceIdList[$key]['device_id'];
            $deviceNoList[$index++] = $db->getOne($sql);
        }
        $result = array(
            'deviceIdList' => $deviceIdList,
            'deviceNoList' => $deviceNoList
        );
        return $result;
    }

    public function getDeviceNoInUse()
    {
        $db = $GLOBALS['db'];
        $sql = 'SELECT device_no FROM tc_device WHERE  device_machine_code!=""';
        $deviceNoList = $db->getAll($sql);
        var_dump($deviceNoList);die();
    }

    /**
     * 获取指定设备归属
     * @param int $id
     * @param string $select
     * @return bool
     */
    function getDevicerr($id=0,$select='*'){
        $table = $GLOBALS['ecs']->table('device_role_relationship');
        $db = $GLOBALS['db'];
        $sql='select '.$select.' from '.$table.' where device_id='.$id;
        return $db->getRow($sql);
    }
    /**
     * 事务处理
     * @param callable|array $callback    回调函数
     * @param null $message         接受抛出的异常
     * @param array $args           传递给回调函数的参数
     * @return bool
     */
    public function transaction($callback, &$message=null, $args=[])
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        $db->pdo->beginTransaction();
        $message = new stdClass();

        try{
            call_user_func_array($callback, $args);

            $message->code = null;
            $message->message = null;
            $message->data = [];
        }catch (Exception $e){
            $result = (object) json_decode($e->getMessage());
            if(empty((array) $result)) {
                $result->code = $e->getCode();
                $result->message = $e->getMessage();
                $result->data = [];
            }
            $message->code = $result->code;
            $message->message = $result->message;
            $message->data = $result->data;

            $db->pdo->rollBack();
            return false;
        }

        $db->pdo->commit();
        return true;
    }



    /**
     * 抛出异常
     * @param string $code 错误码
     * @param string $message 错误消息
     * @param array $data   附加参数
     * @throws Exception
     */
    public function raise($code='', $message='', $data=[])
    {
        throw new Exception(json_encode(compact(['message', 'code', 'data'])), 0);
    }

    /**
     * 输出抛出的异常
     * @param $message
     */
    public function raiseErr($message)
    {
        $code = $message->code;
        $message = $message->message;
        $data = isset($message->data) ? $message->data : [];

        cls_output::out($code, $message, $data);
    }



    /**
     * 过滤数组或对象
     * @param array|object $filterData  操作的数组/对象
     * @param callable|null $callback   回调函数
     * @return array|object
     */
    public function _filter($filterData, callable $callback=null)
    {
        $array = (array) $filterData;

        if(is_null($callback)) {
            $result = array_filter($array);
        } else if(defined('ARRAY_FILTER_USE_BOTH')) {
            $result = array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
        } else {
            $result = array();
            foreach ($array as $key=>$item) {
                if( call_user_func($callback, $item, $key) ){
                    $result = array_merge($result, array($key => $item));
                }
            }
        }

        return is_object($filterData) ? (object) $result : $result;
    }


    /**
     * 对数组中的每个成员执行回调
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public function map(array $array, callable $callback)
    {
        $arguments = array_slice(func_get_args(), 2);
        foreach ($array as &$item)
        {
            $item = call_user_func_array($callback, array_merge([$item], $arguments));
        }
        return $array;
    }

    /**
     * 检查用户是否登录
     * @return bool
     */
    public function hasLogin()
    {
        return isset($_SESSION['user']) && (count($_SESSION['user']) > 2);
    }

    /**
     * 登录后的用户信息
     * @param string $key   用户信息
     * @return  string | array
     */
    public function authUser($key=null)
    {
        if((!isset($_SESSION['user'])) || count($_SESSION['user'])<2) {
            cls_output::out('E120000', '获取用户信息失败');
        }
        return is_null($key) ? $_SESSION['user'] : $_SESSION['user'][$key];
    }

    /**
     * 获取关联表id
     * @return array
     */
    public function relationId()
    {
        $types = $this->userType();

        //(1为小柜后台, 2为商家app, 3为普通用户app)
        $platform = $this->loginPlatform();
//print_r($_SESSION);exit;
        //普通用户app(普通用户登录)
        if($platform == 3 && $this->isOrdUser()) {
            return [
                11 => $this->getOrduserId()
            ];
        }
        //小柜后台
        if($platform == 1 || $platform == 2) {
            if(count($types) == 1) {
                if($this->isPlatform()) {
                    return [0=>0];
                }
                if($this->isService()) {
                    return [
                        2 => $this->getServiceId(),
                    ];
                }
                if($this->isVirtualService()) {
                    return [
                        13 => $this->getServiceId(),
                    ];
                }
                if($this->isVirtualServiceWithNoComplete()) {
                    return [
                        15 => $this->getServiceId()
                    ];
                }
                if ($this->isBusiness()) {
                    return [
                        3 => $this->getBusinessId(),
                    ];
                }
                if($this->isVirtualBusiness()) {
                    return [
                        14 => $this->getBusinessId(),
                    ];
                }
                if($this->isOrdUser()) {
                    return [
                        11=>$this->getOrduserId()
                    ];
                }
            }else{
                $return = [];

                if($this->isPlatform()) {
                    $return[0] = 0;
                }
                if($this->isService()) {
                    $return[2] = $this->getServiceId();
                }
                if($this->isVirtualService()) {
                    $return[13] = $this->getServiceId();
                }
                if($this->isVirtualServiceWithNoComplete()) {
                    $return[15] = $this->getServiceId();
                }
                if ($this->isBusiness()) {
                    $return[3] = $this->getBusinessId();
                }
                if($this->isVirtualBusiness()) {
                    $return[14] = $this->getBusinessId();
                }
                if($this->isOrdUser()) {
                    $return[11] = $this->getOrduserId();
                }

                return $return;
            }
        }
        return [];
    }

    /**
     * 关联服务商id
     * @return bool | int
     */
    public function relationServiceId()
    {
        $relation = $this->relationId();
        if(isset($relation[2])) {
            return $relation[2];
        }
        return false;
    }

    /**
     * 关联虚拟服务商id
     * @return bool
     */
    public function relationVirtualServiceId()
    {
        $relation = $this->relationId();
        if(isset($relation[13])) {
            return $relation[13];
        }
        return false;
    }

    /**
     * 关联未审核通道服务商id
     * @return bool
     */
    public function relationVirtualServiceIdWithNoComplete()
    {
        $relation = $this->relationId();
        if(isset($relation[15])) {
            return $relation[15];
        }
        return false;
    }

    /**
     * 关联未审核通道服务商id + 服务商id
     */
    public function relationBetweenVirtualServiceId()
    {
        if($this->isVirtualService()) {
            return $this->relationVirtualServiceId();
        }elseif($this->isVirtualServiceWithNoComplete()) {
            return $this->relationVirtualServiceIdWithNoComplete();
        }
        return false;
    }

    /**
     * 关联商家id
     * @return bool
     */
    public function relationBusinessId()
    {
        $relation = $this->relationId();
        if(isset($relation[3])) {
            return $relation[3];
        }
        return false;
    }

    /**
     * 关联虚拟服务商id
     * @return bool
     */
    public function relationVirtualBusinessId()
    {
        $relation = $this->relationId();
        if(isset($relation[14])) {
            return $relation[14];
        }
        return false;
    }

    /**
     * 关联普通用户id
     * @return bool
     */
    public function relationOrduserId()
    {
        $relation = $this->relationId();
//var_dump($relation);exit;
        if(isset($relation[11])) {
            return $relation[11];
        }
        return false;
    }

    /**
     * 获取普通用户id
     * @return mixed
     */
    private function getOrduserId()
    {
        $sql = "select id from {$GLOBALS['ecs']->table('orduser')} where user_id={$this->userId()}";
        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 获取服务商id
     * @return mixed
     */
    private function getServiceId()
    {
        $sql = "select id from {$GLOBALS['ecs']->table('service_provider')} where user_id={$this->userId()}";
        return $GLOBALS['db']->getOne($sql);
    }

    /**
     * 获取商家id
     * @return mixed
     */
    private function getBusinessId()
    {
        $sql = "select id from {$GLOBALS['ecs']->table('business')} where user_id={$this->userId()}";
        return $GLOBALS['db']->getOne($sql);
    }


    /**
     * 获取当前登录的用户的id
     * @return int
     */
    public function userId()
    {
        return $this->authUser('id');
    }

    /**
     * 获取当前登录用户的类型
     * @return array|string
     */
    public function userType()
    {
        $authUser = $this->authUser();
        return $authUser['roles'];
    }

    /**
     * 判断当前登录的账号是否平台
     * @return bool
     */
    public function isPlatform()
    {
        $userType = $this->userType();
        return in_array('0', $userType, true) || in_array(0, $userType, true);
    }

    /**
     * 当前登录的用户是否普通用户
     * @return bool
     */
    public function isOrdUser()
    {
        $userType = $this->userType();
        return in_array(11, $userType);
    }

    /**
     * 当前登录的用户是否是服务商
     * @return bool
     */
    public function isService()
    {
        $userType = $this->userType();
        return in_array(2, $userType);
    }


    /**
     * 是否虚拟服务商
     * @return bool
     */
    public function isVirtualService()
    {
        $userType = $this->userType();
        return in_array(13, $userType);
    }

    /**
     * 是否虚拟服务商或未审核的虚拟服务商
     * @return bool
     */
    public function isBetweenVirtualService()
    {
        return $this->isVirtualService() || $this->isVirtualServiceWithNoComplete();
    }

    /**
     * 是否未审核的虚拟服务商
     * @return bool
     */
    public function isVirtualServiceWithNoComplete()
    {
        $userType = $this->userType();
        return in_array(15, $userType);
    }

    /**
     * 是否虚拟服务商
     * @return bool
     */
    public function isVirtualBusiness()
    {
        $userType = $this->userType();
        return in_array(14, $userType);
    }


    /**
     * 当前登录的用户是否是商家
     * @return bool
     */
    public function isBusiness()
    {
        $userType = $this->userType();
        return in_array(3, $userType);
    }

    /**
     * 当前登录的客户端类型
     * 1为小柜后台, 2为商家app, 3为普通用户app
     * @return mixed
     */
    public function loginPlatform()
    {
        return $_SESSION['platform'];
    }

    /**
     * 获取当前登录的用户的关联表id(已暂停使用)
     * @return int
     */
    public function relationUserId_dev()
    {
        $platform = $this->loginPlatform();
        switch ($platform) {
            case 1:
            case 2:
                $userType = $this->userType();
                if(count($userType) == 1){
                    $table = $this->toTableName($userType);
                }else{
                    return false;
                }
                break;
            case 3:
                return $this->authUser('id');
                break;
        }
    }



    /**
     * object 转 array
     * @param $obj
     * @return mixed
     */
    function object_to_array($obj){
        $_arr=is_object($obj)?get_object_vars($obj):$obj;
        foreach($_arr as $key=>$val){
            $val=(is_array($val))||is_object($val)?object_to_array($val):$val;
            $arr[$key]=$val;
        }
        return $arr;
    }

    /**
     * 用户密码加密规则
     * @param string $password     用户密码
     * @return string              加密后的密码
     */
    public function _password($password)
    {
        return md5(md5('shi##jian1%&xiaogui**.' . $password . '.0123@@'));
    }


    /**
     * 检查邮箱是否已经注册
     * @param $email
     * @return bool
     */
    public function createEmailExists($email)
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        /**
         * @var $ecs ECS
         */
        $ecs = $GLOBALS['ecs'];
        $table = $ecs->table('user');

        $where = sprintf('`email` = "%s"', $email);
        $sql = "select `email` from {$table} where {$where}";

        return !! $db->getOne($sql);
    }

    /**
     * 检查手机号码是否已经注册
     * @param $phone
     * @return bool
     */
    public function createPhoneExists($phone)
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        /**
         * @var $ecs ECS
         */
        $ecs = $GLOBALS['ecs'];
        $table = $ecs->table('user');

        $where = sprintf('`phone` = "%s"', $phone);
        $sql = "select `phone` from {$table} where {$where}";

        return !! $db->getOne($sql);
    }



    /**
     * 检查邮箱是否已经注册
     * @param $email
     * @param $relationId
     * @param $type
     * @return bool
     */
    public function emailExists($email, $relationId, $type)
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        /**
         * @var $ecs ECS
         */
        $ecs = $GLOBALS['ecs'];
        $table = $ecs->table('user');

        $where = '(`email` = "%s" and relation_id != %s and type = %s)';
        $where = sprintf($where, $email, $relationId, $type, $email, $type);

        $sql = "select `email` from {$table} where {$where}";

        return !! $db->getOne($sql);
    }

    /**
     * 检查手机号码是否已经注册
     * @param $phone
     * @param $relationId
     * @param $type
     * @return bool
     */
    public function phoneExists($phone, $relationId, $type)
    {
        /**
         * @var $db cls_mysql
         */
        $db = $GLOBALS['db'];
        /**
         * @var $ecs ECS
         */
        $ecs = $GLOBALS['ecs'];
        $table = $ecs->table('user');

        $where = '(`phone` = "%s" and relation_id != %s and type = %s)';
        $where = sprintf($where, $phone, $relationId, $type, $phone, $type);

        $sql = "select `phone` from {$table} where {$where}";
        return !! $db->getOne($sql);
    }
    /**
     * 获取角色名称
     * @param $id
     * @return bool
     */
    public function get_role_name($id)
    {
        switch ($id) {
            case "0":
                return "平台";
                break;
            case "1":
                return "资方";
                break;
            case "2":
                return "服务商";
                break;
            case "3":
                return "商家";
                break;
            case "4":
                return "平台业务员";
                break;
            case "5":
                return "平台操作员";
                break;
            case "6":
                return "资方操作员";
                break;
            case "7":
                return "服务商操作员";
                break;
            case "8":
                return "商家操作员";
                break;
            case "9":
                return "服务商操作员";
                break;
            case "10":
                return "商家业务员";
            
            default:
                return "";
        }
    }


    /**
     * 判断是否平台用户
     * @return bool
     */
    public function isAdmin()
    {
        $type = $this->authUser('type');
        return $type === 0 || $type === '0';
    }


    /**
     * 关联权限管理的角色
     *
     * @param int $userId           user表的id
     * @param int $type             用户类型
     * @return bool|int|string
     */
    public function toRoleUser($userId, $type)
    {
        //根据用户类型获取角色id
        require_once ROOT_FW_PATH . '/application/source/tc/auth/auth.php';
        $auth = new auth();
        $roleId = $auth->byUserTypeToRoleId($type);

        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];

        $insert = [
            'role_id' => $roleId,
            'user_id' => $userId,
        ];
        return $db->add($insert, 'role_user');
    }


    /**
     * 获取字段
     *
     * @param $array
     * @param $key
     * @param null $default
     * @return null
     */
    public function get($array, $key, $default=null)
    {
        if(array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }


    /**
     * 根据用户的类型获取关联表名称
     * @param $type
     * @return bool|mixed
     */
    public function toTableName($type)
    {
        $relation = [
            //平台
            0 => 'user',
            //资方
            1 => 'management',
            //服务商
            2 => 'service_provider',
            //商家
            3 => 'business',
            //平台业务员
            4 =>  'salesman',
            //平台操作员
            5 =>  'user',
            //资方操作员
            6 => 'user',
            //服务商操作员
            7 => 'user',
            //商家操作员
            8 => 'user',
            //服务商业务员
            9 => 'salesman',
            //商家业务员
            10 => 'salesman',
            //普通用户
            11 => 'orduser',
            //机构
            12 => 'user',
            //通道服务商
            //通道商家
        ];

        if(! isset($relation[$type])) {
            return false;
        }

        return $relation[$type];
    }

    /**
     * 根据user表的id获取关联表的信息(已废弃)
     * @param $userId
     * @param array $select
     * @return bool|mixed
     */
    public function toRelation($userId, $select=['*'])
    {
        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];
        $select = (array) $select;

        //获取用户的类型和relation_id
        $sql = "select `type`,`relation_id` from {$ecs->table('user')} where id={$userId}";
        list($userType, $relationId) = array_values($db->getRow($sql));

        //平台用户没有relation_id
        if($userType == 0) {
            $relationId = $userId;
        }

        //获取关联表的信息
        $select = implode(',', $select);
        $table = $ecs->table($this->toTableName($userType));
        $sql = "select {$select} from {$table} where id = {$relationId}";

        $res = $db->getRow($sql);

        return count($res) == 1 ? current($res) : $res;
    }

    /**
     * 根据平台号获取角色的名字
     * @return string
     */
    public function toRelationName()
    {
        if(! isset($_SESSION['platform'])) {
            return '';
        }

        $platform = $_SESSION['platform'];

        if($platform == -1) {
            return '';
        }

        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];
        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];

        $userId = $this->userId();

        if($platform == 1 && $this->isAdmin()) {
            //user表的name
            $sql = "select name from {$ecs->table('user')} where id='{$userId}'";
            return $db->getOne($sql) ? : '';
        }

        //1:小柜后台, 2:商家app, 3:普通用户
        if($platform == 1 || $platform == 2) {
            if ($this->isService() || $this->isBetweenVirtualService()) {
                //service_provider表的name
                $sql = "select name,contact_name from {$ecs->table('service_provider')} where user_id='{$userId}'";
                $name = $db->getRow($sql);
                $name = current(array_filter($name));
                return $name ? : '';
            }elseif ($this->isBusiness() || $this->isVirtualBusiness()) {
                //business表的name
                $sql = "select name,contact_name from {$ecs->table('business')} where user_id='{$userId}'";
                $name = $db->getRow($sql);
                $name = current(array_filter($name));
                return $name ? : '';
            }
        }elseif ($platform == 3) {
            if($this->isOrdUser()) {
                $sql = "select name from {$ecs->table('orduser')} where user_id='{$userId}'";
                return $db->getOne($sql) ? : '';
            }
        }
        return '';
    }

    /**
     * 新增消息
     * @param $title
     * @param $content
     * @param $userId
     * @param int $type
     * @param int $level
     * @return bool|int|string
     */
    protected function storeToMessage($title, $content, $userId, $type=1, $level=1)
    {
        $fromId = $this->hasLogin() ? $this->userId() : 0;

        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];
        $insertMessageData = array(
            'create_time' => time(),
            'title' => $title,
            'type' => $type,
            'number' => $this->water('message', 'number', 'ME'),
            'level' => $level,
            'content' => $content,
            'from_id' => $fromId,
            'to_id' => $userId,
        );

        return $db->add($insertMessageData, 'message');
    }


    /**
     * 开启事务
     * 使用方法:
     *      //开启事务
     *      $this->beginTransaction();
     *      //提交事务
     *      $this->succ();
     *      //或者事务回滚
     *      $this->err();
     *
     * @return bool
     */
    protected function beginTransaction()
    {
        $result = true;
        if((! defined('HAS_BEGIN_TRANSACTION')) || HAS_BEGIN_TRANSACTION === false) {
            /** @var cls_mysql $db */
            $db = $GLOBALS['db'];
            $result = $db->pdo->beginTransaction();
            //定义标志常量
            define('HAS_BEGIN_TRANSACTION', (bool) $result);
        }
        return $result;
    }


    /**
     * 根据时间获取where条件
     * @param $begin
     * @param $end
     * @param string $fiend
     * @param string $prefix
     * @return string
     */
    public function timeToWhere($begin, $end, $fiend='ctime', $prefix='')
    {
        is_numeric($begin) or ($begin = strtotime($begin));
        is_numeric($end) or ($end = strtotime($end));
        empty($prefix) or ($prefix = "`{$prefix}`.");
        $fiend = "`{$fiend}`";

        $where = '';
        if($begin && $end) {
            if($begin == $end) {
                $where .= " {$prefix}{$fiend} = '{$begin}' ";
            }else{
                $where .= " {$prefix}{$fiend} between '{$begin}' and '{$end}' ";
            }
        }elseif($begin) {
            $where .= " {$prefix}{$fiend} > {$begin} ";
        }elseif($end) {
            $where .= " {$prefix}{$fiend} < {$begin} ";
        }

        return $where;
    }

    /**
     * 过滤 $this->data 中的字段
     * @param $fiendName
     * @param null $default
     * @return null
     */
    public function filterData($fiendName, $default=null)
    {
        if(isset($this->data->{$fiendName})) {
            if(is_numeric($this->data->{$fiendName}) || (!empty($this->data->{$fiendName}))) {
                return $this->data->{$fiendName};
            }
        }
        return $default;
    }

    /**
     * 获取网络推广商链接
     * @param $deviceNo
     * @return string
     */
    public function getVirtualLink($deviceNo)
    {
        return sprintf('http://device.shijianxiaogui.com/index?deviceId=%s', $deviceNo);
    }

    /**
     * 生成二维码并保持到文件夹里
     * @param $url
     * @return string   图片相对路径
     */
    public function getVirtualQrCode($url)
    {
        require ROOT_FW_PATH . 'application/function/includes/phpqrcode/phpqrcode.php';

        $filename = 'qrcode/' . date('Ymd') . '/' . time() . '.png';
        $outfile = $this->image_path($filename);
        //生成图片并保持到文件夹里
        QRcode::png($url, $outfile, QR_ECLEVEL_L, 10, 0);

        return 'static/image/tc/' . $filename;
    }

    /**
     * 根据设备id获取机器码
     * @param $id
     * @return bool|string
     */
    public function byDeviceIdToCode($id)
    {
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];
        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];

        $sql = "select device_no from {$ecs->table('device')} where id='{$id}'";
        return $db->getOne($sql);
    }


    /**
     *  新增默认首台虚拟设备
     * @data:2018-12-14 下午6:05:06
     * @author:MRT
     * @param:
     * @param string $business_id
     * @return  :
     */
    public function addVirtualDevice($service_id,$business_id=''){
        $this->device = $this->load_table('tc/device');
        $apply_log = $this->device;              
        
            $device_data = array(
                'createdatetime' => time(),
                'updatetime' => time(),
                'device_type' => "100", // 虚拟设备类型表id
                'device_supplier' => "100", // 虚拟供应商表id
                'device_status' => "1",        
                'is_virtual' => "1",
            );
        
            // 添加设备信息
            $result = $apply_log->simple_add($device_data);
            // 新设备号 100001开始 顺序数字
            $device_no = 100000 + $result;
        
            // 生成设备号&机器码
            $apply_log->simple_edit(array(
                "device_no" => $device_no,
                "device_machine_code" => $device_no
            ), array(
                "id" => $result
            ));
        
            // 添加设备关联信息
            $this->add_drr(array(
                "device_id" => $result,
                "fuwushang_id" => $service_id,
                "shangjia_id" => $business_id
            ));
        
            $this->device->chose_table('device_registered_log');
            $apply_log = $this->device;
        
            $device_data = array(
                'create_time' => time(),
                'device_machine_code' => $device_no,
                'device_no' => $device_no,
                'state' => "1",
                'ip' => $_SERVER['REMOTE_ADDR']
            );
        
            // 过滤空字段
            $device_data2 = array();
            foreach ($device_data as $k => $v) {
                if ($v) {
                    $device_data2["$k"] = $v;
                }
            }
            $result2 = $apply_log->simple_add($device_data2);
            
            return $result;
        
        
//             // 添加设备转移记录
//             $device_transfer = array(
//                 'device_id' => $result,
//                 'create_time' => time(),
//                 'transfer_from_role_type' => '0',
//                 'transfer_from_role_id' => "0",
//                 'transfer_to_role_type' => "2",
//                 'transfer_to_role_id' => $service_id,
//                 'transfer_state' => "3",
//                 'receive_confirmation' => "1",
//                 'logistics' => isset($this->data->logistics) ? $this->data->logistics : "",
//                 'logistics_order' => isset($this->data->logistics_order) ? $this->data->logistics_order : "",
//                 'remake' => isset($this->data->remake) ? $this->data->remake : ""
//             );
//             $this->add_transfer_log($device_transfer);
        
  

    }

    /**
     * 设备归属添加关联
     * @param $field
     * @return bool
     */
    public function add_drr($field)
    {
        $db = $GLOBALS['db'];
        $result = $db->add($field, "device_role_relationship");
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }


    /**
     * 通道服务商/通道商家 获取收款账号信息
     * @return bool|mixed
     */
    public function getBankInfo()
    {
        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];

        $userType = '';
        $foreignKey = '';
        //判断是否通道服务商(同时是通道服务商和通道商家, 获取通道服务商即可)
        if($this->isVirtualService()) {
            $userType = 13;
            $foreignKey = $this->relationVirtualServiceId();
        //判断是否通道商家
        }elseif($this->isVirtualBusiness()) {
            $userType = 14;
            $foreignKey = $this->relationVirtualBusinessId();
        } else {
            return false;
        }

        $sql = "select card_num,bank_name,phone,name,idcard from {$ecs->table('bank')} where is_default=1 and foreign_key='{$foreignKey}' and user_type='{$userType}'";
        return $db->getRow($sql);
    }


    /**
     * 根据角色获取佣金比例
     * @param $roleType
     * @return bool|string
     */
    public function getCommissionRatio($roleType)
    {
        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];

        $where = implode(' and ', [
            "role_type='{$roleType}'",
//            "is_default=1",
            "status=1",
        ]);
        $sql = "select commission_ratio from {$ecs->table('virtual_policy')} where {$where} limit 1";
        return $db->getOne($sql);
    }

    /**
     * 根据角色获取政策比例
     * @param $roleType
     * @return bool|string
     */
    public function getRatio($roleType)
    {
        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];

        $where = implode(' and ', [
            "role_type='{$roleType}'",
//            "is_default=1",
            "status=1",
        ]);
        $sql = "select * from {$ecs->table('virtual_policy')} where {$where} limit 1";
        return $db->getRow($sql);
    }

    /**
     * 获取分润比例
     * @param $serviceId
     * @return bool|mixed
     */
    public function getServiceRatio($serviceId)
    {
        if(empty($serviceId)) {
            return false;
        }

        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];

        $select = implode(',', [
            //贷款分润比例
            'loan_ratio',
            //信用卡分润比例
            'creditcard_ratio',
            //保险分润比例
            'insurance_ratio',
            //征信分润比例
            'credit_ratio',
            //佣金分成比例
            'commission_ratio',
        ]);
        $sql = "select {$select} from {$ecs->table('service_provider')} as service where id='{$serviceId}'";
        return $db->getRow($sql);
    }

    /**
     * 获取分润比例
     * @param $business
     * @return bool|mixed
     */
    public function getBusinessRatio($business)
    {
        if(empty($business)) {
            return false;
        }

        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];

        $select = implode(',', [
            //贷款分润比例
            'loan_ratio',
            //信用卡分润比例
            'creditcard_ratio',
            //保险分润比例
            'insurance_ratio',
            //征信分润比例
            'credit_ratio',
            //佣金分成比例
            'commission_ratio',
        ]);
        $sql = "select {$select} from {$ecs->table('business')} as business where id='{$business}'";
        return $db->getRow($sql);
    }


    /**
     * 新增普通用户
     *
     * @param $phone
     * @param $deviceId
     * @return bool|int|string
     */
    protected function addOrdUser($phone, $deviceId)
    {
        /** @var ECS $ecs */
        $ecs = $GLOBALS['ecs'];
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];

        //判断普通用户是否已经存在
        $sql = "select count(*) from {$ecs->table('orduser')} where phone='{$phone}'";
        if($db->getOne($sql)) {
            return false;
        }

        //检查用户是否存在
        $sql = "select id from {$ecs->table('user')} where phone='{$phone}' limit 1";
        $userId = $db->getOne($sql);
        if(! $userId) {
            $insertData = [
                'status' => 1,
                'type' => 11,
                'phone' => $phone,
            ];
            $userId = $db->add($insertData, 'user');
        }

        //添加到用户表
        $insertData = [
            'phone' => $phone,
            'regmacno' => $deviceId,
            'user_id' => $userId,
            'regtime' => time(),
        ];
        $db->add($insertData, 'orduser');


        //添加到角色表
        $insertData = [
            'role_id' => 14,
            'user_id' => $userId,
        ];
        $db->add($insertData, 'role_user');

        return $userId;
    }
    


}