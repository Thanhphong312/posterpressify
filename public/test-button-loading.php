<!DOCTYPE html>
<html>
<head>
    <title>Test Button Loading State</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px;
            max-width: 1000px;
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
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #0066ff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            cursor: pointer;
            border: none;
            font-size: 13px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        .btn-primary {
            background: #0066ff;
        }
        .print-btn {
            background: #0066ff;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .debug-output {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Print Button Loading State</h1>
        
        <div class="info-box">
            <strong>Testing the button loading state feature</strong><br>
            Open browser console to see debug messages
        </div>
        
        <div class="test-section">
            <h3>Test 1: Button with class and data-order-id</h3>
            <button class="print-btn btn btn-sm btn-primary" data-order-id="2038099">
                Print Label
            </button>
            <button class="btn btn-sm" onclick="testPrint1()">Test Print</button>
        </div>
        
        <div class="test-section">
            <h3>Test 2: Button with onclick attribute</h3>
            <button onclick="printLabel('2038100')" class="btn btn-primary">
                Print Label
            </button>
            <button class="btn btn-sm" onclick="testPrint2()">Test Print</button>
        </div>
        
        <div class="test-section">
            <h3>Test 3: Direct function calls</h3>
            <button class="btn" onclick="testDirectUpdate()">Test Direct Update</button>
            <button class="btn" onclick="testPrintFunction()">Test printLabel()</button>
        </div>
        
        <div class="debug-output" id="debugOutput">
            Debug output will appear here...
        </div>
    </div>
    
    <script src="/assets/js/print-label-advanced.js"></script>
    <script>
    // Override console.log to show in debug output
    const originalLog = console.log;
    const debugOutput = document.getElementById('debugOutput');
    
    console.log = function(...args) {
        originalLog.apply(console, args);
        const message = args.map(arg => 
            typeof arg === 'object' ? JSON.stringify(arg) : String(arg)
        ).join(' ');
        debugOutput.innerHTML += message + '<br>';
        debugOutput.scrollTop = debugOutput.scrollHeight;
    };
    
    function testPrint1() {
        console.log('=== Test 1: Printing order 2038099 ===');
        printLabel('2038099');
    }
    
    function testPrint2() {
        console.log('=== Test 2: Printing order 2038100 ===');
        printLabel('2038100');
    }
    
    function testDirectUpdate() {
        console.log('=== Test Direct Update ===');
        if (typeof labelPrinter !== 'undefined') {
            // Test loading state
            labelPrinter.updatePrintButton('2038099', 'loading');
            
            // Test complete state after 2 seconds
            setTimeout(() => {
                labelPrinter.updatePrintButton('2038099', 'complete');
            }, 2000);
            
            // Also test on second button
            labelPrinter.updatePrintButton('2038100', 'loading');
            setTimeout(() => {
                labelPrinter.updatePrintButton('2038100', 'complete');
            }, 2000);
        } else {
            console.log('labelPrinter not available!');
        }
    }
    
    function testPrintFunction() {
        console.log('=== Test printLabel function ===');
        // Test with the first button
        printLabel('2038099');
    }
    
    // Initial check
    console.log('Page loaded. labelPrinter available:', typeof labelPrinter !== 'undefined');
    if (typeof labelPrinter !== 'undefined') {
        console.log('labelPrinter mode:', labelPrinter.options.mode);
    }
    </script>
</body>
</html>