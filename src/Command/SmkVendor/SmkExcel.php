<?php

namespace App\Http\Controllers\SmkVendor;

use anlutro\cURL\cURL;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Request as r;
use File;
use App;
use Log;
use Cache;

class SmkExcel extends Controller
{
    const view_path = 'SmkVendor.Excel.';
    const cache_key = 'excel_export_data';

    public function index(Request $req)
    {
        //dump($req->session()->all());
        //dump($req->cookie('laravel_session'));
        $config_url = $req->input('route', null);
        $sub_url = $req->input('subroute', null);
        if (null == $config_url) {
            echo '配置信息错误,请参见调用文档';
            die;
        }
        if (null == $sub_url) {
            echo '配置信息错误,请参见调用文档';
            die;
        }
        $data = json_decode($this->ajax(route($config_url)));
        return $this->see_View('Index', array(
            'urx' => $config_url,
            'cfg' => $data,
            'sub' => $sub_url
        ));
    }

    public function sub_excel(Request $req)
    {
        $excelName = "excel";
        if ($req->hasFile($excelName) && $req->file($excelName)->isValid()) {
            $file = r::file($excelName);
            $ext = $file->getClientOriginalExtension();
            $filename = time() . uniqid() . '.' . $ext;
            $file->storeAs($excelName, $filename);
            static $return_arra = [];
            $filePath = storage_path('app/' . $excelName . '/') . $filename;
            if (File::exists($filePath)) {
                $excel = App::make('excel');
                $return_arra = $excel->load($filePath);
                $return_arra = $return_arra->getSheet(0);//excel第一张sheet
                $return_arra = $return_arra->toArray();
            }
            return $this->bk_json(array(
                'data' => $return_arra[0],
                'file' => $filename
            ));
        } else {
            return $this->bk_json(null, -1, "请上传文件");
        }
    }

