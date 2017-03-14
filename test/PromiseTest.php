<?php

namespace Amp\Test;

use Amp\Loop;
use PHPUnit\Framework\TestCase;

class Promise implements \Amp\Promise {
    use \Amp\Internal\Placeholder {
        resolve as public;
        fail as public;
    }
}

class PromiseTest extends TestCase {
    private $originalErrorHandler;

    /**
     * A Promise to use for a test with resolution methods.
     * Note that the callables shall take care of the Promise being resolved in any case. Example: The actual implementation delays resolution to the next loop tick. The callables then must run one tick of the loop in order to ensure resolution.
     *
     * @return array(Promise, callable, callable) where the last two callables are resolving the Promise with a result or a Throwable/Exception respectively
     */
    public function promise() {
        $promise = new Promise;
        return [
            $promise,
            [$promise, 'resolve'],
            [$promise, 'fail'],
        ];
    }

    function setUp() {
        $this->originalErrorHandler = Loop::setErrorHandler(function ($e) {
            throw $e;
        });
    }

    function tearDown() {
        Loop::setErrorHandler($this->originalErrorHandler);
    }

    function provideSuccessValues() {
        return [
            ["string"],
            [0],
            [~PHP_INT_MAX],
            [-1.0],
            [true],
            [false],
            [[]],
            [null],
            [new \StdClass],
            [new \Exception],
        ];
    }

    function testPromiseImplementsPromise()
    {
        list($promise) = $this->promise();
        $this->assertInstanceOf(Promise::class, $promise);
    }

    /** @dataProvider provideSuccessValues */
    function testPromiseSucceed($value)
    {
        list($promise, $succeeder) = $this->promise();
        $promise->onResolve(function($e, $v) use (&$invoked, $value) {
            $this->assertSame(null, $e);
            $this->assertSame($value, $v);
            $invoked = true;
        });
        $succeeder($value);
        $this->assertTrue($invoked);
    }

    /** @dataProvider provideSuccessValues */
    function testWhenOnSucceededPromise($value) {
        list($promise, $succeeder) = $this->promise();
        $succeeder($value);
        $promise->onResolve(function($e, $v) use (&$invoked, $value) {
            $this->assertSame(null, $e);
            $this->assertSame($value, $v);
            $invoked = true;
        });
        $this->assertTrue($invoked);
    }

    function testSuccessAllWhensExecuted() {
        list($promise, $succeeder) = $this->promise();
        $invoked = 0;

        $promise->onResolve(function($e, $v) use (&$invoked) {
            $this->assertSame(null, $e);
            $this->assertSame(true, $v);
            $invoked++;
        });
        $promise->onResolve(function($e, $v) use (&$invoked) {
            $this->assertSame(null, $e);
            $this->assertSame(true, $v);
            $invoked++;
        });

        $succeeder(true);

        $promise->onResolve(function($e, $v) use (&$invoked) {
            $this->assertSame(null, $e);
            $this->assertSame(true, $v);
            $invoked++;
        });
        $promise->onResolve(function($e, $v) use (&$invoked) {
            $this->assertSame(null, $e);
            $this->assertSame(true, $v);
            $invoked++;
        });

        $this->assertSame(4, $invoked);
    }

    function testPromiseExceptionFailure() {
        list($promise, , $failer) = $this->promise();
        $promise->onResolve(function ($e) use (&$invoked) {
            $this->assertSame(get_class($e), "RuntimeException");
            $invoked = true;
        });
        $failer(new \RuntimeException);
        $this->assertTrue($invoked);
    }

    function testWhenOnExceptionFailedPromise() {
        list($promise, , $failer) = $this->promise();
        $failer(new \RuntimeException);
        $promise->onResolve(function ($e) use (&$invoked) {
            $this->assertSame(get_class($e), "RuntimeException");
            $invoked = true;
        });
        $this->assertTrue($invoked);
    }

    function testFailureAllWhensExecuted() {
        list($promise, , $failer) = $this->promise();
        $invoked = 0;

        $promise->onResolve(function ($e) use (&$invoked) {
            $this->assertSame(get_class($e), "RuntimeException");
            $invoked++;
        });
        $promise->onResolve(function ($e) use (&$invoked) {
            $this->assertSame(get_class($e), "RuntimeException");
            $invoked++;
        });

        $failer(new \RuntimeException);

        $promise->onResolve(function ($e) use (&$invoked) {
            $this->assertSame(get_class($e), "RuntimeException");
            $invoked++;
        });
        $promise->onResolve(function ($e) use (&$invoked) {
            $this->assertSame(get_class($e), "RuntimeException");
            $invoked++;
        });

        $this->assertSame(4, $invoked);
    }

    function testPromiseErrorFailure() {
        if (PHP_VERSION_ID < 70000) {
            $this->markTestSkipped("Error only exists on PHP 7+");
        }

        list($promise, , $failer) = $this->promise();
        $promise->onResolve(function ($e) use (&$invoked) {
            $this->assertSame(get_class($e), "Error");
            $invoked = true;
        });
        $failer(new \Error);
        $this->assertTrue($invoked);
    }

