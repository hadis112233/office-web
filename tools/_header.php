<?php
$webp_images = glob('../static/images/*.{webp,WEBP}', GLOB_BRACE) ?: [];
$fallback_images = glob('../static/images/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE) ?: [];
$bg_images = array_merge($webp_images, $fallback_images);
$bg_image = !empty($bg_images) ? $bg_images[0] : '';
$style_version = @filemtime(__DIR__ . '/../css/style.css') ?: time();
$bg_url = $bg_image !== '' ? $bg_image . '?v=' . (@filemtime(__DIR__ . '/' . $bg_image) ?: time()) : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> - 办公工具站</title>
<link rel="stylesheet" href="../css/style.css?v=<?php echo $style_version; ?>">
</head>
<body data-bg="<?php echo htmlspecialchars($bg_url, ENT_QUOTES, 'UTF-8'); ?>">
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
            <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="desc"><?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?></p>
