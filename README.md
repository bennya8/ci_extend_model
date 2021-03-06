# CodeIgniter Framework Extend ORM Model 

CI Model功能比较简单，使用起来比较不方便，重新封装个扩展模型，支持链式调用，支持动态字段查询，快速主键查询。

## 使用方法

拷贝 MY_Model.php 文件到 /ci_project/app/core/ 下，
要使用扩展模型，在新建表模式时继承 MY_Model 代替以往的 CI_Model，覆写父类中几个属性即可完成配置。

如：购物车模型

```php
class cart_model extends MY_Model
{
	// 主键名称
	public $id = 'id';
	// 表名
	public $table = 'cart';
	// 表前缀
	public $tablePrefix = 'pre_';
}
```

## 增删改查 CRUD

假设现有如下数据表：
```shell
Table user
id INT PK AI
username VARCHAR
password VARCHAR
email VARCHAR
member_level SMALLINT

Table user_data
id INT PK AI
user_id INT FK
birthday VARCHAR
mobile VARCHAR
address VARCHAR
zipcode VARCHAR

```

## 查询 SELECT

***(bool/array) find($id) 方法，根据主键找到一条记录***

例子1 find方法查询
（可配合 field() 链式方法使用）
```php
$this->find(1);
```
```sql
SELECT * FROM User where id = '1' limit 1;
```
例子2 可以直接设定查询条件后，用findAll方法查询，等效于例子1查询
```php
$condition['field'] = 'username,password,email';
$condition['where'] = "username = 'abc'";
$condition['limit'] = 10;
$this->getAll($condition);
```

***(bool/array) findAll($condition) ，根据设置查询条件查询多条记录，支持链式调用。***

例子1 使用链式方法和findAll方法查询
（可配合 field(),table(),where(),join(),group(),limit(),order() 链式方法使用）
```php
$this->field('username,password,email')->where("username = 'abc'")->limit(10)->findAll();
```
```sql
SELECT username,password,email FROM pre_user where `username` = 'abc' limit 10;
```
```php
$this->field('count(member_level)')->group('member_level')->findAll();
```

```sql
SELECT count(member_level) FROM pre_user GROUP BY member_level;
```
```php
$this->field('u.username,ud.birthday,ud.mobile')
->table('pre_user as u')
->join('left join pre_user_data as ud')
->where("u.member_level = '1'")
->limit(20)
->order('u.username DESC')
->findAll();
```
```sql
SELECT u.username.ud.birthday,ud.mobile 
LEFT JOIN pre_user_data AS ud
WHERE u.member_levle = '1'
LIMIT 20
ORDER BY u.username DESC;
```

例子2 可以直接设定查询条件后，使用findAll方法查询，等效于例子1查询
```php
$condition['field'] = 'username,password,email';
$condition['where'] = "username = 'abc'";
$condition['limit'] = 10;
$this->findAll($condition);
```

***(bool/array) select($condition)方法，等效于findAll()方法，注意的是，select方法不会设定默认表名称。***

例子：
```php
$this->table('pre_user')->select();
```
```sql
SELECT * FROM pre_user;
```

***(bool/array) getByXXX($value) 动态字段查询，使用当前表某列字段作为查询条件，查询方法名使用驼峰法***

例子：
```php
$this->getById(2);
$this->getByMemberLevel(1);
```
```sql
SELECT * FROM pre_user where `id` = '2';
SELECT * FROM pre_user where `member_level` = '2';
```

## 插入 INSERT

***(bool/array) add($data) 插入一条或多条数据***

例子：
```php
$data[] = array(
	'username' => 'cartman',
	'password' => 'cartman password'
);
$data[] = array(
	'username' => 'stan',
	'password' => 'stan password'
);
$this->add(data);
```
```sql
INSERT INTO pre_user (username,password) VALUES ('cartman','artman password'),('stan','stan password');
```

## 更新 UPDATE

***(bool/array) save($data,$where) 更新一条或多条数据***

例子：
```php
$data = array(
	'member_level' => 999
);
$where = array(
	'id' => array('eq','1','and'),
	'username' => array('eq','cartman')
);
$this->save($data,$where);

$this->where($where)->save($data);

$this->where("id = '1' AND username = 'cartman'")->save($data);

```
```sql
UPDATE SET pre_user `member_level` = '999'
WHERE `id` = '1' AND `username` = 'cartman';
```

## 删除 DELETE

***(bool/array) remove($where) 删除一条或多条数据***

例子：
```php
$where = array(
	'id' => 'cartman'
);

$this->remove($where);

$this->where($where)->remove();

$this->where("id = 'cartman'")->remove();
```
```sql
DELETE FROM pre_user WHERE `id` = 'cartman';
```

## SQL语句查询

***(bool/array) query($sql) 执行一条query语句查询，返回结果集***

***(bool/array) execute($sql) 执行一条execute语句，返回受影响行数***

***(int) lastInsertId() 获取最新插入ID***

***(int) affectedRows() 获取最新一次查询受影响行数***

## 事务（注：事务需表引擎支持）

***(bool) begin() 关闭自动提交，开启事务***

***(bool) commit() 提交一个事务***

***(bool) rollback() 回滚一个事务***








