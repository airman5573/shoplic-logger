/**
 * Shoplic Logger Frontend
 * 
 * Frontend JavaScript library for Shoplic Logger
 * Direct function API with 8 parameters
 */

(function(window) {
    'use strict';

    // Configuration from WordPress
    const config = window.slConfig || {};
    
    // Log queue for batching
    let logQueue = [];
    let batchTimer = null;
    
    // Valid log levels
    const LOG_LEVELS = {
        LOG: 'LOG',
        ERROR: 'ERROR',
        INFO: 'INFO',
        DEBUG: 'DEBUG',
        WARNING: 'WARNING'
    };
    
    /**
     * Main logging function
     * @param {string} log_level - LOG, ERROR, INFO, DEBUG, WARNING
     * @param {string} plugin_name - Plugin or theme name
     * @param {string} file_path - Absolute file path
     * @param {string} class_name - Class name (can be empty string)
     * @param {string} function_name - Function name (can be empty string)
     * @param {string} message - Log message
     * @param {*} data - Additional data (optional)
     */
    function sl(log_level, plugin_name, file_path, class_name, function_name, message, data = null) {
        // Validate log level
        if (!LOG_LEVELS[log_level]) {
            log_level = LOG_LEVELS.LOG;
        }
        
        // Add to queue
        logQueue.push({
            level: log_level,
            plugin_name: plugin_name,
            file_path: file_path,
            class_name: class_name,
            function_name: function_name,
            message: message,
            data: data,
            timestamp: new Date().toISOString()
        });
        
        // If queue is getting large, send immediately
        if (logQueue.length >= 50) {
            sendBatch();
        }
    }
    
    /**
     * Send batched logs to server
     */
    function sendBatch() {
        // No logs to send
        if (logQueue.length === 0) {
            return;
        }
        
        // Get logs and clear queue
        const logs = [...logQueue];
        logQueue = [];
        
        // Prepare request
        const url = config.restUrl + 'shoplic-logger/v1/log';
        const data = {
            logs: logs
        };
        
        // Send request
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(result => {
            // Log successful batch send to console in development
            if (config.wpDebug) {
                console.log('Shoplic Logger: Batch sent successfully', result);
            }
        })
        .catch(error => {
            // Log error to console
            console.error('Shoplic Logger: Failed to send logs', error);
        });
    }
    
    /**
     * Start the batch timer
     */
    function startBatchTimer() {
        // Send logs every 2 seconds
        batchTimer = setInterval(() => {
            sendBatch();
        }, 2000);
        
        // Send logs before page unload
        window.addEventListener('beforeunload', () => {
            sendBatch();
        });
    }
    
    // Initialize batch timer
    startBatchTimer();
    
    // Expose sl function to global scope
    window.sl = sl;
    
})(window);