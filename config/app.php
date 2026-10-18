<?php
/**
 * Base URL of the application, with no trailing slash.
 *
 * Leave NRMS_BASE_URL unset (or empty) if the app is served from the
 * domain root, e.g. https://nrms.police.gov.ng/ -> BASE_URL = ''.
 *
 * If deployed under a subdirectory, e.g. https://example.com/nrms/,
 * set the environment variable:
 *   NRMS_BASE_URL=/nrms
 *
 * Every internal link, redirect, form action, and asset path in the app
 * is built from this constant via the url() helper in includes/functions.php,
 * so changing it here is the only step needed to move the app between a
 * domain root and a subdirectory.
 */
//define('BASE_URL', rtrim(getenv('NRMS_BASE_URL') ?: '', '/'));

define('BASE_URL', rtrim(getenv('NRMS_BASE_URL') ?: '/4god', '/'));
