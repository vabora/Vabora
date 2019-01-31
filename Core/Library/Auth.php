<?php
/**
 * Auth 系统访问认证类
 * @author vabora(王小平)
 * @create time 2018/5/7 15:43:22
 * @version 0.0.1
 */
namespace Core\Library;
class Auth
{
	private $data = null;
	private $user = array(
		'id'=>'id',
		'roleid'=>'roleid',
		'time'=>'time'
	);
	private $role = array(
		'id'=>'id',
		'permission'=>'permission'
	);
	private $permission = array(
		'id'=>'id'
	);
	private $permission_list = array(
		'id'=>'id'
	);
}
