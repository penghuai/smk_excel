<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../../favicon.ico">
    <title>Excel导入导出</title>
    <!-- Bootstrap core CSS -->
    <link href="{{asset('smkvendor/bootstrap.min.css')}}" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://cdn.bootcss.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        .checkbox-custom input[type="checkbox"] {
            background-color: white;
            border-radius: 5px;
            border: 1px solid #d3d3d3;
            width: 20px;
            height: 20px;
            display: inline-block;
            text-align: center;
            vertical-align: bottom;
            line-height: 20px;
        }
        .checkbox-custom {
            position: relative;
            padding: 0 0 0 25px;
            margin-bottom: 7px;
            margin-top: 0;
        }
        .clearfix:after {
            content: ".";
            display: block;
            height: 0;
            clear: both;
            overflow: hidden;
        }
        .clearfix {
            zoom: 1;
        }
        .show {
            position: relative;
            width: 540px;
            margin: 40px 100px;
            cursor: pointer;
        }
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
        .showleft {
            float: left;
            width: 152px;
        }
        .showright {
            float: right;
            width: 152px;
        }
        .show .showitem {
            width: 100px;
            display: block;
            border-radius: 15px;
            padding: 10px;
        }
        .tools {
        }
        .tools div {
            float: right;
            height: 30px;
            padding: 0 5px;
            margin: 0 5px;
            color: #060707;
            cursor: pointer;
            line-height: 30px;
        }
        .tools div:hover {
            color: #C30;
        }
        .tools .goBackBtn {
            color: #aaa39f;
        }
        .tools .resetCanvasBtn {
            color: #aaa39f;
        }
        .showleft .showitem {
            color: #aaa39f;
            font-size: 18px;
        }
        .showright .showitem {
            color: #aaa39f;
            font-size: 18px;
        }
        .showright .showitem p {
            top: -76px;
            left: 107px;;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="blog-header">
        <h1 class="blog-title">Excel导入</h1>
        <p class="lead blog-description">http://www.cdsmartlink.com</p>
    </div>

    <div class="row">
        <div class="col-sm-8 blog-main">
            <div class="blog-post">
                <div class="row">
                    <form enctype="multipart/form-data" class="form-inline" id="fm" method="post"
                          action="{{route('smk_vender_excel_sub_excel')}}">
                        <div class="form-group">
                            <input type="text" readonly class="form-control" id="show"
                                   style="width: 380px;margin-left:15px;">
                        </div>
                        <button type="button" class="btn btn-default" id="chose">选择Excel</button>
                        <button type="button" class="btn btn-default" id="start_sub">上传Excel</button>
                        <input type="file" id="fs" name="excel" style="display: none"
                               accept=".csv, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                    </form>
                </div>
                <hr>
                <span style="font-size: 20px;color: #aaa39f">请拉动选择对应的数据信息</span>
                <div class="demo1" style="border: 1px solid #eee">
                    <div class="show clearfix">
                        <div class="showleft" id="data_excel"><!--左侧-->
                        </div>
                        <div class="showright"><!--右侧-->
                            @if(isset($cfg)&&is_array($cfg))
                                @foreach($cfg as $c)
                                    <span class="showitem" smkval="{{$c->id}}">{{$c->chinese}}</span>
                                @endforeach
                            @endif
                        </div>
                        <canvas class="canvas"></canvas><!--连线画布-->
                        <canvas class="backcanvas"></canvas><!--提示线画布-->
                    </div>
                    <div class="tools">
                        <!--<div class="downloadImageBtn">下载</div>-->
                        <div class="goBackBtn">撤销</div>
                        <div class="resetCanvasBtn">重置</div>
                        <div class="saveImageBtn" style="color: #aaa39f;">开始导入</div>
                    </div>
                </div>
            </div>
        </div>



    </div>

    <div >
        <div class="sidebar-module">
            <h4>导入结果<a id="go_download_excel" style="padding-left: 30px;cursor: pointer;display: none">下载未导入成功的结果</a></h4>
            <hr>
            <div id="result">

            </div>
        </div>
    </div>


</div>
<form id="start_resolution" action="{{route('smk_vender_excel_resolution')}}" method="post">
    <input type="hidden" id="ax" name="ax">
    <input type="hidden" id="fx" name="fx">
    <input type="hidden" value="{{$urx}}" id="cfg_url" name="cfg_url">
    <input type="hidden" value="{{$sub}}" name="sub">
</form>

</body>

<script src="{{asset('smkvendor/jquery.min.js')}}"></script>

<script type="text/javascript" src="{{asset('smkvendor/bootstrap.min.js')}}"></script>
<script type="text/javascript" src="{{asset('smkvendor/jquery.form.js')}}"></script>
<script>

    var file = "";
    $(function () {
        $('#chose').click(function () {
            $('#fs').click();
        });

        $('#fs').change(function (e) {
            var filePath = $(this).val();
            if (filePath) {
                var arr = filePath.split('\\');
                var fileName = arr[arr.length - 1];
                var fileext = fileName.substring(fileName.lastIndexOf("."), fileName.length);
                if (fileext != '.xls' && fileext != ".xlsx") {
                    alert("对不起，导入数据格式必须是xls格式文件哦，请您调整格式后重新上传");
                    $(this).val("");
                    return;
                }
                $("#show").val(fileName);
            } else {
                $("#show").val("您未上传文件，或者您上传文件类型有误！");
                return false
            }
        });


        $('#start_sub').click(function () {
            $('#fm').submit();
        });

        $('#fm').submit(function () {
            $(this).ajaxSubmit({
                success: function (msg) {
                    if (msg.code != 0) {
                        alert(msg.msg);
                        return;
                    } else {
                        var h = "";
                        for (var i in msg.data.data) {
                            if (msg.data.data[i] != "null") {
                                h += '<span class="showitem" smkval="'+msg.data.data[i]+'">' + msg.data.data[i] + '</span>';
                            }
                        }
                        file = msg.data.file;
                        $('#data_excel').html(h);
                        creatline($(".demo1"));
                    }
                }
            });
            return false;
        });


        $('#start_resolution').submit(function () {
            $('#result').html("");
            $('#go_download_excel').hide();
            $(this).ajaxSubmit({
                success: function (msg) {
                    $('#fs').val("");
                    $('.resetCanvasBtn').click();
                    if (msg.code != 0) {
                        alert(msg.msg);
                        var h = "<span style='color: red'>导入失败:"+msg.msg+"</span>";
                        $('#result').html(h);
                        return;
                    } else {
                        if(msg.data.length>0){
                            var h = "<table style='width: 100%'><tr>";

                            for(var i in msg.data[0][0].arr){
                                h+="<th>"+i+"</th>";
                            }
                            h+="</tr>";
                            for(var i in msg.data){
                                h+='<tr>'
                               for(var k in msg.data[i][0].arr){
                                   h+='<td>'+msg.data[i][0].arr[k];
                                   for(var myl in msg.data[i]){
                                       if(k==msg.data[i][myl].key){
                                           h+='<span style="color: red">('+msg.data[i][myl].msg+')</span>'
                                       }
                                   }
                                   h+="</td>";
                               }
                                h+='</tr>'
                            }
                            h+="</table>"
                            $('#result').html(h);
                            $('#go_download_excel').attr("href","/"+msg.path);
                            alert("导入完成,请修改有问题的数据之后重新导入");
                            $('#go_download_excel').show();
                        }else{
                            var h = "<span>导入成功</span>";
                            $('#result').html(h);
                        }
                    }
                }
            });
            return false;
        });


    })

    function W(obj) {
        console.log(obj);
    }
</script>


<script type="text/javascript">


    function creatline(box) {
        var linewidth = 1, linestyle = "#393a3a";//连线绘制--线宽，线色
        //初始化赋值 列表内容
        box.find(".showleft").children("span").each(function (index, element) {
            $(this).attr({
                group: "gpl",
                left: $(this).position().left + $(this).outerWidth(),
                top: $(this).position().top + $(this).outerHeight() / 2,
                sel: "0",
                check: "0"
            });
        });
        box.find(".showright").children("span").each(function (index, element) {
            $(this).attr({
                group: "gpr",
                left: $(this).position().left,
                top: $(this).position().top + $(this).outerHeight() / 2,
                sel: "0",
                check: "0"
            });
        });
        box.find(".showleft").attr('first', 0);//初始赋值 列表内容容器
        box.find(".showright").attr('first', 0);
        //canvas 赋值
        var canvas = box.find(".canvas")[0];  //获取canvas  实际连线标签
        canvas.width = box.find(".show").width();//canvas宽度等于div容器宽度
        canvas.height = box.find(".show").height();

        var backcanvas = box.find(".backcanvas")[0];  //获取canvas 模拟连线标签
        backcanvas.width = box.find(".show").width();
        backcanvas.height = box.find(".show").height();
        var ax = [];
        //连线数据
        var groupstate = false;//按下事件状态，标记按下后的移动，抬起参考
        var mx = [];//连线坐标
        var my = [];
        var ms = [];
        var temp;//存贮按下的对象
        var pair = 0;//配对属性
        var pairl = [];
        var pairr = [];
        //提示线数据
        var mid_startx, mid_starty, mid_endx, mid_endy;//存储虚拟连线起始坐标
        var linshi = {};
        //事件处理---按下
        box.children(".show").children().children("span").on("mousedown", function (event) {
            groupstate = true;
            if ($(this).attr("check") == 1) {
                $(this).attr("sel", "1").parent().attr("first", "1");
                temp = $(this);
            } else {
                $(this).attr("sel", "1").addClass("addstyle").parent().attr("first", "1");
                temp = $(this);
            }
            linshi.a=temp.attr("smkval");
            mid_startx = $(this).attr("left");
            mid_starty = $(this).attr("top");
            event.preventDefault();
        });
        $(document).mousemove(function (event) {		//移动
            var $target = $(event.target);
            if (groupstate) {
                mid_endx = event.pageX - box.find(".show").offset().left;
                mid_endy = event.pageY - box.find(".show").offset().top;
                var targettrue = null;
                if ($target.is("span") && $target.closest(".show").parent().attr("class") == box.attr("class")) {
                    targettrue = $target;
                } else if ($target.closest(".showitem").is("span") && $target.closest(".show").parent().attr("class") == box.attr("class")) {
                    targettrue = $target.closest(".showitem");
                } else {
                    targettrue = null;
                }
                ;

                if (targettrue) {
                    if (targettrue.parent().attr("first") == 0) {
                        if (targettrue.attr("check") == 0) {
                            targettrue.addClass("addstyle").attr("sel", "1").siblings("span[check=0]").removeClass("addstyle").attr("sel", "0");
                        }
                        ;
                    } else {
                        if (targettrue.attr("check") == 0) {
                            targettrue.addClass("addstyle").attr("sel", "1").siblings("span[check=0]").removeClass("addstyle").attr("sel", "0");
                            mid_startx = targettrue.attr("left");
                            mid_starty = targettrue.attr("top");
                        }
                        ;
                        //temp=targettrue;
                    }
                    ;
                } else {
                    if (box.find(".showleft").attr("first") == 0) {
                        box.find(".showleft").children("span").each(function (index, element) {
                            if ($(this).attr('check') == 0) {
                                $(this).attr("sel", "0").removeClass("addstyle");
                            }
                            ;
                        });
                    }
                    ;
                    if (box.find(".showright").attr("first") == 0) {
                        box.find(".showright").children("span").each(function (index, element) {
                            if ($(this).attr('check') == 0) {
                                $(this).attr("sel", "0").removeClass("addstyle");
                            }
                            ;
                        });
                    }
                    ;

                }
                ;
                backstrockline();
            }
            ;
            event.preventDefault();
        });
        $(document).mouseup(function (event) {  //抬起

            var $target = $(event.target);
            if (groupstate) {
                var targettrue;
                if ($target.is("span") && $target.closest(".show").parent().attr("class") == box.attr("class")) {
                    targettrue = $target;
                } else if ($target.closest(".showitem").is("span") && $target.closest(".show").parent().attr("class") == box.attr("class")) {
                    targettrue = $target.closest(".showitem");
                } else {
                    targettrue = null;
                };
                if (targettrue) {
                    if (targettrue.parent().attr("first") == 0) {
                        if (targettrue.attr("check") == 0) {
                            if (temp.attr('check') == 1) {
                                box.find(".showleft").children("span").each(function (index, element) {
                                    if ($(this).attr('sel') == 1) {
                                        if ($(this).attr('check') == 0) {
                                            $(this).attr("sel", "0").removeClass("addstyle");
                                        } else {
                                            $(this).attr("sel", "0").addClass("addstyle");
                                        }
                                        ;
                                    }
                                });
                                box.find(".showleft").attr('first', 0);
                                box.find(".showright").children("span").each(function (index, element) {
                                    if ($(this).attr('sel') == 1) {
                                        if ($(this).attr('check') == 0) {
                                            $(this).attr("sel", "0").removeClass("addstyle");
                                        } else {
                                            $(this).attr("sel", "0").addClass("addstyle");
                                        }
                                        ;
                                    }
                                });
                                box.find(".showright").attr('first', 0);

                            } else {
                                box.find(".showleft").children("span").each(function (index, element) {
                                    if ($(this).attr('sel') == 1) {
                                        mx.push($(this).attr('left'));
                                        my.push($(this).attr('top'));
                                        ms.push(0);
                                        linshi.a=($(element).attr("smkval"))
                                        $(this).attr("check", "1");
                                        $(this).attr("sel", "0");
                                        $(this).attr("pair", pair);
                                        pairl.push(pair);
                                    }
                                });
                                box.find(".showright").children("span").each(function (index, element) {
                                    if ($(this).attr('sel') == 1) {
                                        mx.push($(this).attr('left'));
                                        my.push($(this).attr('top'));
                                        ms.push(1);
                                        $(this).attr("check", "1");
                                        $(this).attr("sel", "0");
                                        $(this).attr("pair", pair);
                                        pairr.push(pair);
                                        linshi.b=($(element).attr("smkval"))
                                    }
                                });
                                ax.push(linshi);
                                linshi={};
                                pair += 1;
                                box.find(".showleft").attr('first', 0);
                                box.find(".showright").attr('first', 0);
                                strockline();
                            }
                            ;
                        } else {

                            box.find(".showleft").children("span").each(function (index, element) {
                                if ($(this).attr('sel') == 1) {
                                    if ($(this).attr('check') == 0) {
                                        $(this).attr("sel", "0").removeClass("addstyle");
                                    } else {
                                        $(this).attr("sel", "0").addClass("addstyle");
                                    }
                                    ;
                                }
                            });
                            box.find(".showleft").attr('first', 0);
                            box.find(".showright").children("span").each(function (index, element) {
                                if ($(this).attr('sel') == 1) {
                                    if ($(this).attr('check') == 0) {
                                        $(this).attr("sel", "0").removeClass("addstyle");
                                    } else {
                                        $(this).attr("sel", "0").addClass("addstyle");
                                    }
                                    ;
                                }
                            });
                            box.find(".showright").attr('first', 0);
                        }
                        ;
                    } else {

                        box.find(".showleft").children("span").each(function (index, element) {

                            if ($(this).attr('check') == 0) {
                                if ($(this).attr('sel') == 1) {
                                    $(this).attr("sel", "0").removeClass("addstyle");
                                }
                                ;
                            } else {
                                if ($(this).attr('sel') == 1) {
                                    $(this).attr("sel", "0").addClass("addstyle");
                                }
                                ;
                            }
                            ;
                        });
                        box.find(".showleft").attr('first', 0);
                        box.find(".showright").children("span").each(function (index, element) {
                            if ($(this).attr('check') == 0) {
                                if ($(this).attr('sel') == 1) {
                                    $(this).attr("sel", "0").removeClass("addstyle");
                                }
                                ;
                            } else {
                                if ($(this).attr('sel') == 1) {
                                    $(this).attr("sel", "0").addClass("addstyle");
                                }
                                ;
                            }
                            ;
                        });
                        box.find(".showright").attr('first', 0);
                    }
                    ;
                } else {
                    box.find(".showleft").children("span").each(function (index, element) {
                        if ($(this).attr('check') == 0) {
                            if ($(this).attr('sel') == 1) {
                                $(this).attr("sel", "0").removeClass("addstyle");
                            }
                            ;
                        }
                        ;
                    });
                    box.find(".showleft").attr('first', 0);
                    box.find(".showright").children("span").each(function (index, element) {
                        if ($(this).attr('check') == 0) {
                            if ($(this).attr('sel') == 1) {
                                $(this).attr("sel", "0").removeClass("addstyle");
                            }
                            ;
                        }
                        ;
                    });
                    box.find(".showright").attr('first', 0);
                }
                ;
                clearbackline();
                groupstate = false;

            }
            event.preventDefault();
        });
        //canvas 追加2d画布
        var context = canvas.getContext('2d');  //canvas追加2d画图
        var lastX, lastY;//存放遍历坐标
        function strockline() {//绘制方法
            context.clearRect(0, 0, box.find(".show").width(), box.find(".show").height());//整个画布清除
            context.save();
            context.beginPath();
            context.lineWidth = linewidth;
            for (var i = 0; i < ms.length; i++) {  //遍历绘制
                lastX = mx[i];
                lastY = my[i];
                if (ms[i] == 0) {
                    context.moveTo(lastX, lastY);
                } else {
                    context.lineTo(lastX, lastY);
                }
            }
            context.strokeStyle = linestyle;
            context.stroke();
            context.restore();
        };

        function clearline() {//清除
            context.clearRect(0, 0, box.find(".show").width(), box.find(".show").height());
            mx = [];//数据清除
            my = [];
            ms = [];
            pairl = [];
            pairr = [];
            pair = 0;
            box.find(".showleft").children("span").each(function (index, element) {
                $(this).removeClass("addstyle");
                $(this).attr("sel", "0");
                $(this).attr("check", "0");

            });
            box.find(".showleft").attr('first', 0);
            box.find(".showright").children("span").each(function (index, element) {
                $(this).removeClass("addstyle");
                $(this).attr("sel", "0");
                $(this).attr("check", "0");
            });
            box.find(".showright").attr('first', 0);
        };
        //init backcanvas 2d画布 虚拟绘制
        var backcanvas = backcanvas.getContext('2d');

        function backstrockline() {//绘制
            backcanvas.clearRect(0, 0, box.find(".show").width(), box.find(".show").height());
            backcanvas.save();
            backcanvas.beginPath();
            backcanvas.lineWidth = linewidth;
            backcanvas.moveTo(mid_startx, mid_starty);
            backcanvas.lineTo(mid_endx, mid_endy);
            backcanvas.strokeStyle = linestyle;
            backcanvas.stroke();
            backcanvas.restore();
        };

        function clearbackline() {//清除
            backcanvas.clearRect(0, 0, box.find(".show").width(), box.find(".show").height());
            mid_startx = null;
            mid_starty = null;
            mid_endx = null;
            mid_endy = null;
        };
        //重置
        box.find(".resetCanvasBtn").click(function () {
        ax.splice(0,ax.length);
            clearline();
        });
        //预览和保存操作
        box.find(".saveImageBtn").click(function () {
        $('#ax').val(JSON.stringify(ax));
        $('#fx').val(file);
            $('#start_resolution').submit();
        });

        //回退
        box.find(".goBackBtn").click(function () {
            ax.pop();
            goBack();
        });

        function goBack() {
            var linenlastIndex = ms.join("").substr(0, ms.length - 1).lastIndexOf("0");
            if (linenlastIndex == 0) {
                clearline();
            } else {
                mx = mx.slice(0, linenlastIndex);  //记录值
                my = my.slice(0, linenlastIndex);  //坐标
                ms = ms.slice(0, linenlastIndex);
                context.clearRect(0, 0, box.find(".show").width(), box.find(".show").height());
                context.save();
                context.beginPath();
                context.lineWidth = linewidth;
                for (var i = 0; i < ms.length; i++) {
                    lastX = mx[i];
                    lastY = my[i];
                    if (ms[i] == 0) {
                        context.moveTo(lastX, lastY);
                    } else {
                        context.lineTo(lastX, lastY);
                    }
                }
                context.strokeStyle = linestyle;
                context.stroke();
                context.restore();
                var pairindex = pairl.length - 1;
                box.find(".showleft").children("span").each(function (index, element) {
                    if ($(this).attr("pair") == pairl[pairindex]) {
                        $(this).removeClass("addstyle");
                        $(this).attr("sel", "0");
                        $(this).attr("check", "0");
                        $(this).removeAttr("pair");
                    }
                    ;
                });
                box.find(".showleft").attr('first', 0);
                box.find(".showright").children("span").each(function (index, element) {
                    if ($(this).attr("pair") == pairl[pairindex]) {
                        $(this).removeClass("addstyle");
                        $(this).attr("sel", "0");
                        $(this).attr("check", "0");
                        $(this).removeAttr("pair");
                    }
                    ;
                });
                box.find(".showright").attr('first', 0);
                pair -= 1;
                pairl = pairl.slice(0, pairindex);
                pairr = pairr.slice(0, pairindex);
            }
            ;
        };
        //end
    };//==============fune
</script>


</html>
