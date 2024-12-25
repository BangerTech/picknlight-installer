<?php
$requirements = [
    'docker' => [
        'name' => 'Docker',
        'command' => 'docker version --format "{{.Server.Version}}" 2>/dev/null || docker version 2>/dev/null',
        'required' => true
    ],
    'docker-compose' => [
        'name' => 'Docker Compose V2',
        'command' => 'docker compose version --short 2>/dev/null || docker compose version 2>/dev/null',
        'required' => true
    ]
];

function checkDockerSocket() {
    $socket = '/var/run/docker.sock';
    if (!file_exists($socket)) {
        return ['success' => false, 'error' => 'Docker socket not found'];
    }
    
    $perms = fileperms($socket);
    $readable = is_readable($socket);
    $writable = is_writable($socket);
    
    return [
        'success' => $readable && $writable,
        'perms' => substr(sprintf('%o', $perms), -4),
        'readable' => $readable,
        'writable' => $writable
    ];
}

$socketCheck = checkDockerSocket();
$debug = [];

// Debug: System-Informationen sammeln
exec('id', $userOutput);
$debug[] = "Current user: " . implode("\n", $userOutput);
exec('ls -l /var/run/docker.sock', $socketOutput);
$debug[] = "Docker socket: " . implode("\n", $socketOutput);
exec('which docker', $dockerOutput);
$debug[] = "Docker binary: " . implode("\n", $dockerOutput);
exec('groups www-data', $groupsOutput);
$debug[] = "www-data groups: " . implode("\n", $groupsOutput);

$results = [];
foreach ($requirements as $key => $req) {
    $command = $req['command'];
    exec($command . " 2>&1", $output, $return_var);
    
    $results[$key] = [
        'success' => $return_var === 0,
        'version' => $return_var === 0 ? trim(implode("\n", $output)) : 'Not installed',
        'command' => $command,
        'return' => $return_var,
        'output' => implode("\n", $output)
    ];
}

// Debug-Ausgabe
echo "<!-- Debug Information:\n";
echo "Socket Check: " . print_r($socketCheck, true) . "\n";
echo implode("\n", $debug) . "\n";
foreach ($results as $key => $result) {
    echo "=== $key ===\n";
    echo "Command: {$result['command']}\n";
    echo "Return: {$result['return']}\n";
    echo "Output: {$result['output']}\n\n";
}
echo "-->";
?>

<div class="requirements-step">
    <h2>System Requirements Check</h2>
    
    <div class="requirements-list">
        <div class="requirement-item <?php echo $socketCheck['success'] ? 'success' : 'error'; ?>">
            <span class="requirement-name">Docker Socket Access</span>
            <span class="requirement-version">Permissions: <?php echo $socketCheck['perms']; ?></span>
            <span class="requirement-status">
                <?php echo $socketCheck['success'] ? '✓' : '✗'; ?>
            </span>
        </div>
        
        <?php foreach ($requirements as $key => $req): ?>
            <div class="requirement-item <?php echo $results[$key]['success'] ? 'success' : 'error'; ?>">
                <span class="requirement-name"><?php echo $req['name']; ?></span>
                <span class="requirement-version"><?php echo $results[$key]['version']; ?></span>
                <span class="requirement-status">
                    <?php echo $results[$key]['success'] ? '✓' : '✗'; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$socketCheck['success'] || in_array(false, array_column($results, 'success'))): ?>
        <div class="error-message">
            <p>Docker-Konfiguration ist nicht vollständig. Bitte überprüfen Sie:</p>
            <ul>
                <?php if (!$socketCheck['success']): ?>
                    <li>Docker Socket Berechtigungen (aktuell: <?php echo $socketCheck['perms']; ?>)</li>
                <?php endif; ?>
                <?php if (!$results['docker']['success']): ?>
                    <li>Docker CLI Installation und Berechtigungen</li>
                <?php endif; ?>
                <?php if (!$results['docker-compose']['success']): ?>
                    <li>Docker Compose V2 Installation</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <?php if ($socketCheck['success'] && !in_array(false, array_column($results, 'success'))): ?>
            <button class="button next" onclick="nextStep()">Next</button>
        <?php else: ?>
            <button class="button" onclick="window.location.reload()">Retry Check</button>
        <?php endif; ?>
    </div>
</div> 