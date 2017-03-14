<?php

namespace Amp\Test;

use Amp\Loop;
use Amp\Promise;

class Placeholder {
    use \Amp\Internal\Placeholder {
        resolve as public;
        fail as public;
    }
}

class PlaceholderTraitTest extends \PHPUnit\Framework\TestCase {
    /** @var \Amp\Test\Placeholder */
    private $placeholder;

    public function setUp() {
        $this->placeholder = new Placeholder;
    }

    public function testWhenOnSuccess() {
        $value = "Resolution value";

        $invoked = 0;
        $callback = function ($exception, $value) use (&$invoked, &$result) {
            ++$invoked;
            $result = $value;
        };

        $this->placeholder->onResolve($callback);

        $this->placeholder->resolve($value);

        $this->assertSame(1, $invoked);
        $this->assertSame($value, $result);
    }

    /**
     * @depends testWhenOnSuccess
     */
    public function testMultipleWhensOnSuccess() {
        $value = "Resolution value";

        $invoked = 0;
        $callback = function ($exception, $value) use (&$invoked, &$result) {
            ++$invoked;
            $result = $value;
        };

        $this->placeholder->onResolve($callback);
        $this->placeholder->onResolve($callback);
        $this->placeholder->onResolve($callback);

        $this->placeholder->resolve($value);

        $this->assertSame(3, $invoked);
        $this->assertSame($value, $result);
    }

    /**
     * @depends testWhenOnSuccess
     */
    public function testWhenAfterSuccess() {
        $value = "Resolution value";

        $invoked = 0;
        $callback = function ($exception, $value) use (&$invoked, &$result) {
            ++$invoked;
            $result = $value;
        };

        $this->placeholder->resolve($value);

        $this->placeholder->onResolve($callback);

        $this->assertSame(1, $invoked);
        $this->assertSame($value, $result);
    }

    /**
     * @depends testWhenAfterSuccess
     */
    public function testMultipleWhenAfterSuccess() {
        $value = "Resolution value";

        $invoked = 0;
        $callback = function ($exception, $value) use (&$invoked, &$result) {
            ++$invoked;
            $result = $value;
        };

        $this->placeholder->resolve($value);

        $this->placeholder->onResolve($callback);
        $this->placeholder->onResolve($callback);
        $this->placeholder->onResolve($callback);

        $this->assertSame(3, $invoked);
        $this->assertSame($value, $result);
    }

    /**
     * @depends testWhenOnSuccess
     */
    public function testWhenThrowingForwardsToLoopHandlerOnSuccess() {
        Loop::run(function () use (&$invoked) {
            $invoked = 0;
            $expected = new \Exception;

            Loop::setErrorHandler(function ($exception) use (&$invoked, $expected) {
                ++$invoked;
                $this->assertSame($expected, $exception);
            });

            $callback = function () use ($expected) {
                throw $expected;
            };

            $this->placeholder->onResolve($callback);

            $this->placeholder->resolve($expected);
        });

        $this->assertSame(1, $invoked);
    }

    /**
     * @depends testWhenAfterSuccess
     */
    public function testWhenThrowingForwardsToLoopHandlerAfterSuccess() {
        Loop::run(function () use (&$invoked) {
            $invoked = 0;
            $expected = new \Exception;

            Loop::setErrorHandler(function ($exception) use (&$invoked, $expected) {
                ++$invoked;
                $this->assertSame($expected, $exception);
            });

            $callback = function () use ($expected) {
                throw $expected;
            };

            $this->placeholder->resolve($expected);

            $this->placeholder->onResolve($callback);
        });

        $this->assertSame(1, $invoked);
    }

    public function testWhenOnFail() {
        $exception = new \Exception;

        $invoked = 0;
        $callback = function ($exception, $value) use (&$invoked, &$result) {
            ++$invoked;
            $result = $exception;
        };

        $this->placeholder->onResolve($callback);

        $this->placeholder->fail($exception);

        $this->assertSame(1, $invoked);
        $this->assertSame($exception, $result);
    }

