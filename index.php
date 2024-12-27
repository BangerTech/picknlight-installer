<!DOCTYPE html>
<html>
<head>
    <title>Pick&Light Setup</title>
    <link rel="stylesheet" href="src/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="tabs">
            <a href="?step=welcome" class="tab <?php echo $step === 'welcome' ? 'active' : ''; ?>">Welcome</a>
            <a href="?step=nodered" class="tab <?php echo $step === 'nodered' ? 'active' : ''; ?>">Node-RED</a>
            <a href="?step=partdb" class="tab <?php echo $step === 'partdb' ? 'active' : ''; ?>">Part-DB</a>
            <a href="?step=mariadb" class="tab <?php echo $step === 'mariadb' ? 'active' : ''; ?>">MariaDB</a>
            <a href="?step=database" class="tab <?php echo $step === 'database' ? 'active' : ''; ?>">Database</a>
            <a href="?step=migrate" class="tab <?php echo $step === 'migrate' ? 'active' : ''; ?>">Migration</a>
            <a href="?step=final" class="tab <?php echo $step === 'final' ? 'active' : ''; ?>">Final</a>
        </nav>

        <?php
        $step = $_GET['step'] ?? 'welcome';
        
        switch ($step) {
            case 'welcome':
                include 'src/steps/welcome.php';
                break;
            case 'nodered':
                include 'src/steps/nodered.php';
                break;
            case 'partdb':
                include 'src/steps/partdb.php';
                break;
            case 'mariadb':
                include 'src/steps/mariadb.php';
                break;
            case 'database':
                include 'src/steps/database.php';
                break;
            case 'migrate':
                include 'src/steps/migrate.php';
                break;
            case 'final':
                include 'src/steps/final.php';
                break;
            default:
                include 'src/steps/welcome.php';
        }
        ?>
    </div>
    <script src="src/js/navigation.js"></script>
</body>
</html> 