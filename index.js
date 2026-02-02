// ============================================
// SHEBAMILES - Interactive Animations & Features
// Modern JavaScript Enhancements
// ============================================

(function() {
    'use strict';

    // ============================================
    // SCROLL ANIMATIONS
    // ============================================
    
    const initScrollAnimations = () => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    // Optional: unobserve after animation
                    // observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Observe all elements with scroll-reveal class
        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });
    };

    // ============================================
    // RIPPLE EFFECT ON CLICK
    // ============================================
    
    const addRippleEffect = (e) => {
        const button = e.currentTarget;
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple-effect');

        button.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
    };

    const initRipples = () => {
        const rippleElements = document.querySelectorAll('.ripple, button, .btn-primary');
        rippleElements.forEach(el => {
            el.addEventListener('click', addRippleEffect);
        });
    };

    // ============================================
    // SMOOTH SCROLL TO ANCHORS
    // ============================================
    
    const initSmoothScroll = () => {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '#!') return;
                
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    };

    // ============================================
    // ANIMATED COUNTER
    // ============================================
    
    const animateCounter = (element) => {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };

        updateCounter();
    };

    const initCounters = () => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                    animateCounter(entry.target);
                    entry.target.classList.add('counted');
                    observer.unobserve(entry.target);
                }
            });
        });

        document.querySelectorAll('[data-target]').forEach(el => {
            observer.observe(el);
        });
    };

    // ============================================
    // PARALLAX SCROLL EFFECT
    // ============================================
    
    const initParallax = () => {
        const parallaxElements = document.querySelectorAll('[data-parallax]');
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(el => {
                const speed = el.getAttribute('data-parallax') || 0.5;
                const yPos = -(scrolled * speed);
                el.style.transform = `translateY(${yPos}px)`;
            });
        });
    };

    // ============================================
    // TYPEWRITER EFFECT
    // ============================================
    
    const typeWriter = (element, text, speed = 50) => {
        let i = 0;
        element.textContent = '';
        
        const type = () => {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(type, speed);
            }
        };
        
        type();
    };

    const initTypewriter = () => {
        document.querySelectorAll('[data-typewriter]').forEach(el => {
            const text = el.textContent;
            const speed = parseInt(el.getAttribute('data-typewriter')) || 50;
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        typeWriter(el, text, speed);
                        observer.unobserve(el);
                    }
                });
            });
            
            observer.observe(el);
        });
    };

    // ============================================
    // CARD TILT EFFECT
    // ============================================
    
    const initCardTilt = () => {
        const cards = document.querySelectorAll('.card-tilt');
        
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
            });
        });
    };

    // ============================================
    // NAVBAR SCROLL EFFECT
    // ============================================
    
    const initNavbarScroll = () => {
        const navbar = document.querySelector('header');
        if (!navbar) return;

        let lastScroll = 0;
        
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            // Add shadow on scroll
            if (currentScroll > 10) {
                navbar.classList.add('shadow-md');
            } else {
                navbar.classList.remove('shadow-md');
            }
            
            // Hide/show navbar on scroll
            if (currentScroll > lastScroll && currentScroll > 100) {
                navbar.style.transform = 'translateY(-100%)';
            } else {
                navbar.style.transform = 'translateY(0)';
            }
            
            lastScroll = currentScroll;
        });
    };

    // ============================================
    // LOADING ANIMATION
    // ============================================
    
    const initPageLoader = () => {
        window.addEventListener('load', () => {
            const loader = document.querySelector('.page-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 300);
            }
            
            // Trigger entrance animations
            document.body.classList.add('loaded');
        });
    };

    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    
    const showToast = (message, type = 'info', duration = 3000) => {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} animate-fade-in-right`;
        toast.textContent = message;
        
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f97316'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            font-weight: 500;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('animate-fade-out');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // Make toast available globally
    window.showToast = showToast;

    // ============================================
    // LAZY LOADING IMAGES
    // ============================================
    
    const initLazyLoad = () => {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    
                    if (src) {
                        img.src = src;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    };

    // ============================================
    // FORM VALIDATION ENHANCEMENTS
    // ============================================
    
    const initFormValidation = () => {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                
                let isValid = true;
                const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('border-red-500');
                        
                        // Add shake animation
                        input.classList.add('animate-wiggle');
                        setTimeout(() => input.classList.remove('animate-wiggle'), 500);
                    } else {
                        input.classList.remove('border-red-500');
                        input.classList.add('border-green-500');
                    }
                });
                
                if (isValid) {
                    showToast('Form submitted successfully!', 'success');
                    form.submit();
                } else {
                    showToast('Please fill in all required fields', 'error');
                }
            });
        });
    };

    // ============================================
    // DYNAMIC THEME TOGGLE
    // ============================================
    
    const initThemeToggle = () => {
        const toggleBtn = document.querySelector('[data-theme-toggle]');
        if (!toggleBtn) return;

        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', currentTheme === 'dark');

        toggleBtn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            
            // Add pulse animation
            toggleBtn.classList.add('animate-pulse');
            setTimeout(() => toggleBtn.classList.remove('animate-pulse'), 500);
        });
    };

    // ============================================
    // SEARCH FUNCTIONALITY
    // ============================================
    
    const initSearch = () => {
        const searchInputs = document.querySelectorAll('[data-search]');
        
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const targetSelector = input.getAttribute('data-search');
                const items = document.querySelectorAll(targetSelector);
                
                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = '';
                        item.classList.add('animate-fade-in');
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    };

    // ============================================
    // MODAL FUNCTIONALITY
    // ============================================
    
    const initModals = () => {
        const modalTriggers = document.querySelectorAll('[data-modal]');
        
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', () => {
                const modalId = trigger.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('animate-fade-in');
                }
            });
        });

        // Close modals
        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal');
                if (modal) {
                    modal.classList.add('animate-fade-out');
                    setTimeout(() => {
                        modal.classList.add('hidden');
                        modal.classList.remove('animate-fade-out');
                    }, 300);
                }
            });
        });
    };

    // ============================================
    // COPY TO CLIPBOARD
    // ============================================
    
    const initCopyButtons = () => {
        document.querySelectorAll('[data-copy]').forEach(btn => {
            btn.addEventListener('click', () => {
                const text = btn.getAttribute('data-copy');
                navigator.clipboard.writeText(text).then(() => {
                    showToast('Copied to clipboard!', 'success');
                });
            });
        });
    };

    // ============================================
    // INITIALIZE ALL FEATURES
    // ============================================
    
    const init = () => {
        initScrollAnimations();
        initRipples();
        initSmoothScroll();
        initCounters();
        initParallax();
        initTypewriter();
        initCardTilt();
        initNavbarScroll();
        initPageLoader();
        initLazyLoad();
        initFormValidation();
        initThemeToggle();
        initSearch();
        initModals();
        initCopyButtons();

        console.log('%c🚀 Shebamiles Interactive Features Loaded!', 'color: #f97316; font-size: 16px; font-weight: bold;');
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
