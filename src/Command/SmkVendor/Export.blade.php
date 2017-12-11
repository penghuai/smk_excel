<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../../favicon.ico">
    <title>Excel导入导出</title>
    <!-- Bootstrap core CSS -->
    <link href="{{asset('smkvendor/materialize.css')}}" rel="stylesheet">
    <link href="{{asset('smkvendor/import.css')}}" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://cdn.bootcss.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style>
        .canvas {
            position: absolute;
            left: 0px;
            top: 0px;
            z-index: -1;
        }
        .backcanvas {
            position: absolute;
            left: 0px;
            top: 0px;
            z-index: -2;
        }
        .showleft span{display: block; }
        .showright span{display: block;}
    </style>
</head>
<body>
<nav class="bg-blue">
    <div class="nav-wrapper container head">
        <div class="pull-left">【Excel导出工具】V1.0版</div>
        <div class="tech pull-right">技术支持</div>
    </div>
</nav>
<div class="container row">
    <h5  class="light-blue-text darken-2 col s12 p0"  >讯安实习>导出数据</h5>
    <h6 class=" col s12 p0">
        该工具由
        <a href="http://www.cdsmartlink.com" class=" light-blue-text darken-2">成都慧联客信息技术</a>
        提供
    </h6>
    <form id="export-form" method="post" action="{{route('smk_vender_excel_export')}}">
        <div id="select-file" class="col s10 p0">
            <div enctype="multipart/form-data" class="form-inline" id="fm" method="post"
                 action="{{route('smk_vender_excel_sub_excel')}}">
                <div class="select-input col s7">
                    <input type="text" name="file_name" id="file_name" class="col s12" id="show">
                </div>
                <div class="select-btn col s2" id="chose" >
                    请给文件命名
                </div>
            </div>
        </div>

        <div id="field" class="col s10 p0 mt15 ">
            <div class="field-header plr15">
                <div class="field-header-left">
                    <div class="tips">
                        请选择导出的字段
                    </div>
                </div>
                <div class="field-header-right tools">
                    <div class="sort-btn">
                        全部选择
                    </div>
                </div>
            </div>

            <div class="demo1">
                <form id="export-form" method="post" action="{{route('smk_vender_excel_export')}}">
                    <input type="hidden" name="more" value="{{isset($more)?$more:''}}">
                    <div class="show clearfix">
                        @if(isset($data[0])&&!empty($data[0]))
                            @foreach($data[0] as $k=>$v)
                                <div style="display: flex; justify-content: space-between;">
                            <span class="showitem" >
                                <input class="filled-in" type="checkbox" id="test{{$k}}" name="filed[]" value="{{$k}}" checked/>
                                <label for="test{{$k}}">{{$v}}</label>
                            </span>
                                    <span class="" style="color: #ff6526;">
                                <input type="text" value="" name="rename[{{$k}}]" placeholder="重命名">
                            </span>
                                </div>
                            @endforeach
                        @endif

                    </div>
                </form>
            </div>

            <div class="field-footer" style="justify-content: space-between; !important;">
                <div style="visibility: hidden;">
                    <a id="go_download_excel" style="margin-left: 15px; cursor: pointer;">导出范围：</a>
                </div>

                <div class="start-import saveImageBtn" onclick="sub()">导出并下载</div>
            </div>
        </div>
    </form>

    <div id="import-result" class="col s10 p0">
        <div class="result-tips">
            <span>导出结果部分预览</span>
        </div>
        @if(isset($data)&&!empty($data))
            <table class="bordered centered" id="result">
                @foreach($data as $value)
                    <tr>
                        @foreach($value as $v)
                            <td>{{$v}}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        @endif

    </div>
</div>
</body>
<script src="{{asset('smkvendor/jquery.min.js')}}"></script>
<script type="text/javascript" src="{{asset('smkvendor/jquery.form.js')}}"></script>
<script>
    $(function () {
        $('.sort-btn').on('click', function () {
            var that = this;
            var i = 0;
            var l = $('.filled-in').length;
            $('.filled-in').each(function () {
                if (this.checked) {
                    i ++;
                }else {
                    this.checked = true;
                }
            });
            if (i==l) {
                $('.filled-in').each(function () {
                    this.checked = false;
                });
            }
        });
    });

    function VerifyNull(str){
        if(typeof(str)=="undefined"){
            return true;
        }
        if(null==str){
            return true
        }
        if(str.length<1){
            return true;
        }
        if (str.replace(/(^\s*)|(\s*$)/g, "") == '') {
            return true;
        }

        return false;
    }

    function sub() {
        if (VerifyNull($('#file_name').val())) {
            alert('请给文件命一个名字');
            return false;
        }else {
            var l = jQuery('#export-form').find("input[type='checkbox']:checked").length;
            if (l==0) {
                alert('至少选择一个要导出的字段');
                return false;
            }else {
                $('#export-form').ajaxSubmit({
                    success: function (msg) {
                        if (msg.errorCode == 0) {
                            location.href = '/'+msg.errorMsg;
                        }else {
                            alert(msg.errorMsg);
                        }
                    }
                });
            }
        }

    }
</script>
</html>
