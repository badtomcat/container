<?php


class Test extends PHPUnit_Framework_TestCase
{
    /**
     * @throws Exception
     */
    public function testContainer()
    {
        $test = new \Badtomcat\Container();
        $test->bind("foo",function (cls $ins)
        {
            return $ins;
        });
        $this->assertEquals('foo',$test->make('foo')->bar);
        $test->instance("value",123);
        $this->assertEquals(123,$test->make('value'));
        $this->assertTrue($test->bound("foo"));
        $test->bind("bar",cls::class);
        $this->assertInstanceOf(cls::class,$test->make("bar"));
    }

    public function testArrGet()
    {
        $test = new \Badtomcat\Container();
        $test->instance("value",123);
        $this->assertEquals(123,$test['value']);
    }

    public function testcallMethod()
    {
        $test = new \Badtomcat\Container();
        $ret = $test->callMethod(cls::class,"action");
        $this->assertEquals("zzz",$ret);
    }
}
class cls
{
    public $bar = 'foo';
    public function action()
    {
        return "zzz";
    }
}

