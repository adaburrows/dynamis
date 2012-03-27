<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <?php util::e($meta); ?>
  <title><?php util::e($title); ?></title>
  <?php util::e($css); ?>
  <?php util::e($scripts) ?>
  <link rel="profile" href="http://microformats.org/profile/hcard" />
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
</head>
<body>
  <div id="outer_wrap">
    <div id="main">
      <div class="wrap">
        <?php util::e($content); ?>
      </div>
    </div>
    <div id="push"></div>
  </div>
</body>
</html>
