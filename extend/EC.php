<?php
// +----------------------------------------------------------------------
// | api请求返回的错误码
// +----------------------------------------------------------------------
class EC
{
	//错误码
	const SUCCESS = 200;
	const API_ERR = 500;

	//URL - (401 -- 420)
	const  URL_ERROR                      = 401; //url有误
	const  URL_EXPIRED_ERROR              = 402; //url失效
	const  URL_ACCESSTOKEN_NOTEXIST_ERROR = 403; //url缺少授权信息ACCESS-TOKEN
	const  ADD_ERR                        = 404; //url缺少授权信息ACCESS-TOKEN

	//database - (421 -- 440)
	const  DB_CONNECT_ERROR   = 421; //数据库连接失败
	const  DB_OPERATION_ERROR = 422; //数据操作失败

	//USER - (441 -- 460)
	const  USERORPASSWD_ERROR        = 441; //用户名或密码错误
	const  USER_NOTLOGIN_ERROR       = 442; //用户未登陆
	const  USER_NOTACTIVE_ERROR      = 443; //用户未激活
	const  USER_NOPERMISSION_ERROR   = 444; //用户未授权
	const  USER_NOTEXIST_ERROR       = 445; //用户不存在
	const  USER_EXIST_ERROR          = 446; //用户已经存在
	const  USER_PASSWD_NULL_ERROR    = 447; //密码为null
	const  USER_EMAIL_EXIST_ERROR    = 448; //邮箱已经存在
	const  USER_OLD_PWD              = 449; //新旧密码相同
	const  USER_OLD_PWD_ERR          = 450; //原密码不正确
	const  USER_NEW_PWD_NULL         = 451; //新密码不能为空
	const  USER_ADMIN_CANNOT_OPERATE = 452; //不能修改超级管理员
	const  USER_CANNOT_REGISTER_ROLE = 453; //不能注册此角色的的用户
	const  USER_NOT_FOLLOWER         = 454; //此用户未关注您
	const  USER_NOT_FOLLOWING        = 455; //未关注此用户

	//ACCESSTOKEN - (461 -- 480)
	const  ACCESSTOKEN_ERROR         = 461; //ACCESS-TOKEN授权信息有误
	const  ACCESSTOKEN_EXPIRED_ERROR = 462; //ACCESS-TOKEN过期

	//PERMISSION - (481 -- 500)
	const  PERMISSION_NO_ERROR          = 481; //没有权限执行此操作
	const  ROLE_NOTEXIST_ERROR          = 482; //角色或组不存在
	const  ROLE_EXIST_ERROR             = 483; //角色或组已经存在
	const  CHANNLE_NOTEXIST_ERROR       = 484; //频道不存在
	const  CHANNLE_EXIST_ERROR          = 485; //频道已经存在
	const  CATALOG_NOTEXIST_ERROR       = 486; //编目不存在
	const  CATALOG_EXIST_ERROR          = 487; //编目已经存在
	const  CATALOG_VALUE_EXIST_ERROR    = 488; //编目值已经存在
	const  CATALOG_VALUE_NOTEXIST_ERROR = 489; //编目值不存在

	//FILE - (501 -- 520)
	const  SOURCE_NOT_EXIST_ERROR  = 501; //资源或路径不存在
	const  FILE_DELETE_ERROR       = 502; //文件删除错误
	const  FILE_HASH_CREATE_ERROR  = 503; //hash计算错误
	const  FILE_COPY_ERROR         = 504; //文件复制失败
	const  FILE_EXIST_ERROR        = 505; //文件已经存在
	const  MESSAGE_NOTEXIST_ERROR  = 506; //消息不存在
	const  IS_FAVORITE_ERROR       = 507; //文件已经收藏
	const  NOT_FAVORITE_ERROR      = 508; //文件未被收藏
	const  SET_CONFIG_ERROR        = 509; //网站设置失败
	const  GET_CONFIG_ERROR        = 510; //网站设置获取失败
	const  IMAGE_SIZE_SAMLL        = 511; //图像尺寸过小
	const  FILE_READ_ERROR         = 512; //文件读取失败
	const  FAVORITE_EXIST_ERROR    = 513; //收藏已经存在
	const  FAVORITE_NOTEXIST_ERROR = 514; //收藏不存在
	const  MKDIR_ERROR             = 515; //临时文件映射创建失败
	const  FAV_ERR_YOURSELF        = 516; //不能收藏自己的资源
	const  DIR_MK_ERR              = 517; //文件夹创建失败
	const  FILE_NOT_VR             = 518; //非VR文件

	//Param - (521 -- 540)
	const  PARAM_ERROR      = 521; //参数错误
	const  VERIFYCODE_ERROR = 522; //验证码错误
	const  FILE_COLS_ERROR  = 523; //数据列数不合法
	const  PARAM_SAFEERROR  = 524; //系统检测到有攻击行为存在

