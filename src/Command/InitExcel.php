<?php

namespace smk_vendor\smk_excel\Command;

use File;
use Illuminate\Console\Command;
use smk_vendor\smk_excel\Temp\RouteTemp;

/**
 * Created by IntelliJ IDEA.
 * User: Yu
 * Date: 2017/10/17 0017
 * Time: 下午 5:31
 */
class InitExcel extends Command
{
    protected $signature = 'excel:init';
    protected $description = 'init import excel to your database';


    public function handle()
    {
        $version = $this->getApplication()->getVersion();
        $route_temp = new RouteTemp();
        $route_t = $route_temp->temp();
        if ($version >= 5.3) {
            //拷贝路由
            $route_path = base_path('routes/web.php');
        } else {
            $route_path = app_path('Http/routes.php');
            $this->error("your laravel version is less than 5.3,please upgrade");
        };
        $this->write($route_path, $route_t, true);
        //拷贝控制器
        $patt = dirname(__FILE__) . '/SmkVendor/SmkExcel.php';
        $dir = app_path('Http/Controllers/SmkVendor');
        if (!File::isDirectory($dir) || !File::exists($dir)) {
            File::makeDirectory($dir, $mode = 0777, $recursive = false);
        }
        File::copy($patt, app_path('Http/Controllers/SmkVendor/SmkExcel.php'));

        //拷贝视图
        $patt = dirname(__FILE__) . '/SmkVendor/Index.blade.php';
        $dir = base_path('resources/views/SmkVendor');

        if (!File::isDirectory($dir) || !File::exists($dir)) {
            File::makeDirectory($dir, $mode = 0777, $recursive = false);
        }
        $this->line($dir);
        $dir = base_path('resources/views/SmkVendor/Excel');
        if (!File::isDirectory($dir) || !File::exists($dir)) {
            File::makeDirectory($dir, $mode = 0777, $recursive = false);
        }
        $this->line($dir);
        File::copy($patt, base_path('resources/views/SmkVendor/Excel/Index.blade.php'));


        //拷贝资源文件
        $k = File::files(dirname(__FILE__) . '/SmkVendor/smkvendor');
        $xxx = public_path('smkvendor');
        if (!File::isDirectory($xxx) || !File::exists($xxx)) {
            File::makeDirectory($xxx, $mode = 0777, $recursive = false);
        }
        foreach ($k as $x) {
            $x1 = str_replace("\\", "/", $x);
            $x1 = explode("/", $x1);
            File::copy($x, $xxx . '/' . $x1[count($x1) - 1]);
        }
        $this->line("successful");

    }

    private function write($path, $content, $is_append = false)
    {
        if (!File::exists($path)) {
            $this->line("文件不存在");
            return;
        }
        if ($is_append) {
            File::append($path, $content);
        } else {
            File::put($path, $content);
        }

    }
}
