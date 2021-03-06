<?php


class Test extends PHPUnit_Framework_TestCase
{
    /**
     * @throws Exception
     */
    public function testContainer()
    {
        $test = new \Badtomcat\Container();
        $test->bind("foo",function (clssss $ins)
        {
            return $ins;
        });
        $this->assertEquals('foo',$test->make('foo')->bar);
        $test->instance("value",123);
        $this->assertEquals(123,$test->make('value'));
        $this->assertTrue($test->bound("foo"));
        $test->bind("bar",clssss::class);
        $this->assertInstanceOf(clssss::class,$test->make("bar"));
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
        $ret = $test->callMethod(clssss::class,"action");
        $this->assertEquals("zzz",$ret);
    }

    /**
     * @throws Exception
     */
    public function testCallEnv()
    {
        $env = [
            "foo" => "bar"
        ];
        $test = new \Badtomcat\Container();
        $f = (new \ReflectionMethod( "abc", "ca" ));

        $ret = $test->getDependencies($f->getParameters(), $env);
        $z = $f->invokeArgs($test->build("abc"), $ret);
        $this->assertEquals("foo-bar",$z);
    }
}
class clssss
{
    public $bar = 'foo';
    public function action()
    {
        return "zzz";
    }
}
class abc
{
    public function ca(clssss $cls,$foo)
    {
        return $cls->bar . "-" . $foo;
    }
}
