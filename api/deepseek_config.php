<?php
// api/deepseek_config.php
// این فایل رو از روت هاست خارج کن یا با .htaccess محافظت کن

define('DEEPSEEK_API_KEY', 'sk-14a7872e2aa5499e92f2277299f3bb00');
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1/chat/completions');
define('DEEPSEEK_MODEL', 'deepseek-chat');
define('DEEPSEEK_MAX_TOKENS', 2000);
define('DEEPSEEK_TEMPERATURE', 0.7);