<?php
// config.php — AIEI EU AI Act Assessment
// Set OPENAI_API_KEY as a server environment variable (recommended),
// or replace the placeholder below for testing.

define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'PASTE_YOUR_OPENAI_KEY_HERE');
define('OPENAI_MODEL', 'gpt-4o-mini');

define('OWNER_EMAIL', 'info@ai-ei.org');
