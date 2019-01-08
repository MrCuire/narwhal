<?php

/**
 * 地区表无限极分类
 * Trait Tree
 */
trait Tree
{
    /**
     * 获取父节点
     * @param int $id        当前节点id
     * @param array $select 查询字段名
     * @return mixed        父节点数组
     */
    public function parent($id, array $select=['*'])
    {
        $table = $GLOBALS['ecs']->table('region');
        /**
         * @var cls_mysql
         */
        $db = $GLOBALS['db'];
        $select = implode(',', array_map(function($item){
            return 'b.' . $item;
        }, $select));

        $sql = implode(' ', [
            "select {$select} from {$table} as a",
            "left join {$table} as b on a.parent_id=b.region_id",
            "where a.region_id={$id} limit 1"
        ]);
        $result = $db->getRow($sql);
        return (count($result) == 1) ? current($result) : $result;
    }

    /**
     * 获取父节点树
     * @param int $id       当前节点
     * @param array $select 查询字段名
     * @return array        返回结果数据
     */
    public function parentsTree($id, array $select=['*'])
    {
        $tree = [];
        do{
            $parent = $this->parent($id);
            $id = $parent['region_id'];

            if(in_array('*', $select)){
                $tree[] = $parent;
            }else{
                $tmp = array();
                array_walk($parent, function($item, $key, $allow)use(&$tmp){
                    in_array($key, $allow) && ($tmp = array_merge($tmp, array($key=>$item)));
                }, $select);
                $tree[] = $tmp;
            }
        }while($id != 1);

        return (count(current($tree)) > 1) ? $tree : array_column($tree, current($select));
    }

    /**
     *获取子级节点
     * @param int parent_id     父节点
     * @param array select      查询内容，默认值='*'
     * @return array return     返回结果
     */
    public function children($parent_id,array $select=['*']){

        $table = $GLOBALS['ecs']->table('region');
        /**
         * @var cls_mysql
         */
        $db = $GLOBALS['db'];
        $select = implode(',', array_map(function($item){
            return 'b.' . $item;
        }, $select));
        $sql = implode(' ', [
            "select {$select} from {$table} as a",
            "left join {$table} as b on a.parent_id=b.region_id",
            "where a.parent_id={$parent_id} "
        ]);
        $result = $db->getAll($sql);
        return (count($result) == 1) ? current($result) : $result;
    }

}