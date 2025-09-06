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
        // Show loading indicator
        this.showPrintLoading(orderId);
        
        // Log print action to timeline
        fetch('/log-print.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId
        }).catch(err => console.log('Could not log print:', err));
        
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
        iframe.onload = () => {
            // Hide loading indicator after a short delay
            setTimeout(() => {
                this.hidePrintLoading();
            }, 1500);
            // Just cleanup after a delay
            cleanup();
        };
        
        iframe.onerror = () => {
            this.hidePrintLoading();
            this.showError('Failed to load label');
            cleanup();
        };
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
     * Show print loading indicator
     */
    showPrintLoading(orderId) {
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
            z-index: 10000;
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
        const loadingOverlay = document.getElementById('printLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => {
                if (loadingOverlay && loadingOverlay.parentNode) {
                    loadingOverlay.parentNode.removeChild(loadingOverlay);
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
    labelPrinter.print(orderId);
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