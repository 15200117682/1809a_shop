<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<table border>
    <tr>
        <td>商品id</td>
        <td>商品名称</td>
        <td>商品价格</td>
        <td>商品库存</td>
        <td>操作</td>
    </tr>
    @foreach($data as $k=>$v)
    <tr>
        <td>{{$v['goods_id']}}</td>
        <td>{{$v['goods_name']}}</td>
        <td>{{$v['goods_price']}}</td>
        <td>{{$v['goods_srcoe']}}</td>
        <td><a href="">加入购物车</a></td>
    </tr>
        @endforeach
</table>

<button id="btn">点击分享</button>
</body>
</html>
<script src="/js/jquery/jquery-1.12.4.min.js"></script>
<script src="http://res2.wx.qq.com/open/js/jweixin-1.4.0.js "></script>
<script>

</script>