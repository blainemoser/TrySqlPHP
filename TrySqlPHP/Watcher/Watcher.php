<?php

namespace TrySqlPHP\Watcher;

const SHELL_STATE_NOT_STARTED = 0;
const SHELL_STATE_BOOTING = 2;
const SHELL_STATE_READY = 3;
const SHELL_STATE_DESTROYING = 5;
const SHELL_STATE_DESTROYED = 7;

class Watcher
{
    /**
     * @var int $readyState
     * 
     * 0 - not started 
     * 2 - booting
     * 3 - shell ready
     * 5 - destroying
     */
    protected int $readyState = 0;

    /**
     * @var string $password;
     */
    protected string $password = "";

    /**
     * @var resource $process
     */
    protected $process;

    /**
     * @var string $lastOut;
     */
    protected string $lastOut = "";

    /**
     * @var string $lastInput;
     */
    public string $lastInput = "";

    /**
     * @var array $pipes
     */
    private array $pipes = [];

    /**
     * @var array $descriptors
     */
    private array $descriptors = [
        0 => ["pipe", "r"],   // stdin is a pipe that the child will read from
        1 => ["pipe", "w"],   // stdout is a pipe that the child will write to
        2 => ["pipe", "w"]    // stderr is a pipe that the child will write to
    ];

    /**
     * @var $stdIn
     */
    private $stdIn;

    /**
     * @var $stdOut
     */
    private $stdOut;

    /**
     * @var $stdErr
     */
    private $stdErr;

    /**
     * @var array $procStatus
     */
    private array $procStatus = [];

    /**
     * Constructs an instance of watcher
     * 
     * @param int $port
     * 
     * @return void
     */
    public function __construct(int $port = 0)
    {
        $this->start($port);
    }

    /**
     * Starts the TrySql shell, waits for input 
     * 
     * @return void
     */
    private function start(int $port = 0): void
    {
        $command = "go run \$GOSRC/TrySql/main.go" . 
            ($port > 0 ? " -p " . (string) $port : ""); // TODO replace this with the command to use the bin
        flush();
        $this->process = proc_open($command, $this->descriptors, $this->pipes);
        $this->stdIn = $this->pipes[0];
        $this->stdOut = $this->pipes[1];
        $this->stdErr = $this->pipes[2];
        $this->readyState = SHELL_STATE_BOOTING;
        if (is_resource($this->process)) {
            while ($s = fgets($this->stdOut)) {
                if ($this->readyState === SHELL_STATE_BOOTING) {
                    print $s;
                }
                if ($this->readyState === SHELL_STATE_READY) {
                    $this->lastOut = $s;
                }
                if (strpos($s, "shell ready") !== false) {
                    $this->readyState = SHELL_STATE_READY;
                    $this->refreshProcStatus();
                    $this->setPassword();
                    $streamBlocked = stream_set_blocking($this->stdOut, 0);
                    echo $streamBlocked ? "stream unblocked\n" : "stream not unblocked \n";
                    break;
                }
                flush();
            }
        }
    }

    /**
     * Writes to the standard input for this process 
     * 
     * @param string $write
     * 
     * @return int
     */
    private function write(string $write): int
    {
        $result = fwrite($this->stdIn, $write, strlen($write));
        if ($result === false) {
            echo "failed write\n";
            return 0;
        }
        if (in_array($this->readyState, [SHELL_STATE_READY])) {
            flush();
            $s = fgets($this->stdOut);
            $this->lastOut = $s;
        }
        return $result;
    }

    /**
     * Refreshes process status
     * 
     * @return void
     */
    protected function refreshProcStatus(): void
    {
        $this->procStatus = proc_get_status($this->process);
    }


    /**
     * Refreshes then returns the process status
     * 
     * @return array
     */
    public function getProcStatus(): array
    {
        return $this->procStatus;
    }

    /**
     * Writes to shell
     * 
     * @param string $write
     * 
     * @return int
     */
    protected function writeToShell(string $write): int
    {
        if (!in_array($this->readyState, [SHELL_STATE_READY, SHELL_STATE_DESTROYING])) {
            return 0;
        }

        if (strpos($write, "\n") === false) {
            $write = "$write\n";
        }
        return $this->write($write);
    }

    /**
     * Sets the MySql password for the new container's service
     * 
     * @return void
     */
    private function setPassword(): void
    {
        $this->writeToShell("p");
        $this->password = $this->getLastOut();
    }

    /**
     * Gets the password
     * 
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function getReadyState()
    {
        return $this->readyState;
    }

    /**
     * Gets the shell's last output
     * 
     * @return string
     */
    private function getLastOut(): string
    {
        return str_replace("> ", "", $this->lastOut);
    }

    /**
     * Handles any ended signal
     * 
     *  
     */
    protected function ended()
    {
        if ($this->readyState === SHELL_STATE_READY) {
            $this->quit();
        }
    }

    /**
     * Quits the shell
     * 
     * @return void
     */
    public function quit(): void
    {
        $streamBlocked = stream_set_blocking($this->stdOut, 1);
        echo $streamBlocked ? "stream blocked\n" : "stream not blocked \n";
        $this->readyState = SHELL_STATE_DESTROYING;
        $this->writeToShell("quit");
        while ($this->readyState !== SHELL_STATE_DESTROYED && $s = fgets($this->stdOut)) {
            if ($this->readyState === SHELL_STATE_DESTROYING) {
                print $s;
            }
            if (strpos($s, "destroyed") !== false) {
                $this->readyState = SHELL_STATE_DESTROYED;
                fclose($this->stdIn);
                fclose($this->stdOut);
                fclose($this->stdErr);
                proc_close($this->process);
            }
            flush();
        }
    }

    /**
     * Destructs this instance 
     * 
     * @return void
     */
    public function __destruct()
    {
        if ($this->readyState !== SHELL_STATE_DESTROYED) {
            $this->quit();
        }
    }
}
