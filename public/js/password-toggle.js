/**
 * Password Visibility Toggle Component
 * Provides functionality to toggle password field visibility
 */

;(function () {
  "use strict"

  /**
   * Initialize password toggle functionality for all password fields
   */
  function initPasswordToggles() {
    // Find all password input wrappers
    const passwordWrappers = document.querySelectorAll(
      ".password-input-wrapper"
    )

    passwordWrappers.forEach((wrapper) => {
      const input = wrapper.querySelector(
        'input[type="password"], input[type="text"]'
      )
      const toggleBtn = wrapper.querySelector(".password-toggle-btn")

      if (!input || !toggleBtn) return

      // Add click event listener to toggle button
      toggleBtn.addEventListener("click", function () {
        togglePasswordVisibility(input, toggleBtn)
      })
    })
  }

  /**
   * Toggle password visibility for a specific input field
   * @param {HTMLInputElement} input - The password input element
   * @param {HTMLButtonElement} toggleBtn - The toggle button element
   */
  function togglePasswordVisibility(input, toggleBtn) {
    const eyeIcon = toggleBtn.querySelector(".eye-icon")
    const eyeSlashIcon = toggleBtn.querySelector(".eye-slash-icon")

    if (input.type === "password") {
      // Show password
      input.type = "text"
      eyeIcon.classList.add("hidden")
      eyeSlashIcon.classList.remove("hidden")
      toggleBtn.setAttribute("aria-label", "Hide password")
    } else {
      // Hide password
      input.type = "password"
      eyeIcon.classList.remove("hidden")
      eyeSlashIcon.classList.add("hidden")
      toggleBtn.setAttribute("aria-label", "Show password")
    }
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPasswordToggles)
  } else {
    initPasswordToggles()
  }
})()
