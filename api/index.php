<?php
// Forward root requests to Laravel front controller or render fallback login
$autoloader = __DIR__ . '/../SERVER/vendor/autoload.php';
if (file_exists($autoloader)) {
              require __DIR__ . '/../SERVER/public/index.php';
} else {
              $loginFile = __DIR__ . '/../SERVER/resources/views/auth/login.blade.php';
              if (file_exists($loginFile)) {
                                $lines = file($loginFile);
                                $cleanLines = [];
                                foreach ($lines as $line) {
                                                      if (strpos($line, '@if') !== false || strpos($line, '@endif') !== false || strpos($line, '@error') !== false || strpos($line, '@enderror') !== false || strpos($line, '@csrf') !== false || strpos($line, '{{ $message }}') !== false || strpos($line, '{{ session') !== false) {
                                                                                continue;
                                                      }
                                                      $cleanLines[] = $line;
                                }
                                $content = implode('', $cleanLines);
                                $content = str_replace("{{ csrf_token() }}", "demo_token", $content);
                                $content = str_replace("{{ asset('css/cbt-offline.css') }}", "/css/cbt-offline.css", $content);
                                $content = str_replace("{{ old('username') }}", "", $content);
                                $content = str_replace("{{ request()->getPort() }}", "8000", $content);
                                echo $content;
              } else {
                                echo "CBT Server Online";
              }
}
