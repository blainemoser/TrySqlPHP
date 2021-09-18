<?php

namespace TrySqlPHP;

require_once("src/Watcher.php");

class TrySqlPHP extends Watcher
{
    /**
     * Constructs an instance of this
     * 
     * @param int $port
     * 
     * @return void
     */
    public function __construct(int $port = 0)
    {
        // register events
        pcntl_signal(SIGHUP, [$this, 'handleSigHup']);
        pcntl_signal(SIGINT, [$this, 'handleSigInt']);
        pcntl_signal(SIGTERM, [$this, 'handleSigTerm']);
        parent::__construct($port);
    }

    /**
     * SIGHUP: the controlling pseudo or virtual terminal has been closed
     */
    public function handleSigHup()
    {
        echo ("Caught SIGHUP, terminating.\n");
        $this->ended();
    }

    /**
     * SIGINT: the user wishes to interrupt the process; this is typically initiated by pressing Control-C
     *
     * It should be noted that SIGINT is nearly identical to SIGTERM.
     */
    public function handleSigInt()
    {
        echo ("Caught SIGINT, terminating.\n");
        $this->ended();
    }

    /**
     * SIGTERM: request process termination
     *
     * The SIGTERM signal is a generic signal used to cause program termination.
     * It is the normal way to politely ask a program to terminate.
     * The shell command kill generates SIGTERM by default.
     */
    public function handleSigTerm()
    {
        echo ("Caught SIGTERM, terminating.\n");
        $this->ended();
    }
}
