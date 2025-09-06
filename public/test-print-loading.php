<!DOCTYPE html>
<html>
<head>
    <title>Test Print Loading</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #0066ff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 10px;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn-success {
            background: #28a745;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖨️ Test Print Loading Indicator</h1>
        
        <div class="info-box">
            <strong>Test the print loading indicator</strong><br>
            Click the buttons below to test if the loading indicator appears when printing.
        </div>
        
        <div style="margin: 20px 0;">
            <button class="btn" onclick="testPrintDirect()">
                Test Direct Print (Order #2038099)
            </button>
            <button class="btn btn-success" onclick="testShipWithPrint()">
                Test Ship + Auto Print
            </button>
        </div>
        
        <hr style="margin: 30px 0;">
        
        <h3>Expected Behavior:</h3>
        <ol>
            <li>Click button → Loading overlay appears immediately</li>
            <li>Shows "🖨️ Loading Print Label" with spinner</li>
            <li>Shows order number</li>
            <li>Automatically disappears after 1.5 seconds</li>
        </ol>
    </div>
    
    <script src="/assets/js/print-label-advanced.js"></script>
    <script>
    function testPrintDirect() {
        console.log('Testing direct print...');
        printLabel('2038099');
    }
    
    function testShipWithPrint() {
        console.log('Testing ship with auto print...');
        // Simulate shipping success then print
        setTimeout(() => {
            console.log('Auto-printing after ship...');
            printLabel('2038099');
        }, 500);
    }
    
    // Debug: Check if labelPrinter is available
    console.log('labelPrinter available:', typeof labelPrinter !== 'undefined');
    if (typeof labelPrinter !== 'undefined') {
        console.log('labelPrinter mode:', labelPrinter.options.mode);
    }
    </script>
</body>
</html>