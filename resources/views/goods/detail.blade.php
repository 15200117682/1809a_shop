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

<div>
    <p>商品名称：{{$data->goods_name}}</p>
    <p>商品价格：{{$data->goods_price}}</p>
    <p>商品库存：{{$data->goods_srcoe}}</p>
</div>

<div id="div"></div>

</body>
</html>
<script src="/js/jquery/code.js"></script>
<script src="/js/jquery/jquerry-1.12.4.min.js"></script>
<script>
    new QRCode(document.getElementById("div"), "{{$url}}");
</script>