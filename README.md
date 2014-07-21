## CodeIgniter Framework Extend ORM Model 

自带Model功能太简单，使用起来比较不方便，重新封装个扩展模型，支持链式调用，支持动态字段查询。



ORM 查询：
$this->field('username,password,email')->where("username = 'abc'")->limit(10)->findAll();

等于 SQL 查询：
SELECT username,password,email FROM {table} where `username` = 'abc' limit 10;


@todo 待更新使用教程