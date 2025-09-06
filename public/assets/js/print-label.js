// Print label inline without opening new window
function printLabel(orderId) {
    // Create hidden iframe
    const iframe = document.createElement('iframe');
    iframe.id = 'printFrame';
    iframe.style.position = 'absolute';
    iframe.style.top = '-9999px';
    iframe.style.left = '-9999px';
    iframe.style.width = '1px';
    iframe.style.height = '1px';
    iframe.style.border = 'none';
    
    // Set source to print page
    iframe.src = '/print-label-final.php?order=' + orderId;
    
    // Append to body
    document.body.appendChild(iframe);
    
    // Wait for iframe to load then print
    iframe.onload = function() {
        setTimeout(() => {
            try {
                iframe.contentWindow.print();
                
                // Remove iframe after printing
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 1000);
            } catch (e) {
                console.error('Print error:', e);
                // Fallback to opening new window if iframe print fails
                window.open('/print-label-final.php?order=' + orderId, '_blank');
            }
        }, 500);
    };
    
    // Handle load errors
    iframe.onerror = function() {
        console.error('Failed to load label');
        alert('Failed to load label. Please try again.');
        document.body.removeChild(iframe);
    };
}

// Alternative: Modal print preview
function printLabelModal(orderId) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.id = 'printModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    `;
    
    // Create modal content
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white;
        width: 90%;
        height: 90%;
        max-width: 800px;
        border-radius: 8px;
        position: relative;
        display: flex;
        flex-direction: column;
    `;
    
    // Create header with close button
    const header = document.createElement('div');
    header.style.cssText = `
        padding: 15px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    `;
    header.innerHTML = `
        <h3 style="margin: 0;">Print Label - Order #${orderId}</h3>
        <div>
            <button onclick="printModalContent()" style="
                padding: 8px 16px;
                background: #0066ff;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                margin-right: 10px;
            ">Print</button>
            <button onclick="closePrintModal()" style="
                padding: 8px 16px;
                background: #666;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            ">Close</button>
        </div>
    `;
    
    // Create iframe for label
    const iframe = document.createElement('iframe');
    iframe.id = 'modalPrintFrame';
    iframe.src = '/print-label-final.php?order=' + orderId;
    iframe.style.cssText = `
        flex: 1;
        border: none;
        width: 100%;
    `;
    
    // Assemble modal
    modalContent.appendChild(header);
    modalContent.appendChild(iframe);
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    // Close on overlay click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closePrintModal();
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function escapeHandler(e) {
        if (e.key === 'Escape') {
            closePrintModal();
            document.removeEventListener('keydown', escapeHandler);
        }
    });
}

function printModalContent() {
    const iframe = document.getElementById('modalPrintFrame');
    if (iframe && iframe.contentWindow) {
        try {
            iframe.contentWindow.print();
        } catch (e) {
            console.error('Print error:', e);
            window.print();
        }
    }
}

function closePrintModal() {
    const modal = document.getElementById('printModal');
    if (modal) {
        document.body.removeChild(modal);
    }
}

// Direct print without any UI
function printLabelDirect(orderId) {
    // Create completely hidden iframe
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = '/print-label-final.php?order=' + orderId;
    
    document.body.appendChild(iframe);
    
    iframe.onload = function() {
        // Auto-print when loaded
        setTimeout(() => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                
                // Clean up after print dialog closes
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 2000);
            } catch (e) {
                console.error('Print failed:', e);
                alert('Unable to print. Please try again.');
                document.body.removeChild(iframe);
            }
        }, 100);
    };
}