<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Direkter Debug-Output
echo "<!-- Debug Output:\n";
echo "Current Step: " . ($_GET['step'] ?? 'none') . "\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Checking migrate.php:\n";
$paths = [
    '/var/www/html/src/steps/migrate.php',
    'src/steps/migrate.php',
    './src/steps/migrate.php'
];
foreach ($paths as $path) {
    echo "$path: " . (file_exists($path) ? "exists" : "not found") . "\n";
}
echo "-->\n";

// Error Handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    return false;
});

// Define step order FIRST
$stepOrder = ['welcome', 'nodered', 'partdb', 'mariadb', 'database', 'migrate', 'final'];

// Get current step from URL parameter or default to 'welcome'
$current_step = $_GET['step'] ?? 'welcome';
error_log("Current step: " . $current_step);

// Definition der Steps
$steps = [
    'welcome' => ['title' => 'Welcome', 'file' => 'steps/welcome.php'],
    'nodered' => ['title' => 'Node-RED', 'file' => 'steps/nodered.php'],
    'partdb' => ['title' => 'Part-DB', 'file' => 'steps/partdb.php'],
    'mariadb' => ['title' => 'MariaDB', 'file' => 'steps/mariadb.php'],
    'database' => ['title' => 'Database', 'file' => 'steps/database.php'],
    'migrate' => ['title' => 'Migration', 'file' => 'steps/migrate.php'],
    'final' => ['title' => 'Finish', 'file' => 'steps/final.php']
];

// Validate current step
if (!isset($steps[$current_step])) {
    error_log("Invalid step, defaulting to welcome");
    $current_step = 'welcome';
}

$step_data = $steps[$current_step];
error_log("Step data: " . print_r($step_data, true));

// Vor dem include
error_log("Checking file existence: " . $step_data['file']);
error_log("Current working directory: " . getcwd());
error_log("File exists check: " . (file_exists($step_data['file']) ? 'yes' : 'no'));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pick&Light Setup - <?php echo $step_data['title']; ?></title>
    <link rel="stylesheet" href="/src/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="tabs">
            <?php 
            // Debug vor der Schleife
            echo "<!-- Pre-loop Debug:\n";
            echo "stepOrder: " . implode(', ', $stepOrder) . "\n";
            foreach ($stepOrder as $stepId) {
                echo "$stepId exists in steps: " . (isset($steps[$stepId]) ? 'yes' : 'no') . "\n";
                if (isset($steps[$stepId])) {
                    echo "$stepId title: " . $steps[$stepId]['title'] . "\n";
                }
            }
            echo "-->\n";

            foreach ($stepOrder as $stepId): 
                if (!isset($steps[$stepId])) {
                    echo "<!-- Warning: Step $stepId not found! -->";
                    continue;
                }
                $stepInfo = $steps[$stepId];
                $isActive = $current_step === $stepId;
            ?>
                <a href="?step=<?php echo $stepId; ?>" 
                   class="tab <?php echo $isActive ? 'active' : ''; ?>">
                    <?php echo $stepInfo['title']; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="content">
            <?php 
            $file = $step_data['file'];
            error_log("Trying to include file: $file");
            error_log("File exists check: " . (file_exists($file) ? 'yes' : 'no'));
            
            if (!file_exists($file)) {
                error_log("File not found, trying absolute path");
                $file = '/var/www/html/' . $file;
                error_log("Checking absolute path: $file");
                error_log("File exists check (absolute): " . (file_exists($file) ? 'yes' : 'no'));
            }
            
            if (file_exists($file)) {
                error_log("Including file: $file");
                include $file;
            } else {
                error_log("ERROR: Could not find file: " . $step_data['file']);
                echo "<div class='error'>Error: Step file not found (" . htmlspecialchars($step_data['file']) . ")</div>";
            }
            ?>
        </div>
    </div>
    <script src="/src/js/navigation.js"></script>
    <script src="/src/js/setup-utils.js"></script>
    <script src="/src/js/setup.js"></script>
</body>
</html> 