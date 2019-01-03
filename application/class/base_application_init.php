<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/12/29
 * Time: 17:38
 */

interface base_application_init
{
    function _api_init();

    function _db_init();

    function _ecs_init();

    function _config_init();

    function _sess_init();

    function _user_init();

}