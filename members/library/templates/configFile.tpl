<?php
    /***** SPECIFIC TO YOUR SERVER (CHANGE) ******/
    $hostname  = '{SERVER}';
    $dbuser    = '{USER}';
    $dbpass    = '{PASSWORD}';
    $database  = '{DATABASE}';
    define('INSTALLED', {INSTALLED});
    define('SESSION_NAME', '{SESSION_NAME}');
    define('SALT', '{SALT}');
    define('DISABLE_CACHING', {DISABLE_CACHING});
    define('REMOTELOG', {REMOTELOG});

    //if set, overrides the key set in Settings->Security->Application Key
    define('APIKEY', {APIKEY});

    // For increased security, you can define here a path in your server where to store session files.
    // It must be only readable/writable by the web server user, and located outside of the web root.
    define('SESSION_PATH', {SESSION_PATH});

    //defines used for debugging
    define('DEBUG',{DEBUG});
    define('DEMO',{DEMO});
    define('FIREBUG',{FIREBUG});
    define('CHROMEBUG',{CHROMEBUG});

    // Hosted Version, do not change
    define('HOSTED',{HOSTED});

    // ***  LOG_LEVELS (each level adds additional information) ***
    // 0: No logging
    // 1: Security attacks attempts, errors and important messages (recommended level)
    // 2: Reserved for debugging
    // 3: + Warnings and EventLogs, VIEW/ACTION and Request URIs and URI redirections and POST/COOKIE values
    // 4. + plugin events, curl requests, some function calls with their parameters, etc.
    //		(use this when sending logs to support)
    // 5: + include suppressed actions
    // 6: + Action responses (ajax,serialized,XML (as array)
    // 7: + SQL queries
    define('LOG_LEVEL', {LOG_LEVEL});

    // To activate text file logging, replace the 'false' with the file full path. Do not use relative paths.
    // Use absolute paths(e.g. /home/yourinstallationpath/ce.log, instead of ce.log)
    // The log may show passwords, so please use a file outside the web root, but writable by the web server user.
    define('LOG_TEXTFILE', {LOG_FILE});

    // If you want to change the location of the admin directory you can rename the /admin folder however you
    // MUST specify it's new location here.
    define('NE_CONTROLLER_ADMIN_DIR', '{ADMIN_DIR}');
?>
