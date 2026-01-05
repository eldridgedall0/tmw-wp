/**
 * TrackMyWrench Main JavaScript
 *
 * @package flavor-starter-flavor
 */

(function() {
    'use strict';

    // DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        TMW.init();
    });

    // TMW Global Object
    window.TMW = window.TMW || {};

    TMW.init = function() {
        TMW.initAlerts();
        TMW.initDropdowns();
        TMW.initSmoothScroll();
        TMW.initFAQ();
        TMW.initPricingToggle();
    };

    // Alert Dismissal
    TMW.initAlerts = function() {
        document.querySelectorAll('.tmw-alert-close').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const alert = this.closest('.tmw-alert');
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        alert.remove();
                    }, 200);
                }
            });
        });
    };

    // Dropdowns
    TMW.initDropdowns = function() {
        const userMenuToggle = document.querySelector('.tmw-user-menu-toggle');
        const userMenu = document.querySelector('.tmw-user-menu');
        
        if (userMenuToggle && userMenu) {
            userMenuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('is-open');
            });

            document.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target)) {
                    userMenu.classList.remove('is-open');
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    userMenu.classList.remove('is-open');
                }
            });
        }
    };

    // Smooth Scroll
    TMW.initSmoothScroll = function() {
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#' || targetId === '#0') return;

                const target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    const headerHeight = document.querySelector('.tmw-header')?.offsetHeight || 0;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });

                    history.pushState(null, null, targetId);
                }
            });
        });
    };

    // FAQ Accordion
    TMW.initFAQ = function() {
        document.querySelectorAll('.tmw-faq-question').forEach(function(question) {
            question.addEventListener('click', function() {
                const item = this.closest('.tmw-faq-item');
                const isOpen = item.classList.contains('is-open');
                
                document.querySelectorAll('.tmw-faq-item.is-open').forEach(function(openItem) {
                    if (openItem !== item) {
                        openItem.classList.remove('is-open');
                    }
                });
                
                item.classList.toggle('is-open', !isOpen);
            });
        });
    };

    // Pricing Toggle
    TMW.initPricingToggle = function() {
        const toggle = document.querySelector('.tmw-pricing-switch');
        if (!toggle) return;

        toggle.addEventListener('click', function() {
            const isYearly = toggle.classList.toggle('active');
            
            document.querySelectorAll('[data-period]').forEach(function(label) {
                label.classList.toggle('active', 
                    (isYearly && label.dataset.period === 'yearly') ||
                    (!isYearly && label.dataset.period === 'monthly')
                );
            });
            
            document.querySelectorAll('[data-price-monthly][data-price-yearly]').forEach(function(el) {
                el.textContent = isYearly ? el.dataset.priceYearly : el.dataset.priceMonthly;
            });

            document.querySelectorAll('.tmw-pricing-period').forEach(function(period) {
                period.textContent = isYearly ? '/year' : '/month';
            });
        });
    };

    // AJAX Helper
    TMW.ajax = function(action, data, callback) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', typeof tmwData !== 'undefined' ? tmwData.nonce : '');
        
        for (const key in data) {
            formData.append(key, data[key]);
        }

        fetch(typeof tmwData !== 'undefined' ? tmwData.ajaxUrl : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(result => { if (callback) callback(result); })
        .catch(error => {
            console.error('TMW Ajax Error:', error);
            if (callback) callback({ success: false, data: { message: 'An error occurred.' }});
        });
    };

    // Notification Helper
    TMW.notify = function(message, type) {
        type = type || 'info';
        
        const alert = document.createElement('div');
        alert.className = 'tmw-alert tmw-alert-' + type + ' tmw-alert-dismissible';
        alert.innerHTML = '<span class="tmw-alert-message">' + message + '</span>' +
                         '<button type="button" class="tmw-alert-close"><i class="fas fa-times"></i></button>';
        
        let container = document.querySelector('.tmw-alerts-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'tmw-alerts-container';
            container.style.cssText = 'position:fixed;top:100px;right:20px;z-index:9999;max-width:400px;';
            document.body.appendChild(container);
        }
        
        container.appendChild(alert);
        
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
        
        alert.querySelector('.tmw-alert-close').addEventListener('click', () => alert.remove());
    };

    // Debounce Utility
    TMW.debounce = function(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    };

})();
