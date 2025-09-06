<!DOCTYPE html>
<html>
<head>
    <title>Test Simple Loading</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            margin: 0 auto;
        }
        .btn {
            padding: 8px 16px;
            background: #0066ff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .btn-primary {
            background: #0066ff;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Simple Button Loading</h1>
        
        <button id="testBtn1" class="btn btn-primary" onclick="testLoading(this)">
            Print Label
        </button>
        
        <button id="testBtn2" class="btn btn-primary print-btn" data-order-id="123">
            Print Label
        </button>
        
        <button class="btn" onclick="directTest()">Direct Test</button>
    </div>
    
    <script>
    function testLoading(button) {
        // Store original text
        const originalText = button.textContent;
        
        // Set loading state
        button.disabled = true;
        button.innerHTML = `
            <span style="display: inline-flex; align-items: center; gap: 6px;">
                <span style="
                    width: 14px;
                    height: 14px;
                    border: 2px solid #ffffff;
                    border-top-color: transparent;
                    border-radius: 50%;
                    display: inline-block;
                    animation: spin 0.8s linear infinite;
                "></span>
                Loading Print...
            </span>
        `;
        
        // Reset after 2 seconds
        setTimeout(() => {
            button.disabled = false;
            button.innerHTML = '✓ ' + originalText;
            
            setTimeout(() => {
                button.textContent = originalText;
            }, 1000);
        }, 2000);
    }
    
    function directTest() {
        const btn = document.getElementById('testBtn2');
        testLoading(btn);
    }
    </script>
</body>
</html>