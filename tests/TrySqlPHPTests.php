<?php

declare(strict_types=1);

namespace TrySqlPHPTests;

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

    public function testGetProcStatus()
    {
        $status = self::$watcher->getProcStatus();
        $this->assertIsArray($status);
        if (!isset($status["pid"])) {
            trigger_error("expected status to contain the process ID 'pid'");
        }
        $this->assertIsInt($status["pid"]);
    }

    public function testReadyState() 
    {
        $state = self::$watcher->getReadyState();
        $this->assertEquals($state, SHELL_STATE_READY);
    }
}
