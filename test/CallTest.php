<?php

namespace Amp\Test;

use Amp;
use Amp\Coroutine;
use Amp\Success;
use Amp\Promise;

class CallTest extends \PHPUnit\Framework\TestCase {
    public function testCallWithFunctionReturningPromise() {
        $value = 1;
        $promise = Amp\call(function ($value) {
            return new Success($value);
        }, $value);

        $this->assertInstanceOf(Promise::class, $promise);

        $promise->onResolve(function ($exception, $value) use (&$reason, &$result) {
            $reason = $exception;
            $result = $value;
        });

        $this->assertNull($reason);
        $this->assertSame($value, $result);
    }

    public function testCallWithFunctionReturningValue() {
        $value = 1;
        $promise = Amp\call(function ($value) {
            return $value;
        }, $value);

        $this->assertInstanceOf(Promise::class, $promise);

        $promise->onResolve(function ($exception, $value) use (&$reason, &$result) {
            $reason = $exception;
            $result = $value;
        });

        $this->assertNull($reason);
        $this->assertSame($value, $result);
    }

    public function testCallWithThrowingFunction() {
        $exception = new \Exception;
        $promise = Amp\call(function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Promise::class, $promise);

        $promise->onResolve(function ($exception, $value) use (&$reason, &$result) {
            $reason = $exception;
            $result = $value;
        });

        $this->assertSame($exception, $reason);
        $this->assertNull($result);
    }

    public function testCallWithGeneratorFunction() {
        $value = 1;
        $promise = Amp\call(function ($value) {
            return yield new Success($value);
        }, $value);

        $this->assertInstanceOf(Coroutine::class, $promise);

        $promise->onResolve(function ($exception, $value) use (&$reason, &$result) {
            $reason = $exception;
            $result = $value;
        });

        $this->assertNull($reason);
        $this->assertSame($value, $result);
    }
}
