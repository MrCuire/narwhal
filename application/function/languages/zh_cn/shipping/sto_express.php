<?php

/**
 * ECSHOP 申通快递插件的语言文件
 * ============================================================================
 * 版权所有 2005-2011 亚洲网视股份有限公司，并保留所有权利。
 * 网站地址: http://www.charse.net；
 * ----------------------------------------------------------------------------
 * 
 *  
 * ============================================================================
 * $Author: liubo $
 * $Id: sto_express.php 17217 2011-01-19 06:29:08Z liubo $
*/

$_LANG['sto_express']            = '申通快递';
$_LANG['sto_express_desc']       = '由商家选择合作快递为您配送：';
$_LANG['item_fee']              = '单件商品费用：';
$_LANG['base_fee']              = '1000克以内费用';
$_LANG['step_fee']               = '续重每1000克或其零数的费用';
$_LANG['shipping_print']         = '<table border="0" cellspacing="0" cellpadding="0" style="width:18.9cm;">
  <tr>
    <td colspan="3" style="height:2cm;">&nbsp;</td>
  </tr>
  <tr>
    <td style="width:8cm; height:4cm; padding-top:0.3cm;" align="center" valign="middle">
     <table border="0" cellspacing="0" cellpadding="0" style="width:7.5cm;" align="center">
  <tr>
    <td style="width:2.3cm;">&nbsp;</td>
    <td style="height:1.5cm;">{$shop_address}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$shop_name}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$shop_name}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$service_phone}</td>
  </tr>
</table>

    </td>
    <td style="width:8cm; height:4cm; padding-top:0.3cm;" align="center" valign="middle">
    <table border="0" cellspacing="0" cellpadding="0" style="width:7.5cm;" align="center">
  <tr>
    <td style="width:2.3cm;">&nbsp;</td>
    <td style="height:1.5cm;">{$order.address}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$order.consignee}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$order.tel}</td>
  </tr>
</table>
    </td>
    <td rowspan="2" style="width:3cm;">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2" style="height:3.5cm;">&nbsp;</td>
  </tr>
</table>';

?>