module.exports = {
    flowFile: 'flows.json',
    credentialSecret: false,
    adminAuth: null,
    httpNodeAuth: null,
    httpStaticAuth: null,
    uiPort: process.env.PORT || 1880,
    mqttReconnectTime: 15000,
    serialReconnectTime: 15000,
    debugMaxLength: 1000,
    functionGlobalContext: {},
    exportGlobalContextKeys: false,
    logging: {
        console: {
            level: "info",
            metrics: false,
            audit: false
        }
    },
    editorTheme: {
        projects: {
            enabled: true
        }
    }
}; 