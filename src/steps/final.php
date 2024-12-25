<div class="final-step">
    <h2>Setup Complete!</h2>
    
    <div class="summary">
        <h3>Configuration Summary</h3>
        <div class="config-item">
            <h4>Node-RED</h4>
            <?php if ($_SESSION['nodered_config']['useTraefik']): ?>
                <p>Access URL: https://<?php echo $_SESSION['nodered_config']['noderedDomain']; ?></p>
            <?php else: ?>
                <p>Access URL: http://localhost:<?php echo $_SESSION['nodered_config']['noderedPort']; ?></p>
            <?php endif; ?>
        </div>

        <div class="config-item">
            <h4>Part-DB</h4>
            <?php if ($_SESSION['partdb_config']['useTraefik']): ?>
                <p>Access URL: https://<?php echo $_SESSION['partdb_config']['partdbDomain']; ?></p>
            <?php else: ?>
                <p>Access URL: http://localhost:<?php echo $_SESSION['partdb_config']['partdbPort']; ?></p>
            <?php endif; ?>
        </div>

        <div class="config-item">
            <h4>Next Steps</h4>
            <ol>
                <li>Import Node-RED flows from the provided file</li>
                <li>Configure MQTT settings in Node-RED</li>
                <li>Install the ViolentMonkey browser extension</li>
                <li>Import the ViolentMonkey scripts</li>
                <li>Start adding parts to your inventory!</li>
            </ol>
        </div>
    </div>

    <div class="button-group">
        <button class="button" onclick="window.location.href='/'">Finish Setup</button>
    </div>
</div> 