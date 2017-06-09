<?php

namespace app\api\model;

class Auth
{
	/**
	 * 获取每个控制器下的自定义public方法
	 * @author 王崇全
	 * @date
	 * @param string $suffix 控制器后缀，默认从配置文件获取
	 * @return array
	 */
	public function GetPublicMethods(string $suffix = null)
	{
		//从配置文件获取控制器后缀
		if (!$suffix)
		{
			$suffix = config("controller_suffix");
		}

		$methods = [];

		//获取控制器文件
		$controllerFiles = scandir(ROOT_PATH."/app/api/controller/");
		if (!$controllerFiles)
		{
			return [];
		}

		//获取每个控制器下的自定义public方法
		foreach ($controllerFiles as $controllerFile)
		{
			//过滤 . 和 ..
			if ($controllerFile === "." || $controllerFile === "..")
			{
				continue;
			}

			//控制器名称
			$controller = basename($controllerFile, ".php");

			//此控制器下的所有方法
			$ms = get_class_methods('app\api\controller\\'.$controller);
			if (!$ms)
			{
				continue;
			}

			//过滤方法
			foreach ($ms as $k => $m)
			{
				//过滤非自定义方法
				if (in_array($m, [
					"_initialize",
					"_empty",
					"__construct",
				]))
				{
					unset($ms[$k]);
					continue;
				}

				$foo = new \ReflectionMethod('app\api\controller\\'.$controller, $m);
				foreach (\Reflection::getModifierNames($foo->getModifiers()) as $modifierName)
				{
					//过滤非public方法
					if (in_array($modifierName, [
						"protected",
						"private",
					]))
					{
						unset($ms[$k]);
					}
				}
			}
			if (empty($ms))
			{
				continue;
			}

			$methods[str_replace($suffix, "", $controller)] = $ms;
		}

		return $methods;
	}

	/**
	 * 操作方法转
	 * @author 王崇全
	 * @date
	 * @param string $separator 控制器与操作方法的分割符
	 * @return array
	 */
	public function MethodToAuthName(string $separator = "-")
	{
		$authNames = [];

		$methods = $this->GetPublicMethods();
		if (!$methods)
		{
			return [];
		}

		foreach ($methods as $controller => $ms)
		{
			if (!$ms)
			{
				continue;
			}

			foreach ($ms as $method)
			{
				if (!$method)
				{
					continue;
				}

				$authNames[] = $controller.$separator.$method;
			}
		}

		return $authNames;
	}

}