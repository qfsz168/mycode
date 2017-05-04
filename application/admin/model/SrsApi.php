<?php
/**
 * Created by PhpStorm.
 * User: hacfin
 * Date: 2017/4/13
 * Time: 10:39
 */

namespace application\admin\model;

use think\Cache;

class SrsApi
{
	protected $_apiUrl          = "";
	protected $_serverId        = null;
	protected $_isServerChanged = false;

	public function __construct(string $apiHost, int $apiPort)
	{
		$this->_apiUrl = "http://{$apiHost}:{$apiPort}/api/v1/";

		$data = $this->GetDate();
		if (isset($data) && $data["code"] === 0)
		{
			$this->_serverId = $data["server"];
			Cache::remember("srs_server_id", $this->_serverId);
		}
	}

	/**
	 * 获取api数据
	 * @author 王崇全
	 * @param string $bName 面包屑名称
	 * @return bool|array
	 */
	protected function GetDate(string $bName = "")
	{
		$url = $this->_apiUrl.$bName;
		$url = strtolower($url);

		$data = file_get_contents($url);

		$data = json_decode($data, true);

		if (is_null($data) || $data["code"] !== 0)
		{
			E(\EC::API_ERR, "{$url} 没有返回有效的数据");
		}

		return $data;
	}

	public function Index(string $bName = "")
	{
		return $this->GetDate($bName);
	}

	public function Summaries()
	{
		$summaries = $this->GetDate("summaries");

		return $summaries["data"]??null;
	}

	/**
	 * 服务器版本信息
	 * @author 王崇全
	 * @date
	 * @return array|bool
	 */
	public function Versions()
	{
		$versions = $this->GetDate("versions");

		return $versions["data"]??null;
	}

	/**
	 * 服务器资源使用信息
	 * @author 王崇全
	 * @date
	 * @return array|bool
	 */
	public function Rusages()
	{
		$rusages = $this->GetDate("rusages");

		return $rusages["data"]??null;
	}

	/**
	 * 服务器进程信息
	 * @author 王崇全
	 * @date
	 * @return array|bool
	 */
	public function SelfProcStats()
	{
		$selfProcStats = $this->GetDate("self_proc_stats");

		return $selfProcStats["data"]??null;
	}

	/**
	 * 服务器所有进程情况
	 * @author 王崇全
	 * @date
	 * @return array|bool
	 */
	public function SystemProcStats()
	{
		$systemProcStats = $this->GetDate("system_proc_stats");

		return $systemProcStats["data"]??null;
	}

	/**
	 * 服务器内存使用情况
	 * @author 王崇全
	 * @date
	 * @return array|bool
	 */
	public function MemInfos()
	{
		$memInfos = $this->GetDate("meminfos");

		return $memInfos["data"]??null;
	}

	/**
	 * 作者、版权和License信息
	 * @author 王崇全
	 * @return array|bool
	 */
	public function Authors()
	{
		$authors = $this->GetDate("authors");

		return $authors["data"]??null;
	}

	/**
	 * 系统支持的功能列表
	 * @author 王崇全
	 * @return array|bool
	 */
	public function Features()
	{
		$features = $this->GetDate("features");

		return $features["data"]??null;
	}

	/**
	 * 服务器上的vhosts信息
	 * @author 王崇全
	 * @return array|bool
	 */
	public function Vhosts(int $vhostId = null)
	{
		$data = $this->GetDate("vhosts/{$vhostId}");
		if (is_null($vhostId))
		{
			return $data["vhosts"];
		}
		else
		{
			return $data["vhost"];
		}
	}

	/**
	 * 服务器的streams信息
	 * @author 王崇全
	 * @return array|bool
	 */
	public function Streams(int $streamId = null)
	{
		$data = $this->GetDate("streams/{$streamId}");
		if (is_null($streamId))
		{
			return $data["streams"];
		}
		else
		{
			return $data["stream"];
		}
	}

	/**
	 * 获取某客户端信息
	 * @author 王崇全
	 * @param int $client_id 客户端ID
	 * @return array|bool
	 */
	public function ClientInfo(string $client_id)
	{
		$client = $this->GetDate("clients/{$client_id}");

		return $client["client"];
	}

	/**
	 * 获取客户端列表
	 * @author 王崇全
	 * @param int $page     页码
	 * @param int $pageSize 页幅
	 * @return array|bool
	 */
	public function ClientList(int $page = 1, int $pageSize = 10)
	{
		$satrt = ($page - 1) * $pageSize;
		$count = $pageSize;

		//获取客户端总数
		$vhosts = $this->Vhosts();
		$vhost  = reset($vhosts);
		$total  = $vhost["clients"];

		$clients = $this->GetDate("clients/?start={$satrt}&count={$count}");

		return [
			"count" => $total,
			"list"  => $clients["clients"],
		];
	}

	/**
	 * 检查srs服务器是否重启过
	 * @author 王崇全
	 * @date
	 * @return bool
	 */
	protected function CheackServerChanged()
	{
		$data = $this->GetDate();
		if (($data !== false) && Cache::get("srs_server_id") != $data["server"])
		{
			$this->_serverId        = $data["server"];
			$this->_isServerChanged = true;

			return true;
		}

		return false;
	}
}