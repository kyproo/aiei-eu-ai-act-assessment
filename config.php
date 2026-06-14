<?php
// config.php — AIEI EU AI Act Assessment
// Set ANTHROPIC_API_KEY as a server environment variable (recommended),
// or replace the placeholder below for testing.
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: 'YOUR PRIVATE KEY');
define('ANTHROPIC_MODEL', 'claude-sonnet-4-6');
define('OWNER_EMAIL', 'info@ai-ei.org');
