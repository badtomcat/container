<?php


class ExTest extends PHPUnit_Framework_TestCase
{
    /**
     * @throws Exception
     */
    public function testContainer()
    {
        $test = new \Badtomcat\Container();
        $c = $test->build(ea::class);
        var_dump($c->aa());
    }

    /**
     * @throws Exception
     */
    public function testReArg()
    {
        $test = new \Badtomcat\Container();
        $test->instance(cls::class,new cls());
        $reflectionMethod = new \ReflectionMethod(ea::class, "aa");
        //解析方法参数
        $args = $test->getDependencies($reflectionMethod->getParameters());
        //生成类并执行方法
        $content =  $reflectionMethod->invokeArgs($test->build(ea::class), $args);
        var_dump($content);
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
class a
{
    protected $bb;
    public function __construct(cls $cls,$foo = 'c')
    {
        $this->bb = $cls->bar . "-" . $foo;
    }
}

class ea extends a
{
    public function aa()
    {
        return $this->bb;
    }
}