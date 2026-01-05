/**
 * Mobile Navigation
 *
 * Hamburger menu toggle for mobile devices
 *
 * @package flavor-starter-flavor
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', initMobileNav);

    function initMobileNav() {
        const toggle = document.querySelector('.tmw-mobile-toggle');
        const nav = document.querySelector('.tmw-nav-mobile');
        const body = document.body;

        if (!toggle || !nav) return;

        // Toggle menu
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMenu();
        });

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                closeMenu();
            }
        });

        // Close on link click
        nav.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                closeMenu();
            });
        });

        // Close on resize to desktop
        window.addEventListener('resize', debounce(function() {
            if (window.innerWidth >= 1024 && nav.classList.contains('is-open')) {
                closeMenu();
            }
        }, 150));

        function toggleMenu() {
            const isOpen = nav.classList.contains('is-open');
            if (isOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        }

        function openMenu() {
            nav.classList.add('is-open');
            toggle.classList.add('is-active');
            toggle.setAttribute('aria-expanded', 'true');
            body.style.overflow = 'hidden';
            
            // Focus first link for accessibility
            const firstLink = nav.querySelector('a');
            if (firstLink) {
                setTimeout(function() {
                    firstLink.focus();
                }, 100);
            }
        }

        function closeMenu() {
            nav.classList.remove('is-open');
            toggle.classList.remove('is-active');
            toggle.setAttribute('aria-expanded', 'false');
            body.style.overflow = '';
            
            // Return focus to toggle
            toggle.focus();
        }

        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }

        // Trap focus within mobile nav when open
        nav.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab') return;
            
            const focusableElements = nav.querySelectorAll(
                'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
            );
            
            if (focusableElements.length === 0) return;
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        });
    }

})();
