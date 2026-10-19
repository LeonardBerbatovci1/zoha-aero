<?php
/**
 * One-way fingerprint (bcrypt hash) of the first-time setup code.
 * Safe to keep in git: the code cannot be recovered from it. It lets the first admin
 * login be created after a GitHub deploy without creating cms/config.php on the server.
 * Setup switches itself off once the admin login exists.
 * To set a new code: php -r "echo password_hash('your-new-code', PASSWORD_DEFAULT);"
 */
const SETUP_CODE_HASH = '$2y$10$Rq.h6vHPXTm1BjfbF0JgdOtm6tvkLMb0wYtI5XHhXDtVafEQc03qW';
