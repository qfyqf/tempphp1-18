{__NOLAYOUT__}<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.5, maximum-scale=2.0, user-scalable=yes" />  
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>跳转提示</title>
    <link href="__PUBLIC__/static/css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
        body, h1, h2, p,dl,dd,dt{margin: 0;padding: 0;font: 15px/1.5 微软雅黑,tahoma,arial;}
        body{background:#efefef;}
        h1, h2, h3, h4, h5, h6 {font-size: 100%;cursor:default;}
        ul, ol {list-style: none outside none;}
        a {text-decoration: none;color:#447BC4}
        a:hover {text-decoration: underline;}
    </style>

</head>
<body>
    
    <div style="width: 600px; overflow: hidden; background-color: #FFF; padding:20px; margin:50px auto; border-radius:10px;border: 1px solid #CDCDCD;-webkit-box-shadow: 0 0 8px #CDCDCD;-moz-box-shadow: 0 0 8px #cdcdcd;box-shadow: 0 0 8px #CDCDCD; ">
        <div style="text-align: center; font-size: 60px;">
            <?php if($code): ?>
            <span class="glyphicon glyphicon-ok-sign" style="color: #43c382;"></span>
            <?php else: ?>
            <span class="glyphicon glyphicon-remove-sign" style="color: #e48e74;"></span>
            <?php endif; ?>
        </div>


        <div style="text-align: center; font-size: 16px; margin: 20px auto; line-height: 30px;">
            <?php if($code): ?>
                <span style="color: #43c382;"><?php echo(strip_tags($msg));?></span>
            <?php else: ?>
                <span style="color: #e48e74;"><?php echo(strip_tags($msg));?></span>
            <?php endif; ?>
        </div>
        <div style="text-align: center; font-size: 16px; line-height: 40px; color: #888;">
            页面自动 <a id="href" href="<?php echo($url);?>">跳转</a> 等待时间： <b id="wait"><?php echo($wait);?></b>
        </div>
    </div>

    <script type="text/javascript">
        (function(){
            var wait = document.getElementById('wait'),
                href = document.getElementById('href').href;
            var interval = setInterval(function(){
                var time = --wait.innerHTML;
                if(time <= 0) {
                    location.href = href;
                    clearInterval(interval);
                };
            }, 1000);
        })();
    </script>
</body>
</html>