    public function resolution(Request $req)
    {
        $xsl = $req->input('fx', null);//获取xsl的路径
        $url = $req->input('cfg_url');//获取参数的url
        $relation = json_decode($req->input('ax'));//获取用户选择的关联

        //Log::info($req->all());

        $data = json_decode($this->ajax(route($url)));//获取调用方的配置
        $dx2 = collect($data);
        foreach ($dx2 as $key => $dt) {
            foreach ($relation as $re) {
                if ($re->b == $dt->id) {
                    $dx2->forget($key);
                }
            }
        }
        foreach ($dx2 as $d) {
            if (!$d->can_be_null) {
                return $this->bk_json(null, -1, $d->chinese . "必须要导入");
            }
        }
        //获取excel里面的详细内容
        $xsl = storage_path('app/excel/') . $xsl;

        $reader = Excel::selectSheetsByIndex(0)->load($xsl);
        $reader->setDateFormat('Y-m-d H:i:s');
        $excel_data = $reader->all()->toArray();
        //Log::info($excel_data);
        //按照用户选定的关联开始解析数据
        $data_for_return = array();//定义需要返回的数组
        $err_array = collect(); //定义一个报错的错误数组,所有有问题的数据都存入这个数组


        foreach ($excel_data as $excel_data_key => $ex_data) {
            //$ex_data为每一行的所有数据
            $hold_on = true;
            $arr = collect();
            $xxx = collect();
            foreach ($ex_data as $ex_key => $ex) {
                //这个循环是要判断这个关联是否在用户指定的关联之中
                $has_err = false;
                foreach ($relation as $rel) {
                    if ($rel->a == $ex_key) {
                        //如果在用户指定的数据里面,则需要去判断调用时候定义的规则
                        foreach ($data as $d) {
                            if ($d->id == $rel->b) {
                                //开始进入验证规则
                                if (!empty($d->self_verify)) {
                                    //如果这个字段开启了自定义规则则交给调用方自定义验证
                                    $self_ver_data = $this->ajax($d->self_verify,[$d->id=>$ex]);
                                    $self_ver_data = json_decode($self_ver_data);
                                    if ($self_ver_data->code != 0) {
                                        $ex_datax['msg'] = $self_ver_data->msg;
                                        foreach ($data as $d) {
                                            if ($d->id == $self_ver_data->id) {
                                                foreach ($relation as $r) {
                                                    if ($r->b == $self_ver_data->id) {
                                                        $ex_datax['key'] = $r->a;
                                                    }
                                                }
                                            };
                                        }
                                        $x = array($ex_datax);
                                        $err_array->push($x);
                                        break;
                                    }
                                } else {
                                    $ex_datax = array('arr' => $ex_data, 'key' => $ex_key);
                                    //是否为空
                                    if (!$d->can_be_null && empty($ex)) {
                                        $ex_datax['msg'] = '不能为空';
                                        $xxx->push($ex_datax);
                                        $has_err = true;
                                        break;
                                    };
                                    //验证调用方的正则表达式
                                    if (!empty($d->preg)) {
                                        try {
                                            if (!preg_match($d->preg, $ex)) {
                                                $ex_datax['msg'] = $d->preg_err_msg;
                                                $xxx->push($ex_datax);
                                                $has_err = true;
                                                break;
                                            }
                                        } catch (\Exception $e) {
                                            $ex_datax['msg'] = $d->preg_err_msg;
                                            $xxx->push($ex_datax);
                                            $has_err = true;
                                            break;
                                        }
                                    }
                                    //否则就自动验证
                                    $yanzheng = $d->type;
                                    if (in_array("string", $yanzheng)) {
                                        //echo "验证是否为string类型";
                                        if (!is_string($ex)) {
                                            $ex_datax['msg'] = '数据类型错误';
                                            $xxx->push($ex_datax);
                                            $has_err = true;
                                            break;
                                        }
                                    }
                                    if (in_array("int", $yanzheng)) {
                                        //echo "验证是否为int类型";
                                        if (!is_int($ex)) {
                                            $ex_datax['msg'] = '必须为整数';
                                            $xxx->push($ex_datax);
                                            $has_err = true;
                                            break;
                                        }
                                    }
                                    $arr->put($d->id, $ex);
                                }
                            }
                        }
                    }
                }

                if ($has_err) {
                    $hold_on = false;
                    //break;
                }
            }
            if(count($xxx)>0){
                $err_array->push($xxx);
            }
            if ($hold_on) {
                $sub_val = $this->ajax(route($req->input('sub')), $arr->toArray());
                $sub_val = json_decode($sub_val);


                if (isset($sub_val->code)&&$sub_val->code != 0) {
                    $ex_datax['msg'] = $sub_val->msg;
                    foreach ($data as $d) {
                        if ($d->id == $sub_val->id) {
                            foreach ($relation as $r) {
                                if ($r->b == $sub_val->id) {
                                    $ex_datax['key'] = $r->a;
                                }
                            }
                        };
                    }
                    $x = array($ex_datax);
                    $err_array->push($x);
                    continue;
                } else {
                    $data_for_return[] = $arr->toArray();
                }
            }
        }
        $time = time() . rand(0, 50);
        if (count($err_array) > 0) {
            $out_data = $err_array->toArray();
            Excel::create($time, function ($excel) use ($out_data) {
                $excel->sheet('错误的数据信息', function ($sheet) use ($out_data) {
                    $title = collect();
                    $err_con = collect();
                    $zm = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
                    foreach ($out_data as $key => $out) {
                        if (!isset($out[0]['arr'])) {
                            break;
                        }
                        $title->push($out[0]['arr']);
                        foreach ($out as $o) {
                            $mk = array_keys($o['arr']);
                            foreach ($mk as $a2 => $mkv) {
                                if ($mkv == $o['key']) {
                                    $dm = "$zm[$a2]" . ($key + 2);
                                    $sheet->cell($dm, function ($row) {
                                        $row->setBackground('#ff0000');
                                    });
                                }
                            }
                            $err_con->push($o['key'], $key);
                        }
                    }
                    $all_err = $title->toArray();
                    $sheet->setAutoSize(true);
                    $sheet->fromArray($all_err);
                });
            })->store('xls');
            $file = storage_path('exports/') . $time . '.xls';
            $pt = public_path('smktest/');
            if (!File::isDirectory($pt)) {
                File::makeDirectory($pt, $mode = 0777, $recursive = false);
            }
            $pt = public_path('smktest/excel/');
            if (!File::isDirectory($pt)) {
                File::makeDirectory($pt, $mode = 0777, $recursive = false);
            }
            File::move($file, $pt . $time . '.xls');
            $err_array = $err_array->toArray();
            return $this->bk_json($err_array, 0, "successful", ['excel' => $time, 'path' => 'smktest/excel/' . $time . '.xls']);
            File::delete($xsl);
        }
        //最后删除掉这个excel
        File::delete($xsl);
        return $this->bk_json($err_array);
    }

