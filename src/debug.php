<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "Current working directory: " . getcwd() . "\n";
echo "Listing /var/www/html/src/steps:\n";
system("ls -la /var/www/html/src/steps");
echo "\nFile exists checks:\n";
$files = [
    '/var/www/html/src/steps/migrate.php',
    'src/steps/migrate.php',
    './src/steps/migrate.php'
];
foreach ($files as $file) {
    echo "$file: " . (file_exists($file) ? "exists" : "not found") . "\n";
}
echo "</pre>"; 