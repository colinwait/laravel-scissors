## 图片上传服务（laravel扩展包）

> 服务说明：用于程序内部上传图片的服务，支持本地存储和七牛云存储


#### 安装

	composer require colinwait/laravel-pockets

在 `config/app.php` 的 `providers` 数组中添加 ：

	Colinwait\LaravelPockets\PocketProvider::class,

在 `config/app.php` 的 `aliases` 数组中添加 ：

	'Pocket' => \Colinwait\LaravelPockets\Pocket::class,
	
执行以下命令导出配置文件和数据库迁移 ：

	php artisan vendor:publish
	
配置 `config/pocket.php`

执行数据库迁移 ：

	php artisan migrate
	
#### 使用方法

使用门面

	use Colinwait\LaravelPockets\Pocket;
	
#### 方法说明

本地上传图片 (支持所有资源类型)

	Pocket::upload($path, $key = null);
	
本地上传附件 

	Pocket::uploadMaterial(UploadedFile $file);
	
本地上传图片 (支持所有资源类型)

	Pocket::uploadVideo(UploadedFile $file);
	
获取图片信息

	Pocket::get($key);
	
删除图片

	Pocket::delete($key);