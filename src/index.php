<?php
session_start();

// Get current step from URL parameter or default to 'welcome'
$current_step = $_GET['step'] ?? 'welcome';

$steps = [
    'welcome' => ['title' => 'Welcome', 'file' => 'steps/welcome.php'],
    'nodered' => ['title' => 'Node-RED', 'file' => 'steps/nodered.php'],
    'partdb' => ['title' => 'Part-DB', 'file' => 'steps/partdb.php'],
    'mariadb' => ['title' => 'MariaDB', 'file' => 'steps/mariadb.php'],
    'database' => ['title' => 'Database', 'file' => 'steps/database.php'],
    'final' => ['title' => 'Finish', 'file' => 'steps/final.php']
];

// Validate current step
if (!isset($steps[$current_step])) {
    $current_step = 'welcome';
}

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
    <!-- Theme Switch Button außerhalb des Containers -->
    <button class="theme-switch" id="themeToggle" aria-label="Toggle theme"></button>

    <div class="container">
        <div class="setup-header">
            <img src="images/logo.png" alt="Pick'n'Light Logo" class="logo">
        </div>
        
        <div class="progress-bar">
            <div class="steps-container">
                <?php 
                $stepOrder = ['welcome', 'nodered', 'partdb', 'mariadb', 'database', 'final'];
                $currentIndex = array_search($current_step, $stepOrder);
                
                foreach ($stepOrder as $index => $step): 
                    $stepClass = '';
                    if ($step === $current_step) {
                        $stepClass = 'active';
                    } elseif ($index < $currentIndex) {
                        $stepClass = 'completed';
                    }
                ?>
                    <div class="step <?php echo $stepClass; ?>">
                        <?php echo $steps[$step]['title']; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="content">
            <?php include $step_data['file']; ?>
        </div>
    </div>
    
    <script>
        const stepOrder = <?php echo json_encode($stepOrder); ?>;
    </script>
    <script src="js/theme.js"></script>
    <script src="js/navigation.js"></script>
    <script src="js/setup-utils.js"></script>
    <script src="js/setup.js"></script>
</body>
</html> 