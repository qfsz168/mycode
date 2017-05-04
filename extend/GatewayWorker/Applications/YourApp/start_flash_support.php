<?php 

use \Workerman\Worker;
#use \Workerman\Autoloader;

$flash_policy = new Worker('tcp://0.0.0.0:843');

$flash_policy->onMessage = function($connection, $message)
{
    $connection->send('<?xml version="1.0"?><cross-domain-policy><site-control permitted-cross-domain-policies="all"/><allow-access-from domain="*" to-ports="*"/></cross-domain-policy>'."\0");
};


// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')) {
    Worker::runAll();
}

