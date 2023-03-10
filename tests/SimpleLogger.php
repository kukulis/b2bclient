<?php
/**
 * SimpleLogger.php
 * Created by Giedrius Tumelis.
 * Date: 2021-04-13
 * Time: 11:41
 */

namespace Catalog\B2b\Client\Tests;


use Psr\Log\LoggerInterface;

class SimpleLogger implements LoggerInterface
{
    public function emergency($message, array $context = array())
    {
        echo $message."\n";
    }

    public function alert($message, array $context = array())
    {
        echo $message."\n";
    }

    public function critical($message, array $context = array())
    {
        echo $message."\n";
    }

    public function error($message, array $context = array())
    {
        echo $message."\n";
    }

    public function warning($message, array $context = array())
    {
        echo $message."\n";
    }

    public function notice($message, array $context = array())
    {
        echo $message."\n";
    }

    public function info($message, array $context = array())
    {
        echo $message."\n";
    }

    public function debug($message, array $context = array())
    {
        echo $message."\n";
    }

    public function log($level, $message, array $context = array())
    {
        echo $message."\n";
    }
}