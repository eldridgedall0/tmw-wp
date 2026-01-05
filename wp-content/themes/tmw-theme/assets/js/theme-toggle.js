/**
 * Theme Toggle - Light/Dark Mode
 *
 * Saves preference to:
 * 1. localStorage (instant)
 * 2. User meta via AJAX (persists across devices for logged-in users)
 *
 * @package flavor-starter-flavor
 */

(function() {
    'use strict';

    const STORAGE_KEY = 'tmw_theme_mode';
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', initThemeToggle);
    
    // Also run immediately if DOM already loaded
    if (document.readyState !== 'loading') {
        initThemeToggle();
    }

    function initThemeToggle() {
        // Set initial theme from localStorage before paint
        applyStoredTheme();
        
        // Bind toggle buttons
        document.querySelectorAll('.tmw-theme-toggle').forEach(function(btn) {
            btn.addEventListener('click', toggleTheme);
        });
    }

    /**
     * Apply stored theme preference
     */
    function applyStoredTheme() {
        // Priority: localStorage > server-provided default
        let theme = localStorage.getItem(STORAGE_KEY);
        
        if (!theme && typeof tmwData !== 'undefined') {
            theme = tmwData.currentTheme || tmwData.defaultTheme || 'dark';
        }
        
        if (!theme) {
            // Check system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                theme = 'light';
            } else {
                theme = 'dark';
            }
        }
        
        setTheme(theme, false);
    }

    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        setTheme(newTheme, true);
    }

    /**
     * Set the theme
     *
     * @param {string} theme - 'dark' or 'light'
     * @param {boolean} savePreference - Whether to save to storage
     */
    function setTheme(theme, savePreference) {
        // Add transition class for smooth change
        if (savePreference) {
            document.documentElement.classList.add('theme-transitioning');
            setTimeout(function() {
                document.documentElement.classList.remove('theme-transitioning');
            }, 300);
        }
        
        // Apply theme
        document.documentElement.setAttribute('data-theme', theme);
        document.body.classList.remove('tmw-theme-dark', 'tmw-theme-light');
        document.body.classList.add('tmw-theme-' + theme);
        
        // Update toggle button icons
        updateToggleIcons(theme);
        
        // Save preference
        if (savePreference) {
            saveThemePreference(theme);
        }
    }

    /**
     * Update toggle button icons based on current theme
     */
    function updateToggleIcons(theme) {
        document.querySelectorAll('.tmw-theme-toggle').forEach(function(btn) {
            const sunIcon = btn.querySelector('.tmw-icon-light');
            const moonIcon = btn.querySelector('.tmw-icon-dark');
            
            if (sunIcon && moonIcon) {
                if (theme === 'dark') {
                    sunIcon.style.display = 'block';
                    moonIcon.style.display = 'none';
                } else {
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'block';
                }
            }
        });
    }

    /**
     * Save theme preference
     */
    function saveThemePreference(theme) {
        // Save to localStorage
        localStorage.setItem(STORAGE_KEY, theme);
        
        // Save to user meta if logged in
        if (typeof tmwData !== 'undefined' && tmwData.isLoggedIn) {
            const formData = new FormData();
            formData.append('action', 'tmw_toggle_theme');
            formData.append('nonce', tmwData.nonce);
            formData.append('mode', theme);
            
            fetch(tmwData.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }).catch(function(error) {
                console.log('Theme preference save failed:', error);
            });
        }
    }

    /**
     * Get current theme
     */
    function getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || 'dark';
    }

    // Expose API
    window.TMW = window.TMW || {};
    window.TMW.theme = {
        toggle: toggleTheme,
        set: setTheme,
        get: getCurrentTheme
    };

    // Listen for system preference changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            // Only auto-switch if user hasn't set a preference
            if (!localStorage.getItem(STORAGE_KEY)) {
                setTheme(e.matches ? 'dark' : 'light', false);
            }
        });
    }

})();

// Run immediately to prevent flash
(function() {
    const theme = localStorage.getItem('tmw_theme_mode');
    if (theme) {
        document.documentElement.setAttribute('data-theme', theme);
    }
})();