    public function exportIndex(Request $request){
        $config_url = $request->input('route', null);
        $more = $request->input('more', null);
        if (null == $config_url || null == $more) {
            dd('配置信息错误,请参见调用文档');
        }
        $data = (array)json_decode($this->ajax(route($config_url)));
        Cache::forever(self::cache_key, $data);
        if ($more == md5(1)) {
            $data = current($data);
        }
        if (is_array($data) && count($data)>1) {
            $pam = array(
                'data' => array_slice($data,0,2),
                'more' => $more,
            );
            return $this->see_View('Export', $pam);
        }else{
            dd('配置信息错误,请参见调用文档');
        }
    }

    public function export(Request $request){
        try{
            $filed_arr = $request->input('filed');
            $file_name = $request->input('file_name');
            if (empty($file_name)) {
                $errorCode = 1;
                $errorMsg = '请给文件命一个名字';
            }else{
                if (empty($filed_arr)) {
                    $errorCode = 1;
                    $errorMsg = '至少选择一个要导出的字段';
                }
                else{
                    $more = $request->input('more');
                    $rename_arr = $request->input('rename');
                    $excel_arr = Cache::get(self::cache_key);
                    $file_path = 'excel/exports/'.time().uniqid().'/';
                    $name = $file_name;
                    foreach ($excel_arr as $k=>$data) {
                        foreach ($data as $key=>$val) {
                            if ($k == 0 && (isset($rename_arr[$key])&&!empty(trim($rename_arr[$key])))) {
                                $excel_arr[$k][$key] = $rename_arr[$key];
                            }
                            if (!in_array($key, $filed_arr)) {
                                unset($excel_arr[$k][$key]);
                            }
                        }
                    }

                    if (!empty($excel_arr)) {
                        Excel::create($name, function ($excel) use ($excel_arr, $more) {
                            if ($more == md5(1)) {
                                foreach ($excel_arr as $k=>$v) {
                                    $excel->sheet($k, function ($sheet) use ($v) {
                                        $sheet->setAutoSize(false);
                                        $sheet->cells(1, function ($row) {
                                            $row->setAlignment('center');
                                            $row->setFontWeight('bold');
                                        });
                                        $sheet->rows($v);
                                    });
                                }
                            }else{
                                $excel->sheet('sheet', function ($sheet) use ($excel_arr) {
                                    $sheet->setAutoSize(false);
                                    $sheet->cells(1, function ($row) {
                                        $row->setAlignment('center');
                                        $row->setFontWeight('bold');
                                    });
                                    $sheet->rows($excel_arr);
                                });
                            }
                        })->store('xls', public_path($file_path));
                        //})->export('xls');
                        $errorCode = 0;
                        $errorMsg = $file_path.$name.'.xls';
                    }else{
                        $errorCode = 1;
                        $errorMsg = '系统配置出错';
                    }
                }
            }
        }catch (Exception $e){
            $errorCode = 1;
            $errorMsg = '系统错误';
        }

        $pam = array(
            'errorCode' => $errorCode,
            'errorMsg' => $errorMsg
        );
        return response()->json($pam);
    }

    private function see_View($page, $data = null)
    {
        return view(self::view_path . $page)->with($data);
    }

    private function bk_json($d = null, $c = 0, $m = "success", $other = [])
    {
        $x = [
            'data' => $d,
            'code' => $c,
            'msg' => $m
        ];
        if (is_array($other)) {
            foreach ($other as $k => $v) {
                $x[$k] = $v;
            }
        }
        return response()->json(
            $x
        );
    }

    private function ajax($url, $data = null)
    {
        $curl = new cURL();
        $request = $curl->newRequest('post', $url, $data)
            ->setHeader('Accept-Charset', 'utf-8')
            ->setHeader('Accept-Language', 'en-US')
            ->setOption(CURLOPT_TIMEOUT,10)
            ->setCookies($_COOKIE);
        $response = $request->send()->body;
        return $response;
    }

}