	//Param - (541 -- 560)
	const  MAIL_ERROR              = 541; //邮件错误
	const  MAIL_ADDRESSEE_TOO_MORE = 542; //收件人一次不能超过10个

	//Chat - (561 -- 580)
	const  CHAT_NOTEXIST_ERROR        = 561; //评论不存在
	const  NO_PERMISSION_DELETE_ERROE = 562; //无权删除此评论
	const  ALREADY_VOTED_ERROE        = 563; //已投过票

	const  DEVICE_MISSING = 591; //设备信息不存在
	const  TOO_FREQUENT   = 592; //操作过于频繁
	const  VERSION_LOW    = 593; //version低于服务器version
	const  ORDER_ERROR    = 594; //指令无效

	//LIVE - ( 595 -- 610)
	const  LIVE_EXIST_ERROR         = 595; //直播已经存在
	const  LIVE_NOTEXIST_ERROR      = 596; //直播不存在
	const  LIVE_ROOM_EXIST_ERROR    = 597; //直播间已经存在
	const  LIVE_ROOM_NOTEXIST_ERROR = 598; //直播间不存在
	const  LIVE_SURL_ERROR          = 599; //流地址错误
	const  LIVE_ROOM_ADD_ERR        = 600; //直播间创建失败
	const  LIVE_ROOM_MAP_ADD_ERR    = 601; //直播-直播间关联失败
	const  LIVE_ROOM_NOT_VR         = 602; //非VR直播间
	const  LIVE_ADD_ERR             = 603; //直播添加失败
	const  LIVE_NOT_INTERACTION     = 604; //不是互动直播
	const  LIVE_ROOM_NOT_THIS       = 605; //不是此互动直播所用的直播间

	//Share - ( 620 -- 640 )
	const  SHARE_IS_PUBLIC      = 621; //公开资源不能分享
	const  SHARE_NOT_EXIST      = 622; //分享不存在或无权查看
	const  SHARE_LINK_PWD_ERROR = 623; //分享不存在或无权查看

	//LOG - (641 - 660)
	const  LOG_EXIST_ERROR    = 641; //历史记录已经存在
	const  LOG_NOTEXIST_ERROR = 642; //历史记录不存在

	//上传 - (661 - 680)
	const  UPL_VOID              = 661; //空的上传请求
	const  UPL_NO_FILE_NAME      = 662; //服务器获取的文件名有误
	const  UPL_TMP_PATH          = 663; //临时文件的路径创建失败
	const  UPL_TMPFILE_READ_ERR  = 664; //分片读取失败
	const  UPL_THUNK_GETERR      = 665; //分片上传失败
	const  UPL_TMPFILE_WRITE_ERR = 666; //分片保存失败
	const  UPL_FILE_CREATE_ERR   = 667; //目标文件创建失败
	const  UPL_THUNK_TO_FILE_ERR = 668; //分片写入总文件失败
	const  UPL_CHUNK_MISS        = 669; //分片丢失
	const  EXCEED_UPL_NUM_LIMIT  = 670; //上传的文件过多
	const  FILE_INFO_CREATE_ERR  = 671; //文件信息创建失败
	const  UPL_TEMPFILE_PATH_ERR = 672; //上传的临时文件获取失败
	const  FILE_UPLOAD_ERROR     = 503; //文件上传失败

	//课程管理 - （681-700）
	const  COURSE_NOT_EXIST         = 681; //课程不存在
	const  COURSE_SECTION_NOT_EXIST = 682; //章节不存在

	//Auth - (701 - 720)
	const  AUTH_NOT_EXIST              = 701; //授权信息未找到
	const  AUTH_MACHINESCODE_GET_ERROR = 702; //本机机器码读取失败
	const  AUTH_MACHINESCODE_ERROR     = 703; //机器码错误
	const  AUTH_MACHINESCODE_EXIST     = 704; //机器码已经注册
	const  AUTH_ACTIVECODE_ERROR       = 705; //注册码错误
	const  AUTH_ACTIVECODE_NOTEXIST    = 706; //未授权
	const  AUTH_FILE_NOTEXIST_ERROR    = 707; //授权文件未找到
	const  AUTH_FILE_WIRTE_ERROR       = 708; //授权文件写入失败
	const  AUTH_FILE_READ_ERROR        = 709; //授权文件读取失败
	const  AUTH_FILE_ERROR             = 710; //授权文件信息有误
	const  AUDIT_ERROR                 = 711; //请求错误

	//VR - (721 - 740)
	const  VR_HOT_NOT_EXIST = 721; //VR热点不存在

