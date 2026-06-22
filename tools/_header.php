<?php
$bg_images = glob('../static/images/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
$bg_image = !empty($bg_images) ? $bg_images[0] : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $title; ?> - 办公工具站</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body data-bg="<?php echo $bg_image; ?>">
<script>
(function(){
    const bg = document.body.getAttribute('data-bg');
    if (bg) document.documentElement.style.setProperty('--bg', 'url("' + bg + '")');
})();
</script>
<div class="app" style="flex-direction:column;">
    <main class="main" style="width:100%;">
        <div class="tool-page">
            <a href="../index.php" class="back-link">← 返回首页</a>
            <h1><?php echo $title; ?></h1>
            <p class="desc"><?php echo $desc; ?></p>
