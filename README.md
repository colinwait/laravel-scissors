## 图片上传服务（laravel扩展包）

> 服务说明：用于程序内部上传图片的服务，支持本地存储和七牛云存储


#### 安装

	composer require colinwait/laravel-scissors

在 `config/app.php` 的 `providers` 数组中添加 ：

	Colinwait\LaravelScissors\ScissorProvider::class,

在 `config/app.php` 的 `aliases` 数组中添加 ：

	'Scissor' => \Colinwait\LaravelScissors\Scissor::class,
	
执行以下命令导出配置文件和数据库迁移 ：

	php artisan vendor:publish
	
配置 `config/scissor.php`

执行数据库迁移 ：

	php artisan migrate
	
#### 使用方法

使用门面

	use Colinwait\LaravelScissors\Scissor;
	
#### 方法说明

本地上传图片 (文件路径或base64格式)

	Scissor::putFile($path, $key = null);
	
url上传图片

	Scissor::fetch($url, $key = null);
	
base64上传
	
	Scissor::put($data, $key = null);
	
获取图片信息

	Scissor::get($key);
	
删除图片

	Scissor::delete($key);