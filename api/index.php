<?php
// Forward root requests to Laravel front controller or render fallback login
$autoloader = __DIR__ . '/../SERVER/vendor/autoload.php';
if (file_exists($autoloader)) {
      require __DIR__ . '/../SERVER/public/index.php';
} else {
      $loginFile = __DIR__ . '/../SERVER/resources/views/auth/login.blade.php';
      if (file_exists($loginFile)) {
                $content = file_get_contents($loginFile);
                $content = str_replace("{{ csrf_token() }}", "demo_token", $content);
                $content = str_replace("{{ asset('css/cbt-offline.css') }}", "/css/cbt-offline.css", $content);
                echo $content;
      } else {
                echo "CBT Server Online";
      }
}
