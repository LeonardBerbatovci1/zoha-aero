<?php
/* Copy this file to config.php and set your own SETUP_CODE. */
/**
 * CMS settings.
 *
 * SETUP_CODE is asked for once, when you open /admin for the first time and create the admin password.
 * Change it to your own secret before uploading. Setup stays disabled while it is still the default.
 */
const SETUP_CODE = 'change-me-before-upload';

// Log out after this many minutes without activity.
const SESSION_MINUTES = 120;