    function testWhenOnErrorFailedPromise() {
        if (PHP_VERSION_ID < 70000) {
            $this->markTestSkipped("Error only exists on PHP 7+");
        }

        list($promise, , $failer) = $this->promise();
        $failer(new \Error);
        $promise->onResolve(function ($e) use (&$invoked) {
            $this->assertSame(get_class($e), "Error");
            $invoked = true;
        });
        $this->assertTrue($invoked);
    }

    /** Implementations MAY fail upon resolution with a Promise, but they definitely MUST NOT return a Promise */
    function testPromiseResolutionWithPromise() {
        list($success, $succeeder) = $this->promise();
        $succeeder(true);

        list($promise, $succeeder) = $this->promise();

        $ex = false;
        try {
            $succeeder($success);
        } catch (\Throwable $e) {
            $ex = true;
        } catch (\Exception $e) {
            $ex = true;
        }
        if (!$ex) {
            $promise->onResolve(function ($e, $v) use (&$invoked) {
                $invoked = true;
                $this->assertFalse($v instanceof Promise);
            });
            $this->assertTrue($invoked);
        }
    }

    function testThrowingInCallback() {
        Loop::run(function () {
            $invoked = 0;

            Loop::setErrorHandler(function () use (&$invoked) {
                $invoked++;
            });

            list($promise, $succeeder) = $this->promise();
            $succeeder(true);
            $promise->onResolve(function ($e, $v) use (&$invoked, $promise) {
                $this->assertSame(null, $e);
                $this->assertSame(true, $v);
                $invoked++;

                throw new \Exception;
            });

            list($promise, $succeeder) = $this->promise();
            $promise->onResolve(function ($e, $v) use (&$invoked, $promise) {
                $this->assertSame(null, $e);
                $this->assertSame(true, $v);
                $invoked++;

                throw new \Exception;
            });
            $succeeder(true);

            $this->assertEquals(4, $invoked);
        });
    }

    function testThrowingInCallbackContinuesOtherWhens() {
        Loop::run(function () {
            $invoked = 0;

            Loop::setErrorHandler(function () use (&$invoked) {
                $invoked++;
            });

            list($promise, $succeeder) = $this->promise();
            $promise->onResolve(function ($e, $v) use (&$invoked, $promise) {
                $this->assertSame(null, $e);
                $this->assertSame(true, $v);
                $invoked++;

                throw new \Exception;
            });
            $promise->onResolve(function ($e, $v) use (&$invoked, $promise) {
                $this->assertSame(null, $e);
                $this->assertSame(true, $v);
                $invoked++;
            });
            $succeeder(true);

            $this->assertEquals(3, $invoked);
        });
    }

    function testThrowingInCallbackOnFailure() {
        Loop::run(function () {
            $invoked = 0;
            Loop::setErrorHandler(function () use (&$invoked) {
                $invoked++;
            });

            list($promise, , $failer) = $this->promise();
            $exception = new \Exception;
            $failer($exception);
            $promise->onResolve(function ($e, $v) use (&$invoked, $exception) {
                $this->assertSame($exception, $e);
                $this->assertNull($v);
                $invoked++;

                throw $e;
            });

            list($promise, , $failer) = $this->promise();
            $exception = new \Exception;
            $promise->onResolve(function ($e, $v) use (&$invoked, $exception) {
                $this->assertSame($exception, $e);
                $this->assertNull($v);
                $invoked++;

                throw $e;
            });
            $failer($exception);

            $this->assertEquals(4, $invoked);
        });
    }

    /**
     * @requires PHP 7
     */
    function testWeakTypes() {
        $invoked = 0;
        list($promise, $succeeder) = $this->promise();

        $expectedData = "15.24";

        $promise->onResolve(function($e, int $v) use (&$invoked, $expectedData) {
            $invoked++;
            $this->assertSame((int) $expectedData, $v);
        });
        $succeeder($expectedData);
        $promise->onResolve(function($e, int $v) use (&$invoked, $expectedData) {
            $invoked++;
            $this->assertSame((int) $expectedData, $v);
        });

        $this->assertEquals(2, $invoked);
    }

    public function testWhenQueueUnrolling() {
        $count = 50;
        $invoked = false;

        $promise = new Promise;
        $promise->onResolve(function () { });
        $promise->onResolve(function () { });
        $promise->onResolve(function () use (&$invoked) {
            $invoked = true;
            $this->assertLessThan(30, count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)));
        });

        $last = $promise;

        $f = function () use (&$f, &$count, &$last) {
            $p = new Promise;
            $p->onResolve(function () { });
            $p->onResolve(function () { });

            $last->resolve($p);
            $last = $p;

            if (--$count > 0) {
                $f();
            }
        };

        $f();
        $last->resolve();

        $this->assertTrue($invoked);
    }
}