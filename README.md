# container
IOC服务容器管理组件

> composer require badtomcat/container

```php
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
```
> [] 操作符执行的是make和bind