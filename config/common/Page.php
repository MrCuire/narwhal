<?php

trait Page
{
    protected function limit()
    {
        $size = isset($this->data->pageSize) ? $this->data->pageSize : 15;
        $limit = array(
            ((isset($this->data->curPage) ? $this->data->curPage : 1) - 1) * $size,
            $size
        );
        return implode(',', $limit);
    }


    /**
     * 获取分页总数
     * @param string $sql      原生的sql语句, 不支持子查询
     * @param string $select    统计规则
     * @return bool|string      返回分页总数
     */
    protected function getTotalPage($sql, $select='count(*)')
    {
        /** @var cls_mysql $db */
        $db = $GLOBALS['db'];
        $pattrens = [
            '/select.*?from/i',
            '/limit.+$/i'
        ];
        $replacements = [
            "select {$select} from",
            ""
        ];
        $sql = preg_replace($pattrens, $replacements, $sql, 1);

        return $db->getOne($sql) ? : 0;
    }



}