<?php 
/**
 * 该文件实现手机端免登服务，把该页面作为应用的登录验证页面；
 *
 * 登录有几种情况：
 *
 * 1. 企业自建微应用登录 	type=app
 * 2. 企业自建服务窗登录 	type=service
 * 3. isv服务窗登录   		type=isv_service
 * 4. isv微应用登录		type=isv_app
 * 5. 微应用后台管理员免登  type=admin
 * 6. 普通钉钉用户账号开放及免登 type=oauth
 * 7. 网站应用钉钉扫码登录  type=barcode
 * 8. 企业员工对isv授权    type=auth_isv
 *
 * 不同的登录需要带上get参数type，如果不带，默认为企业自建微应用登录
 *
 * 该页面还需要传入agentid：登录的应用id
 *
 * 免登成功后，触发YDingHook::AUTH_SUCCESS；
 * 免登失败，触发YDingHook::AUTH_FAIL；
 */

chdir(dirname(__FILE__));//把工作目录切换到文件所在目录
include_once dirname(__FILE__).'/../__config__.php';
try{
	if ( @ $_GET["error"]){
		YDingHook::do_hook(YDingHook::AUTH_FAIL, $_GET["error"]);
		die();
	}

	if ( @ ! $_GET["agentid"]){
		YDingHook::do_hook(YDingHook::AUTH_FAIL, "missing agentid");
		die();
	}

	switch (@$_GET["type"]){
		case "service":
			$hook_get_token 			= YDingHook::GET_CHANNEL_TOKEN;
			$hook_refresh_token 		= YDingHook::REFRESH_CHANNEL_TOKEN;
			$hook_get_jsapi_ticket  	= YDingHook::GET_CHANNEL_JS_API_TICKET;
			$hook_refresh_jsapi_ticket  = YDingHook::REFRESH_CHANNEL_JS_API_TICKET;
				
			$func_get_access_token		= "yding_get_channel_token";
			$func_get_user_Info			= "yding_get_channel_user_by_code";
			$func_get_js_ticket			= "yding_get_channel_jsapi_ticket";
			break;
		case "isv_service":
			$hook_get_token 			= YDingHook::GET_CHANNEL_ISV_TOKEN;
			$hook_refresh_token 		= YDingHook::REFRESH_CHANNEL_ISV_TOKEN;
			$hook_get_jsapi_ticket  	= YDingHook::GET_CHANNEL_JS_API_TICKET;
			$hook_refresh_jsapi_ticket  = YDingHook::REFRESH_CHANNEL_JS_API_TICKET;
				
			$func_get_access_token		= "yding_get_channel_isv_token";
			$func_get_user_Info			= "yding_get_channel_user_by_code";
			$func_get_js_ticket			= "yding_get_channel_jsapi_ticket";
			break;
		case "isv_app":
			die("not support now");
		case "app":
		default:
			$hook_get_token 			= YDingHook::GET_ACCESS_TOKEN;
			$hook_refresh_token 		= YDingHook::REFRESH_ACCESS_TOKEN;
			$hook_get_jsapi_ticket  	= YDingHook::GET_JS_API_TICKET;
			$hook_refresh_jsapi_ticket  = YDingHook::REFRESH_JS_API_TICKET;
				
			$func_get_access_token		= "yding_get_access_token";
			$func_get_user_Info			= "yding_get_User_Info";
			$func_get_js_ticket			= "yding_get_jsapi_ticket";
	}

	$access_token = YDingHook::do_hook($hook_get_token);


	if ( ! $access_token){
		$access_token = $func_get_access_token();
		YDingHook::do_hook($hook_refresh_token, $access_token);
	}


	if ( @ $_GET["code"]){
		$userinfo = $func_get_user_Info($access_token, $_GET["code"]);
		YDingHook::do_hook(YDingHook::AUTH_SUCCESS, $userinfo);
		die();
	}

	$jsapi_ticket = YDingHook::do_hook($hook_get_jsapi_ticket);
	if ( ! $jsapi_ticket){
		//有可能jsapi_ticket.php没有配置执行或者得到的jsapi_ticket没有存储下来，
		//这里重新执行一次获取jsapi ticket
		$jsapi_ticket = $func_get_js_ticket($access_token);
		YDingHook::do_hook($hook_refresh_jsapi_ticket, $jsapi_ticket);
	}

	$nonceStr = uniqid();
	$timeStamp = time();
	$url 		= yding_curr_page_url ();
	$signature = yding_sign($jsapi_ticket, $nonceStr, $timeStamp, $url);

}catch (YDing_Exception $e){
	YDingHook::do_hook(YDingHook::EXCEPTION, $e);
	YDingHook::do_hook(YDingHook::AUTH_FAIL, $e->getMessage());
	die();
}

?>