	//资源 - (761 - 780)
	const  SOURCE_ADD_ERR   = 761; //资源添加失败
	const  SOURCE_VOTE_SELF = 762; //不能赞/踩自己的资源

	//关系 - (781 - 800)
	const  RELATIONSHIP_ADD_ERR = 781; //关注失败

	//错误信息
	protected static $_msg = [
		self:: SUCCESS => '操作成功',
		self:: API_ERR => '程序中断',

		self:: URL_ERROR                      => 'url有误',
		self:: URL_EXPIRED_ERROR              => 'url失效',
		self:: URL_ACCESSTOKEN_NOTEXIST_ERROR => 'url缺少授权信息ACCESS-TOKEN',
		self:: ADD_ERR                        => '添加失败',

		self:: DB_CONNECT_ERROR   => '数据库连接失败',
		self:: DB_OPERATION_ERROR => '数据操作失败',

		self:: USERORPASSWD_ERROR        => '用户名或密码错误',
		self:: USER_NOTLOGIN_ERROR       => '用户未登陆',
		self:: USER_NOTACTIVE_ERROR      => '用户未激活',
		self:: USER_NOPERMISSION_ERROR   => '用户未授权',
		self:: USER_NOTEXIST_ERROR       => '用户不存在',
		self:: USER_EXIST_ERROR          => '用户已经存在',
		self:: USER_PASSWD_NULL_ERROR    => '密码为空',
		self:: USER_EMAIL_EXIST_ERROR    => '该邮箱已被注册，请重新输入',
		self:: USER_OLD_PWD              => '新旧密码相同',
		self:: USER_OLD_PWD_ERR          => '原密码不正确',
		self:: USER_NEW_PWD_NULL         => '新密码不能为空',
		self:: USER_ADMIN_CANNOT_OPERATE => '不能对超级管理员进行操作',
		self:: USER_CANNOT_REGISTER_ROLE => '不能注册此角色的用户',
		self:: USER_NOT_FOLLOWER         => '此用户未关注您',
		self:: USER_NOT_FOLLOWING        => '未关注此用户',

		self:: ACCESSTOKEN_ERROR         => '令牌无效',
		self:: ACCESSTOKEN_EXPIRED_ERROR => '令牌已过期',

		self:: PERMISSION_NO_ERROR          => '没有权限执行此操作',
		self:: ROLE_NOTEXIST_ERROR          => '角色或组不存在',
		self:: ROLE_EXIST_ERROR             => '角色或组已经存在',
		self:: CHANNLE_NOTEXIST_ERROR       => '频道不存在',
		self:: CHANNLE_EXIST_ERROR          => '频道已经存在',
		self:: CATALOG_NOTEXIST_ERROR       => '编目不存在',
		self:: CATALOG_EXIST_ERROR          => '编目已经存在',
		self:: CATALOG_VALUE_EXIST_ERROR    => '编目值已经存在',
		self:: CATALOG_VALUE_NOTEXIST_ERROR => '编目值不存在',

		self:: SOURCE_NOT_EXIST_ERROR  => '资源不存在',
		self:: FILE_DELETE_ERROR       => '文件删除错误',
		self:: FILE_HASH_CREATE_ERROR  => 'Hash计算错误',
		self:: FILE_COPY_ERROR         => '文件复制失败',
		self:: FILE_EXIST_ERROR        => '文件已经存在',
		self:: MESSAGE_NOTEXIST_ERROR  => '消息不存在',
		self:: IS_FAVORITE_ERROR       => '已经收藏',
		self:: NOT_FAVORITE_ERROR      => '文件未被收藏',
		self:: SET_CONFIG_ERROR        => '网站设置失败',
		self:: GET_CONFIG_ERROR        => '网站设置获取失败',
		self:: IMAGE_SIZE_SAMLL        => '图像尺寸过小',
		self:: FILE_READ_ERROR         => '文件读取失败',
		self:: FAVORITE_EXIST_ERROR    => '收藏已经存在',
		self:: FAVORITE_NOTEXIST_ERROR => '收藏不存在',

		self:: PARAM_ERROR      => '参数错误',
		self:: VERIFYCODE_ERROR => '验证码错误',
		self:: FILE_COLS_ERROR  => '数据列数不合法',
		self:: PARAM_SAFEERROR  => '系统检测到有攻击行为存在',

		self:: MAIL_ERROR              => '邮件错误',
		self:: MAIL_ADDRESSEE_TOO_MORE => '收件人一次不能超过10个',

		self:: CHAT_NOTEXIST_ERROR        => '评论不存在',
		self:: NO_PERMISSION_DELETE_ERROE => '无此评论或无权删除',
		self:: ALREADY_VOTED_ERROE        => '已赞/踩',

		self:: DEVICE_MISSING           => '设备信息不存在',
		self:: TOO_FREQUENT             => '操作过于频繁',
		self:: VERSION_LOW              => 'Version低于服务器Version',
		self:: ORDER_ERROR              => '指令无效',
		self:: LIVE_EXIST_ERROR         => '直播已经存在',
		self:: LIVE_NOTEXIST_ERROR      => '直播不存在',
		self:: LIVE_ROOM_EXIST_ERROR    => '直播间已经存在',
		self:: LIVE_ROOM_NOTEXIST_ERROR => '直播间不存在',
		self:: LIVE_SURL_ERROR          => '流地址错误',
		self:: LIVE_ROOM_ADD_ERR        => '直播间创建失败',
		self:: LIVE_ROOM_MAP_ADD_ERR    => '直播-直播间关联失败',
		self:: LIVE_ROOM_NOT_VR         => '非VR直播间',
		self:: LIVE_ADD_ERR             => '直播添加失败',
		self:: LIVE_NOT_INTERACTION     => '不是互动直播',
		self:: LIVE_ROOM_NOT_THIS       => '不是此互动直播所用的直播间',

		self:: SHARE_IS_PUBLIC      => '公开资源不需要分享，直接分享网址即可',
		self:: SHARE_NOT_EXIST      => '分享不存在或无权查看',
		self:: SHARE_LINK_PWD_ERROR => '链接分享的密码有误',

		self:: LOG_EXIST_ERROR    => '历史记录已经存在',
		self:: LOG_NOTEXIST_ERROR => '历史记录不存在',

		self:: UPL_VOID              => '空的上传请求',
		self:: UPL_NO_FILE_NAME      => '服务器获取的文件名有误',
		self:: UPL_TMP_PATH          => '临时文件的路径创建失败',
		self:: UPL_TMPFILE_READ_ERR  => '分片读取失败',
		self:: UPL_THUNK_GETERR      => '分片获取失败',
		self:: UPL_TMPFILE_WRITE_ERR => '分片保存失败',
		self:: UPL_FILE_CREATE_ERR   => '目标文件创建失败',
		self:: UPL_THUNK_TO_FILE_ERR => '分片写入总文件失败',
		self:: UPL_CHUNK_MISS        => '分片丢失',
		self:: UPL_TEMPFILE_PATH_ERR => '上传的临时文件获取失败',

		self:: MKDIR_ERROR          => '临时文件映射创建失败',
		self:: FAV_ERR_YOURSELF     => '不能收藏自己的资源',
		self:: DIR_MK_ERR           => '文件夹创建失败',
		self:: FILE_NOT_VR          => '非VR文件',
		self:: EXCEED_UPL_NUM_LIMIT => '上传的文件过多',
		self:: FILE_INFO_CREATE_ERR => '文件信息创建失败',
		self:: FILE_UPLOAD_ERROR    => '文件上传失败',

		self:: COURSE_NOT_EXIST         => '课程不存在',
		self:: COURSE_SECTION_NOT_EXIST => '章节不存在',

		self:: AUTH_NOT_EXIST              => '授权信息未找到',
		self:: AUTH_MACHINESCODE_GET_ERROR => '本机机器码读取失败',
		self:: AUTH_MACHINESCODE_ERROR     => '机器码错误',
		self:: AUTH_MACHINESCODE_EXIST     => '机器码已经注册',
		self:: AUTH_ACTIVECODE_ERROR       => '注册码错误',
		self:: AUTH_ACTIVECODE_NOTEXIST    => '未授权',
		self:: AUTH_FILE_NOTEXIST_ERROR    => '授权文件未找到',
		self:: AUTH_FILE_WIRTE_ERROR       => '授权文件写入失败',
		self:: AUTH_FILE_READ_ERROR        => '授权文件读取失败',
		self:: AUTH_FILE_ERROR             => '授权文件信息有误',

		self:: VR_HOT_NOT_EXIST => 'VR热点不存在',

		self:: SOURCE_ADD_ERR   => '资源添加失败',
		self:: SOURCE_VOTE_SELF => '不能赞/踩自己的资源',

		self:: RELATIONSHIP_ADD_ERR => '关注失败',
	];

	/**
	 * 根据错误码获取错误信息
	 * @author 王崇全
	 * @param $code
	 * @throws Exception
	 * @return mixed
	 */
	public static function GetMsg($code)
	{
		if (!is_int($code))
		{
			throw new \Exception("错误码只能是整数");
		}

		if (!isset(self::$_msg[$code]))
		{
			return "API出现致命的非预期错误";
		}

		return self::$_msg[$code];
	}

	/**
	 * GetClassConstants
	 * @author 王崇全
	 * @date
	 * @return array
	 */
	public function GetClassConstants()
	{
		$reflect = new ReflectionClass(get_class($this));

		return $reflect->getConstants();
	}
}
