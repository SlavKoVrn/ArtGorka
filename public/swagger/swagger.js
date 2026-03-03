    window.onload = function () {
        // Build a system
        const ui = SwaggerUIBundle({
            url: "/json-schema",
            dom_id: '#swagger-ui',
            deepLinking: true,
            jsonEditor: true,
            displayRequestDuration: true,
            filter: true,
            validatorUrl: null,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout"
        });
        window.ui = ui
    }