    /**
     * @depends testWhenOnFail
     */
    public function testMultipleWhensOnFail() {
        $exception = new \Exception;

        $invoked = 0;
        $callback = function ($exception, $value) use (&$invoked, &$result) {
            ++$invoked;
            $result = $exception;
        };

        $this->placeholder->onResolve($callback);
        $this->placeholder->onResolve($callback);
        $this->placeholder->onResolve($callback);

        $this->placeholder->fail($exception);

        $this->assertSame(3, $invoked);
        $this->assertSame($exception, $result);
    }

    /**
     * @depends testWhenOnFail
     */
    public function testWhenAfterFail() {
        $exception = new \Exception;

        $invoked = 0;
        $callback = function ($exception, $value) use (&$invoked, &$result) {
            ++$invoked;
            $result = $exception;
        };

        $this->placeholder->fail($exception);

        $this->placeholder->onResolve($callback);

        $this->assertSame(1, $invoked);
        $this->assertSame($exception, $result);
    }

    /**
     * @depends testWhenAfterFail
     */
    public function testMultipleWhensAfterFail() {
        $exception = new \Exception;

        $invoked = 0;
        $callback = function ($exception, $value) use (&$invoked, &$result) {
            ++$invoked;
            $result = $exception;
        };

        $this->placeholder->fail($exception);

        $this->placeholder->onResolve($callback);
        $this->placeholder->onResolve($callback);
        $this->placeholder->onResolve($callback);

        $this->assertSame(3, $invoked);
        $this->assertSame($exception, $result);
    }

    /**
     * @depends testWhenOnSuccess
     */
    public function testWhenThrowingForwardsToLoopHandlerOnFail() {
        Loop::run(function () use (&$invoked) {
            $invoked = 0;
            $expected = new \Exception;

            Loop::setErrorHandler(function ($exception) use (&$invoked, $expected) {
                ++$invoked;
                $this->assertSame($expected, $exception);
            });

            $callback = function () use ($expected) {
                throw $expected;
            };

            $this->placeholder->onResolve($callback);

            $this->placeholder->fail(new \Exception);
        });

        $this->assertSame(1, $invoked);
    }

    /**
     * @depends testWhenOnSuccess
     */
    public function testWhenThrowingForwardsToLoopHandlerAfterFail() {
        Loop::run(function () use (&$invoked) {
            $invoked = 0;
            $expected = new \Exception;

            Loop::setErrorHandler(function ($exception) use (&$invoked, $expected) {
                ++$invoked;
                $this->assertSame($expected, $exception);
            });

            $callback = function () use ($expected) {
                throw $expected;
            };

            $this->placeholder->fail(new \Exception);

            $this->placeholder->onResolve($callback);
        });

        $this->assertSame(1, $invoked);
    }

    public function testResolveWithPromiseBeforeWhen() {
        $promise = $this->getMockBuilder(Promise::class)->getMock();

        $promise->expects($this->once())
            ->method("when")
            ->with($this->callback("is_callable"));

        $this->placeholder->resolve($promise);

        $this->placeholder->onResolve(function () {});
    }

    public function testResolveWithPromiseAfterWhen() {
        $promise = $this->getMockBuilder(Promise::class)->getMock();

        $promise->expects($this->once())
            ->method("when")
            ->with($this->callback("is_callable"));

        $this->placeholder->onResolve(function () {});

        $this->placeholder->resolve($promise);
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage Promise has already been resolved
     */
    public function testDoubleResolve() {
        $this->placeholder->resolve();
        $this->placeholder->resolve();
    }

    /**
     * @expectedException \Error
     * @expectedExceptionMessage Promise has already been resolved
     */
    public function testResolveAgainWithinWhenCallback() {
        Loop::run(function () {
            $this->placeholder->onResolve(function () {
                $this->placeholder->resolve();
            });

            $this->placeholder->resolve();
        });
    }
}
