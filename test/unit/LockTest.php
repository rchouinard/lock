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

use PHPUnit_Framework_TestCase as TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Lock test
 *
 * Tests for the Lock class.
 */
class LockTest extends TestCase
{

    /**
     * @var \org\bovigo\vfs\vfsStream
     */
    protected $vfs;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->vfs = vfsStream::setup("tests");
    }

    /**
     * Test that the lock file is created
     *
     * @test
     */
    public function testConstructorCreatesLockFile()
    {
        $this->assertFalse($this->vfs->hasChild("test.lock"));
        $lock = new Lock("test", vfsStream::url("tests"));
        $this->assertTrue($this->vfs->hasChild("test.lock"));
    }

    /**
     * Test that a fresh Lock is not in lock state
     *
     * @test
     */
    public function testNewInstanceIsNotLocked()
    {
        $lock = new Lock("test", vfsStream::url("tests"));
        $this->assertFalse($lock->check());
    }

    /**
     * Test that a single lock can only be acquired once
     *
     * @test
     */
    public function testTwoInstancesCannotAcquireSameLock()
    {
        $lock1 = new Lock("test", vfsStream::url("tests"));
        $lock2 = new Lock("test", vfsStream::url("tests"));

        $this->assertTrue($lock1->lock());
        $this->assertFalse($lock2->lock());
    }

    /**
     * Test that unlocking removes the lock
     *
     * @test
     */
    public function testUnlockReleasesLock()
    {
        $lock1 = new Lock("test", vfsStream::url("tests"));
        $lock2 = new Lock("test", vfsStream::url("tests"));

        $this->assertTrue($lock1->lock());
        $this->assertTrue($lock2->check());

        $this->assertTrue($lock1->unlock());
        $this->assertFalse($lock2->check());
    }

}
