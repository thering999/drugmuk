<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\CSRF::metaTag() ?>
    <title><?= $title ?? 'Drugmuk' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Your content here -->
    
    <script>
        // Auto-include CSRF token in all AJAX requests
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (csrfToken) {
                // Fetch API
                const originalFetch = window.fetch;
                window.fetch = function(url, options = {}) {
                    if (options.method && options.method.toUpperCase() !== 'GET') {
                        options.headers = options.headers || {};
                        options.headers['X-CSRF-Token'] = csrfToken;
                    }
                    return originalFetch(url, options);
                };
                
                // XMLHttpRequest
                const originalOpen = XMLHttpRequest.prototype.open;
                XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
                    this._method = method;
                    return originalOpen.apply(this, arguments);
                };
                
                const originalSend = XMLHttpRequest.prototype.send;
                XMLHttpRequest.prototype.send = function(data) {
                    if (this._method && this._method.toUpperCase() !== 'GET') {
                        this.setRequestHeader('X-CSRF-Token', csrfToken);
                    }
                    return originalSend.apply(this, arguments);
                };
            }
        });
    </script>
</body>
</html>
