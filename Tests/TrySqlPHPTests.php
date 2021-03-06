<?php

declare(strict_types=1);

namespace TrySqlPHP\Tests;

require 'src/TrySql.php';

use TrySqlPHP\TrySql;
use PHPUnit\Framework\TestCase;

use const TrySqlPHP\SHELL_STATE_READY;

final class WatcherTest extends TestCase
{

    /**
     * @var TrySqlPHP $watcher
     */
    private static TrySql $watcher;

    /**
     * Constructs this test class 
     * 
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        self::$watcher = new TrySql();
    }

    public function testGetPassword()
    {
        $password = self::$watcher->getPassword();
        $this->assertIsString($password);
        if (strlen(trim($password)) != 32) {
            trigger_error("expected password to be 32 characters, got " . (string) strlen($password));
        }
    }

    public function testGetMysqlCommand()
    {
        $command = self::$watcher->getMySqlCommand();
        $this->assertIsString($command);
        $this->assertStringContainsString("mysql", $command);
        $password = self::$watcher->getPassword();
        $this->assertStringContainsString("-p{$password}", $command);
        $user = self::$watcher->getUser();
        $this->assertStringContainsString("-u{$user}", $command);
        $host = self::$watcher->getHost();
        $this->assertStringContainsString("-h{$host}", $command);
        $port = (string) self::$watcher->getPort();
        $this->assertStringContainsString("-P{$port}", $command);
    }

    public function testGetProcStatus()
    {
        $status = self::$watcher->getProcStatus();
        $this->assertIsArray($status);
        if (!isset($status["pid"])) {
            trigger_error("expected status to contain the process ID 'pid'");
        }
        $this->assertIsInt($status["pid"]);
    }

    public function testGetPort()
    {
        $port = self::$watcher->getPort();
        $this->assertEquals($port, 6603);
    }

    public function testGetUser()
    {
        $u = self::$watcher->getUser();
        $this->assertEquals($u, "root");
    }

    public function testGetHost()
    {
        $h = self::$watcher->getHost();
        $this->assertEquals($h, "127.0.0.1");
    }

    public function testReadyState() 
    {
        $state = self::$watcher->getReadyState();
        $this->assertEquals($state, SHELL_STATE_READY);
    }
}
