/**
 * Advanced Print Label System
 * Supports: inline printing, modal preview, direct print
 */

class LabelPrinter {
    constructor(options = {}) {
        this.options = {
            mode: options.mode || 'direct', // 'direct', 'modal', 'iframe'
            autoprint: options.autoprint !== false,
            showPreview: options.showPreview || false,
            ...options
        };
    }

    /**
     * Main print function
     */
    print(orderId) {
        switch(this.options.mode) {
            case 'modal':
                this.printWithModal(orderId);
                break;
            case 'iframe':
                this.printWithIframe(orderId);
                break;
            case 'direct':
            default:
                this.printDirect(orderId);
                break;
        }
    }

    /**
     * Direct print using hidden iframe (no UI)
     */
    printDirect(orderId) {
        console.log('printDirect called for order:', orderId);
        
        // Button loading is handled in printLabel function
        
        // Show loading indicator immediately (optional - can be disabled)
        // this.showPrintLoading(orderId);
        
        // Track when loading started to ensure minimum display time
        const loadingStartTime = Date.now();
        
        // Log print action to timeline
        fetch('/log-print.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId
        }).catch(err => console.log('Could not log print:', err));
        
        // Create iframe after a tiny delay to ensure loading indicator is visible
        setTimeout(() => {
            const frameId = 'printFrame_' + Date.now();
            const iframe = document.createElement('iframe');
            iframe.id = frameId;
            iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;left:-9999px;';
            
            // Use the once-only print version
            iframe.src = `/print-label-once.php?order=${orderId}`;
            
            document.body.appendChild(iframe);
            
            // Set up cleanup
            const cleanup = () => {
                setTimeout(() => {
                    const frame = document.getElementById(frameId);
                    if (frame) {
                        document.body.removeChild(frame);
                    }
                }, 5000); // Give more time for printing
            };
            
            // Don't try to print from parent - let iframe handle it
            const self = this; // Store reference to this
            iframe.onload = () => {
                console.log('Print iframe loaded, keeping loading indicator visible for 2 seconds');
                // Calculate remaining time to ensure 2 seconds minimum display
                const elapsedTime = Date.now() - loadingStartTime;
                const remainingTime = Math.max(2000 - elapsedTime, 0);
                
                // Keep loading indicator visible for at least 2 seconds total
                setTimeout(() => {
                    // self.hidePrintLoading();
                    // Button update is handled in printLabel function
                }, remainingTime);
                
                // Just cleanup after a delay
                cleanup();
            };
            
            iframe.onerror = () => {
                // Ensure 2 seconds minimum display even on error
                const elapsedTime = Date.now() - loadingStartTime;
                const remainingTime = Math.max(2000 - elapsedTime, 0);
                
                setTimeout(() => {
                    // self.hidePrintLoading();
                    // Button update is handled in printLabel function
                    self.showError('Failed to load label');
                }, remainingTime);
                cleanup();
            };
        }, 50); // Small delay to ensure loading indicator renders first
    }

    /**
     * Print with modal preview
     */
    printWithModal(orderId) {
        // Remove any existing modal
        this.closeModal();
        
        // Create modal HTML
        const modalHtml = `
            <div id="printModalOverlay" class="print-modal-overlay">
                <div class="print-modal-container">
                    <div class="print-modal-header">
                        <h3 class="print-modal-title">Label - Order #${orderId}</h3>
                        <div class="print-modal-actions">
                            <button class="print-modal-btn print-modal-btn-primary" onclick="labelPrinter.printFromModal()">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                                </svg>
                                Print
                            </button>
                            <button class="print-modal-btn print-modal-btn-secondary" onclick="labelPrinter.closeModal()">
                                Close
                            </button>
                        </div>
                    </div>
                    <div class="print-modal-body">
                        <div class="print-modal-loading">
                            <div class="print-modal-spinner"></div>
                            <div>Loading label...</div>
                        </div>
                        <iframe id="printModalFrame" 
                                class="print-modal-iframe" 
                                src="/print-label-final.php?order=${orderId}&autoprint=0"
                                style="display:none;"
                                onload="labelPrinter.onModalFrameLoad()"></iframe>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer.firstElementChild);
        
        // Add CSS if not already loaded
        if (!document.getElementById('printModalCSS')) {
            const link = document.createElement('link');
            link.id = 'printModalCSS';
            link.rel = 'stylesheet';
            link.href = '/assets/css/print-modal.css';
            document.head.appendChild(link);
        }
        
        // Close on overlay click
        document.getElementById('printModalOverlay').addEventListener('click', (e) => {
            if (e.target.id === 'printModalOverlay') {
                this.closeModal();
            }
        });
        
        // Close on Escape
        this.escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        };
        document.addEventListener('keydown', this.escapeHandler);
        
        // Auto-print if configured
        if (this.options.autoprint) {
            setTimeout(() => {
                this.printFromModal();
            }, 1500);
        }
    }

    /**
     * Called when modal iframe loads
     */
    onModalFrameLoad() {
        const loading = document.querySelector('.print-modal-loading');
        const frame = document.getElementById('printModalFrame');
        if (loading) loading.style.display = 'none';
        if (frame) frame.style.display = 'block';
    }

    /**
     * Print from modal
     */
    printFromModal() {
        const frame = document.getElementById('printModalFrame');
        if (frame && frame.contentWindow) {
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (e) {
                console.error('Print error:', e);
                window.print();
            }
        }
    }

    /**
     * Close modal
     */
    closeModal() {
        const modal = document.getElementById('printModalOverlay');
        if (modal) {
            modal.remove();
        }
        if (this.escapeHandler) {
            document.removeEventListener('keydown', this.escapeHandler);
        }
    }

    /**
     * Update print button state
     */
    updatePrintButton(orderId, state) {
        // Ensure orderId is a string
        orderId = String(orderId);
        console.log('updatePrintButton called:', orderId, state);
        
        // Find all print buttons for this order - handle both numeric and string order IDs
        const selectors = [
            `.print-btn[data-order-id="${orderId}"]`,
            `button[onclick*="printLabel('${orderId}')"]`,
            `button[onclick*="printLabel(${orderId})"]`,
            `button[onclick*="'${orderId}'"]`
        ];
        
        const buttons = document.querySelectorAll(selectors.join(', '));
        
        console.log('Found buttons:', buttons.length, 'with selectors:', selectors);
        
        buttons.forEach(button => {
            const originalText = button.getAttribute('data-original-text') || button.textContent.trim();
            
            if (state === 'loading') {
                console.log('Setting button to loading state');
                // Store original text
                button.setAttribute('data-original-text', originalText);
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
                // Add loading class
                button.classList.add('btn-loading');
                
                // Add animation style if not exists
                if (!document.getElementById('btnLoadingStyles')) {
                    const style = document.createElement('style');
                    style.id = 'btnLoadingStyles';
                    style.textContent = `
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                        .btn-loading {
                            background: #17a2b8 !important;
                            cursor: wait !important;
                            position: relative;
                            overflow: hidden;
                        }
                        .btn-loading::after {
                            content: '';
                            position: absolute;
                            top: 0;
                            left: -100%;
                            width: 100%;
                            height: 100%;
                            background: linear-gradient(
                                90deg, 
                                transparent, 
                                rgba(255, 255, 255, 0.2), 
                                transparent
                            );
                            animation: shimmer 1.5s infinite;
                        }
                        @keyframes shimmer {
                            0% { left: -100%; }
                            100% { left: 100%; }
                        }
                    `;
                    document.head.appendChild(style);
                }
            } else if (state === 'complete') {
                button.disabled = false;
                button.innerHTML = `✓ ${originalText}`;
                button.classList.remove('btn-loading');
                button.classList.add('btn-printed');
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('btn-printed');
                }, 3000);
            } else if (state === 'error') {
                button.disabled = false;
                button.innerHTML = `⚠️ ${originalText}`;
                button.classList.remove('btn-loading');
                button.classList.add('btn-error');
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('btn-error');
                }, 3000);
            }
        });
    }
    
    /**
     * Show print loading indicator
     */
    showPrintLoading(orderId) {
        console.log('Showing print loading for order:', orderId);
        
        // Remove any existing loading indicator
        this.hidePrintLoading();
        
        // Create loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'printLoadingOverlay';
        loadingOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            animation: fadeIn 0.2s ease;
        `;
        
        // Create loading content
        const loadingContent = document.createElement('div');
        loadingContent.style.cssText = `
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: scaleIn 0.3s ease;
            z-index: 100000;
            position: relative;
        `;
        
        loadingContent.innerHTML = `
            <div style="margin-bottom: 20px;">
                <div style="
                    width: 60px;
                    height: 60px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #0066ff;
                    border-radius: 50%;
                    margin: 0 auto;
                    animation: spin 1s linear infinite;
                "></div>
            </div>
            <h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 18px;">
                🖨️ Loading Print Label
            </h3>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">
                Order #${orderId}
            </p>
            <p style="margin: 10px 0 0 0; color: #95a5a6; font-size: 12px;">
                Please wait...
            </p>
        `;
        
        loadingOverlay.appendChild(loadingContent);
        document.body.appendChild(loadingOverlay);
        
        // Force browser to repaint
        loadingOverlay.offsetHeight;
        
        console.log('Print loading overlay added to page');
        
        // Add animation styles if not already present
        if (!document.getElementById('printLoadingStyles')) {
            const style = document.createElement('style');
            style.id = 'printLoadingStyles';
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                @keyframes scaleIn {
                    from { transform: scale(0.8); opacity: 0; }
                    to { transform: scale(1); opacity: 1; }
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    /**
     * Hide print loading indicator
     */
    hidePrintLoading() {
        console.log('Hiding print loading indicator');
        const loadingOverlay = document.getElementById('printLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => {
                if (loadingOverlay && loadingOverlay.parentNode) {
                    loadingOverlay.parentNode.removeChild(loadingOverlay);
                    console.log('Print loading indicator removed');
                }
            }, 200);
        }
    }
    
    /**
     * Show error message
     */
    showError(message) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10001;
            animation: slideInRight 0.3s ease;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
}

