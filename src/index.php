<?php
session_start();

// Initialize setup step if not set
if (!isset($_SESSION['setup_step'])) {
    $_SESSION['setup_step'] = 1;
}

$steps = [
    1 => ['title' => 'Welcome', 'file' => 'steps/welcome.php'],
    2 => ['title' => 'System Requirements', 'file' => 'steps/requirements.php'],
    3 => ['title' => 'Node-RED Setup', 'file' => 'steps/nodered.php'],
    4 => ['title' => 'Part-DB Setup', 'file' => 'steps/partdb.php'],
    5 => ['title' => 'Database Configuration', 'file' => 'steps/database.php'],
    6 => ['title' => 'Trigger Setup', 'file' => 'steps/triggers.php'],
    7 => ['title' => 'Final Configuration', 'file' => 'steps/final.php']
];

$current_step = $_SESSION['setup_step'];
$step_data = $steps[$current_step];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pick'n'Light Setup - <?php echo $step_data['title']; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="setup-header">
            <h1>Pick'n'Light Setup</h1>
        </div>
        
        <div class="progress-bar">
            <?php foreach ($steps as $num => $step): ?>
                <div class="step <?php echo $num == $current_step ? 'active' : ($num < $current_step ? 'completed' : ''); ?>">
                    <?php echo $step['title']; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="content">
            <?php include $step_data['file']; ?>
        </div>
    </div>
    <script src="js/setup.js"></script>
    <script src="js/database-setup.js"></script>
</body>
</html> 