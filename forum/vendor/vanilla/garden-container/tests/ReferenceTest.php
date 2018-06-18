<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests;

use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\Container\Tests\Fixtures\Db;
use Garden\Container\Tests\Fixtures\Foo;
use Garden\Container\Tests\Fixtures\FooConsumer;
use Garden\Container\Tests\Fixtures\Model;
use Garden\Container\Tests\Fixtures\Sql;

/**
 * Tests involving reference instantiation.
 */
class ReferenceTest extends AbstractContainerTest {

    /**
     * References can take arguments.
     */
    public function testReferenceArgs() {
        $dic = new Container();

        $dic->rule(Model::class)
            ->setConstructorArgs(['sql' => new Reference(Sql::class, ['baz'])]);

        /* @var Model $model */
        $model = $dic->get(Model::class);
        $this->assertSame('baz', $model->sql->name);
    }

    /**
     * I should be able to pass a required parameter by ordinal reference.
     *
     * @param bool $shared Shared or factory construction.
     * @dataProvider provideShared
     */
    public function testOrdinalReferenceArg($shared) {
        $dic = (new Container())->setShared($shared);

        $r = $dic->getArgs(FooConsumer::class, [new Reference(Foo::class)]);
    }

    /**
     * I should be able to set constructor arguments by ordinal reference.
     *
     * @param bool $shared Shared or factory construction.
     * @dataProvider provideShared
     */
    public function testOrdinalConstructorReferenceArg($shared) {
        $dic = (new Container())->setShared($shared);

        $dic->rule('foo')
            ->setClass(Db::class)
            ->setShared(true)
            ->setConstructorArgs([__FUNCTION__])

            ->rule(Sql::class)
            ->setConstructorArgs([new Reference('foo')]);

        $r = $dic->get(Sql::class);

        $this->assertSame($dic->get('foo'), $r->db);
    }

    /**
     * I should be able to override reference arguments by ordinal.
     *
     * @param bool $shared Shared or factory construction.
     * @dataProvider provideShared
     */
    public function testOrdinalConstructorReferenceArgOverride($shared) {
        $dic = (new Container())->setShared($shared);

        $dic->rule('foo')
            ->setShared(true)
            ->addCall('setFoo', [__FUNCTION__])

            ->rule(FooConsumer::class)
            ->setConstructorArgs([new Reference('foo')]);

        $foo = new Foo();

        $r = $dic->getArgs(FooConsumer::class, [$foo]);
        $this->assertSame($foo, $r->foo);
    }

    /**
     * I should be able to override reference arguments by ordinal reference.
     *
     * @param bool $shared Shared or factory construction.
     * @dataProvider provideShared
     */
    public function testReferenceOverride($shared) {
        $dic = (new Container())->setShared($shared);

        $dic->rule(FooConsumer::class)
            ->setConstructorArgs([new Reference(Foo::class)]);

        $dic->setInstance('baz', new Foo());

        $r = $dic->getArgs(FooConsumer::class, [new Reference('baz')]);
        $this->assertSame($dic->get('baz'), $r->foo);
    }
}
