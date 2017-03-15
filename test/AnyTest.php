<?php

namespace Amp\Test;

use Amp\Failure;
use Amp\Pause;
use Amp\Promise;
use Amp\Success;
use Amp\Loop;

class AnyTest extends \PHPUnit\Framework\TestCase {
    public function testEmptyArray() {
        $callback = function ($exception, $value) use (&$result) {
            $result = $value;
        };

        Promise\any([])->when($callback);

        $this->assertSame([[], []], $result);
    }

    public function testSuccessfulPromisesArray() {
        $promises = [new Success(1), new Success(2), new Success(3)];

        $callback = function ($exception, $value) use (&$result) {
            $result = $value;
        };

        Promise\any($promises)->when($callback);

        $this->assertEquals([[], [1, 2, 3]], $result);
    }

    public function testFailedPromisesArray() {
        $exception = new \Exception;
        $promises = [new Failure($exception), new Failure($exception), new Failure($exception)];

        $callback = function ($exception, $value) use (&$result) {
            $result = $value;
        };

        Promise\any($promises)->when($callback);

        $this->assertEquals([[$exception, $exception, $exception], []], $result);
    }

    public function testMixedPromisesArray() {
        $exception = new \Exception;
        $promises = [new Success(1), new Failure($exception), new Success(3)];

        $callback = function ($exception, $value) use (&$result) {
            $result = $value;
        };

        Promise\any($promises)->when($callback);

        $this->assertEquals([[1 => $exception], [0 => 1, 2 => 3]], $result);
    }

    public function testPendingAwatiablesArray() {
        Loop::run(function () use (&$result) {
            $promises = [
                new Pause(20, 1),
                new Pause(30, 2),
                new Pause(10, 3),
            ];

            $callback = function ($exception, $value) use (&$result) {
                $result = $value;
            };

            Promise\any($promises)->when($callback);
        });

        $this->assertEquals([[], [1, 2, 3]], $result);
    }

    /**
     * @depends testMixedPromisesArray
     */
    public function testArrayKeysPreserved() {
        $exception = new \Exception;
        $expected = [['two' => $exception], ['one' => 1, 'three' => 3]];

        Loop::run(function () use (&$result, $exception) {
            $promises = [
                'one'   => new Pause(20, 1),
                'two'   => new Failure($exception),
                'three' => new Pause(10, 3),
            ];

            $callback = function ($exception, $value) use (&$result) {
                $result = $value;
            };

            Promise\any($promises)->when($callback);
        });

        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \Amp\UnionTypeError
     */
    public function testNonPromise() {
        Promise\any([1]);
    }
}
