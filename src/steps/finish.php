<div class="finish-step">
    <div class="success-card">
        <div class="success-icon">✨</div>
        <h2>Setup Complete!</h2>
        <p>Your Pick'n'Light system has been successfully set up.</p>
    </div>

    <div class="service-cards">
        <div class="service-card">
            <img src="images/nodered-icon.png" alt="Node-RED Logo" class="service-icon">
            <h3>Node-RED</h3>
            <p>Access your Node-RED instance at:</p>
            <a href="http://localhost:1880" target="_blank" class="service-link">
                http://localhost:1880
            </a>
        </div>

        <div class="service-card">
            <img src="images/partdb-icon.png" alt="Part-DB Logo" class="service-icon">
            <h3>Part-DB</h3>
            <p>Access your Part-DB instance at:</p>
            <a href="http://localhost:8080" target="_blank" class="service-link">
                http://localhost:8080
            </a>
        </div>
    </div>

    <div class="next-steps">
        <h3>Next Steps</h3>
        <ul>
            <li>Configure your Node-RED flows</li>
            <li>Set up your parts inventory in Part-DB</li>
            <li>Connect your LED strips</li>
            <li>Start picking!</li>
        </ul>
    </div>

    <div class="documentation-link">
        <a href="https://github.com/yourusername/picknlight" target="_blank" class="button primary">
            View Documentation
        </a>
    </div>
</div>

<style>
.finish-step {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.success-card {
    background: var(--card-background);
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    margin-bottom: 40px;
    box-shadow: var(--shadow);
}

.success-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.service-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 40px 0;
}

.service-card {
    background: var(--card-background);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: var(--shadow);
    transition: transform 0.3s ease;
}

.service-card:hover {
    transform: translateY(-5px);
}

.service-icon {
    width: 64px;
    height: 64px;
    margin-bottom: 15px;
}

.service-link {
    display: inline-block;
    color: var(--primary-color);
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 20px;
    background: var(--background-color);
    margin-top: 10px;
    transition: all 0.3s ease;
}

.service-link:hover {
    background: var(--primary-color);
    color: white;
}

.next-steps {
    background: var(--card-background);
    border-radius: 12px;
    padding: 30px;
    margin: 40px 0;
    box-shadow: var(--shadow);
}

.next-steps ul {
    list-style-type: none;
    padding: 0;
}

.next-steps li {
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.next-steps li:last-child {
    border-bottom: none;
}

.documentation-link {
    text-align: center;
    margin-top: 40px;
}
</style> 