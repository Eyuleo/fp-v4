/**
 * Form Button Enhancement Module
 * 
 * Adds loading and disabled states to form submit buttons
 * - Shows spinner on form submission
 * - Disables button to prevent double-submits
 * - Adds accessibility attributes
 * - Configurable via data attributes
 */

(function() {
    'use strict';

    /**
     * Initialize button enhancement for all forms
     */
    function initializeFormButtons() {
        // Find all forms with submit buttons
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            // Get submit buttons in this form
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            
            submitButtons.forEach(button => {
                // Skip if already initialized
                if (button.dataset.buttonEnhanced) {
                    return;
                }
                
                // Mark as initialized
                button.dataset.buttonEnhanced = 'true';
                
                // Wrap button text for loading state
                if (button.tagName === 'BUTTON' && !button.querySelector('.btn-text')) {
                    const originalHTML = button.innerHTML;
                    button.innerHTML = `<span class="btn-text">${originalHTML}</span>`;
                    
                    // Add spinner element (hidden by default)
                    const spinner = document.createElement('span');
                    spinner.className = 'btn-spinner';
                    spinner.setAttribute('role', 'status');
                    spinner.setAttribute('aria-live', 'polite');
                    
                    const loadingText = button.dataset.loading || 'Loading...';
                    spinner.innerHTML = `
                        <i class="fas fa-spinner fa-spin-custom" aria-hidden="true"></i>
                        <span class="sr-only">${loadingText}</span>
                    `;
                    
                    button.appendChild(spinner);
                }
            });
            
            // Handle form submission
            form.addEventListener('submit', function(e) {
                // Check if form is already submitting
                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return;
                }
                
                // Mark form as submitting
                form.dataset.submitting = 'true';
                
                // Enable loading state on all submit buttons
                submitButtons.forEach(button => {
                    setLoadingState(button, true);
                });
                
                // If form submission is prevented (validation), restore state
                setTimeout(() => {
                    if (form.dataset.submitting === 'true') {
                        // Form is actually submitting, check if still on page after delay
                        setTimeout(() => {
                            // If we're still here, something prevented navigation
                            // This could be client-side validation
                            const stillOnPage = document.body.contains(form);
                            if (stillOnPage && form.dataset.submitting === 'true') {
                                // Check if any HTML5 validation failed
                                const isValid = form.checkValidity ? form.checkValidity() : true;
                                if (!isValid) {
                                    resetFormState(form, submitButtons);
                                }
                            }
                        }, 100);
                    }
                }, 0);
            });
            
            // Handle form reset
            form.addEventListener('reset', function() {
                resetFormState(form, submitButtons);
            });
            
            // Restore state on validation errors (for HTML5 validation)
            form.addEventListener('invalid', function() {
                resetFormState(form, submitButtons);
            }, true);
        });
    }
    
    /**
     * Set loading state on a button
     */
    function setLoadingState(button, isLoading) {
        if (isLoading) {
            // Store original state
            if (!button.dataset.originalDisabled) {
                button.dataset.originalDisabled = button.disabled ? 'true' : 'false';
            }
            
            // Add loading class and disable
            button.classList.add('is-loading');
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            
            // Show spinner if it exists
            const spinner = button.querySelector('.btn-spinner');
            if (spinner) {
                spinner.style.display = 'flex';
            }
        } else {
            // Remove loading class
            button.classList.remove('is-loading');
            button.removeAttribute('aria-busy');
            
            // Restore original disabled state
            const wasDisabled = button.dataset.originalDisabled === 'true';
            button.disabled = wasDisabled;
            delete button.dataset.originalDisabled;
            
            // Hide spinner
            const spinner = button.querySelector('.btn-spinner');
            if (spinner) {
                spinner.style.display = 'none';
            }
        }
    }
    
    /**
     * Reset form state after validation failure
     */
    function resetFormState(form, submitButtons) {
        form.dataset.submitting = 'false';
        submitButtons.forEach(button => {
            setLoadingState(button, false);
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeFormButtons);
    } else {
        initializeFormButtons();
    }
    
    // Re-initialize for dynamically added forms
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'FORM') {
                            initializeFormButtons();
                        } else if (node.querySelector && node.querySelector('form')) {
                            initializeFormButtons();
                        }
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
})();
