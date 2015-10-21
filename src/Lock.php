<?php
/**
 * Rych Lock
 *
 * Simple process lock management library.
 *
 * @package   Rych\Lock
 * @copyright Copyright (c) 2014, Ryan Chouinard
 * @author    Ryan Chouinard <rchouinard@gmail.com>
 * @license   MIT
 */

namespace Rych\Lock;

/**
 * Lock class
 *
 * Represents a process lock.
 */
class Lock
{

    /**
     * Represents the locked state
     */
    const UNLOCKED = 0;

    /**
     * Represents the unlocked state
     */
    const LOCKED = 1;

    /**
     * Lock name
     *
     * @var string
     */
    protected $name;

    /**
     * Lock bucket / path
     *
     * @var string
     */
    protected $bucket;

    /**
     * Lock file pointer
     *
     * @var resource
     */
    protected $lock;

    /**
     * Lock status
     *
     * @var Lock::UNLOCKED|Lock::LOCKED
     */
    protected $status;

    /**
     * Lock constructor
     *
     * Creates a Lock instance for a specified lock name.
     *
     * @param  string  $name   Name of the lock.
     * @param  string  $bucket Filesystem path where lock files should be
     *   stored. If NULL, will be set to the system's default temporary
     *   directory.
     * @return void
     * @throws \RuntimeException Throws a \RuntimeException if the lock file
     *   cannot be opened or created.
     */
    public function __construct($name, $bucket = null)
    {
        if ($bucket === null) {
            $bucket = sys_get_temp_dir();
        }

        $filename = sprintf("%s/%s.lock", $bucket, self::cleanName($name));
        $lock = @fopen($filename, "c");
        if (!$lock) {
            throw new \RuntimeException("Unable to open/create lock file: {$filename}");
        }

        $this->name = $name;
        $this->bucket = $bucket;
        $this->lock = $lock;
        $this->status = self::UNLOCKED;
    }

    /**
     * Lock destructor
     *
     * Cleans up before the instance is destroyed.
     *
     * @return void
     */
    public function __destruct()
    {
        flock($this->lock, LOCK_UN);
        fclose($this->lock);
    }

    /**
     * Acquire a new lock
     *
     * @param  boolean $block If TRUE, the method call will block until any
     *   existing locks are released. Defaults to FALSE, meaning the call will
     *   return immediately.
     * @return boolean Will return TRUE on success, FALSE otherwise.
     */
    public function lock($block = false)
    {
        if ($this->status === self::LOCKED) {
            return false;
        }

        $flags = LOCK_EX;
        if (!$block) {
            $flags |= LOCK_NB;
        }

        if (($status = flock($this->lock, $flags))) {
            $this->status = self::LOCKED;

            $data = str_pad(getmypid(), 10, chr(32), STR_PAD_LEFT) . "\n";
            ftruncate($this->lock, 0);
            fwrite($this->lock, $data, 11);
        }

        return $status;
    }

    /**
     * Release the current lock
     *
     * @return boolean Will return TRUE on success, FALSE otherwise.
     */
    public function unlock()
    {
        if ($this->status === self::UNLOCKED) {
            return false;
        }

        ftruncate($this->lock, 0);
        if (($status = flock($this->lock, LOCK_UN))) {
            $this->status = self::UNLOCKED;
        }

        return $status;
    }

    /**
     * Check the current lock status
     *
     * @param  string  $pidof If passed, will be set to the value of the
     *   process ID which currently holds the lock.
     * @return boolean Will return TRUE if a lock is in place, FALSE otherwise.
     */
    public function check(&$pidof = null)
    {
        if ($this->status === self::LOCKED) {
            $pidof = $this->readLockPid();
            return true;
        }

        $locked = !flock($this->lock, LOCK_SH | LOCK_NB);
        flock($this->lock, LOCK_UN);

        $pidof = $this->readLockPid();
        return $locked;
    }

    /**
     * Read the process ID which holds the lock
     *
     * @return string|false Will return the pid as a string or FALSE on failure.
     */
    public function readLockPid()
    {
        $filename = sprintf("%s/%s.lock", $this->bucket, self::cleanName($this->name));
        $pid = @file_get_contents($filename);

        if ($pid) {
            $pid = trim($pid);
        }

        return $pid;
    }

    /**
     * Parse a filesystem-safe file name from the lock name
     *
     * @static
     * @param  string  $name
     * @return string
     */
    protected static function cleanName($name)
    {
        $name = preg_replace("/[^0-9a-z]/i", "", $name);

        return $name;
    }

}