// Create global instance
const labelPrinter = new LabelPrinter({
    mode: 'direct', // Change to 'modal' for preview
    autoprint: true
});

// Global function for backward compatibility
function printLabel(orderId) {
    console.log('printLabel called with orderId:', orderId, 'type:', typeof orderId);
    
    // Directly update any print button being clicked
    if (event && event.target) {
        const button = event.target.closest('button');
        if (button && button.textContent.includes('Print')) {
            updatePrintButtonDirect(button, 'loading');
            
            // Reset after 2 seconds
            setTimeout(() => {
                updatePrintButtonDirect(button, 'complete');
            }, 2000);
        }
    }
    
    // Also try to find buttons by order ID
    updateAllPrintButtons(orderId, 'loading');
    setTimeout(() => {
        updateAllPrintButtons(orderId, 'complete');
    }, 2000);
    
    // Ensure orderId is a string
    labelPrinter.print(String(orderId));
}

// Direct button update function
function updatePrintButtonDirect(button, state) {
    if (!button) return;
    
    const originalText = button.getAttribute('data-original-text') || button.textContent.trim();
    
    if (state === 'loading') {
        button.setAttribute('data-original-text', originalText);
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
        
        // Add style if needed
        if (!document.getElementById('btnSpinStyle')) {
            const style = document.createElement('style');
            style.id = 'btnSpinStyle';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    } else if (state === 'complete') {
        button.disabled = false;
        button.innerHTML = '✓ ' + originalText;
        
        setTimeout(() => {
            button.textContent = originalText;
        }, 1500);
    }
}

// Update all print buttons for an order
function updateAllPrintButtons(orderId, state) {
    const buttons = document.querySelectorAll(
        `.print-btn[data-order-id="${orderId}"], 
         button[onclick*="printLabel"]`
    );
    
    buttons.forEach(button => {
        if (button.textContent.includes('Print')) {
            updatePrintButtonDirect(button, state);
        }
    });
}

// Alternative functions for different modes
function printLabelWithPreview(orderId) {
    const printer = new LabelPrinter({ mode: 'modal', autoprint: false });
    printer.print(orderId);
}

function printLabelDirect(orderId) {
    const printer = new LabelPrinter({ mode: 'direct' });
    printer.print(orderId);
}