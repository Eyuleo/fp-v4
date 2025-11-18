/**
 * Form Loading States
 * Automatically applies loading states to form submit buttons
 * Usage: Add data-loading attribute to any form element
 */

;(function () {
  "use strict"

  /**
   * Initialize form loading functionality
   */
  function initFormLoading() {
    // Find all forms with data-loading attribute
    const forms = document.querySelectorAll("form[data-loading]")

    forms.forEach(function (form) {
      form.addEventListener("submit", function (e) {
        // Find the submit button
        const submitBtn = form.querySelector('button[type="submit"]')

        if (!submitBtn) {
          return // No submit button found, continue normally
        }

        // Prevent double submission
        if (submitBtn.disabled) {
          e.preventDefault()
          return
        }

        // Store original button content
        const originalContent = submitBtn.innerHTML
        const loadingText =
          submitBtn.getAttribute("data-loading-text") || "Processing..."

        // Apply loading state
        submitBtn.disabled = true
        submitBtn.classList.add("btn-loading")
        submitBtn.innerHTML =
          '<i class="fas fa-spinner fa-spin"></i> ' + loadingText

        // Store original content for potential restoration
        submitBtn.setAttribute("data-original-content", originalContent)
      })
    })
  }

  /**
   * Restore button to original state
   * Useful for AJAX forms or error handling
   */
  function restoreButton(button) {
    if (!button) return

    const originalContent = button.getAttribute("data-original-content")

    if (originalContent) {
      button.innerHTML = originalContent
      button.disabled = false
      button.classList.remove("btn-loading")
      button.removeAttribute("data-original-content")
    }
  }

  /**
   * Restore all buttons in a form
   */
  function restoreForm(form) {
    if (!form) return

    const submitBtn = form.querySelector('button[type="submit"]')
    restoreButton(submitBtn)
  }

  // Initialize on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initFormLoading)
  } else {
    initFormLoading()
  }

  // Expose utility functions globally for AJAX forms
  window.FormLoading = {
    restore: restoreButton,
    restoreForm: restoreForm,
    init: initFormLoading,
  }
})()
