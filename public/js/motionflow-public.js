
/**
 * MotionFlow - Main JavaScript
 * Digital Commerce, Redefined
 * 
 * This file contains the core functionality for the MotionFlow plugin.
 */

// Create MotionFlow namespace
window.MotionFlow = window.MotionFlow || {};

(function($, window, document) {
    'use strict';

    /**
     * Core functionality
     */
    MotionFlow.Core = {
        /**
         * Initialize the plugin
         * 
         * @param {Object} options Configuration options
         */
        init: function(options) {
            // Initialize settings
            this.settings = $.extend({
                ajaxUrl: '',
                nonce: {
                    filter: '',
                    cart: '',
                    modal: '',
                    analytics: ''
                },
                settings: {
                    animations: true,
                    drag_to_cart: true,
                    modal: true,
                    lazy_load: true,
                    debug: false
                },
                selectors: {
                    product: '.motionflow-product',
                    dragHandle: '.motionflow-drag-handle',
                    cartContainer: '.motionflow-cart-sidebar',
                    cartButton: '.motionflow-cart-button',
                    filterForm: '.motionflow-filters-form',
                    filterControl: '.motionflow-filter-control',
                    gridContainer: '.motionflow-grid'
                }
            }, options);

            // Initialize components
            this.initComponents();
            
            // Initialize events
            this.bindEvents();
            
            this.log('MotionFlow initialized', this.settings);
        },

        /**
         * Initialize components
         */
        initComponents: function() {
            // Initialize AJAX component
            if (window.MotionFlow.AJAX) {
                window.MotionFlow.AJAX.init({
                    ajaxUrl: this.settings.ajaxUrl,
                    nonce: this.settings.nonce,
                    selectors: this.settings.selectors,
                    debug: this.settings.settings.debug
                });
            }
            
            // Initialize LazyLoad component
            if (window.MotionFlow.LazyLoad && this.settings.settings.lazy_load) {
                window.MotionFlow.LazyLoad.init();
            }
            
            // Initialize Animation component
            if (window.MotionFlow.Animation && this.settings.settings.animations) {
                window.MotionFlow.Animation.init();
            }
            
            // Initialize DragDrop component
            if (window.MotionFlow.DragDrop && this.settings.settings.drag_to_cart) {
                window.MotionFlow.DragDrop.init({
                    enabled: this.settings.settings.drag_to_cart,
                    selectors: this.settings.selectors,
                    ajaxUrl: this.settings.ajaxUrl,
                    nonce: this.settings.nonce.cart,
                    debug: this.settings.settings.debug
                });
            }
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            var settings = this.settings;
            
            // Add cart event handlers
            $(document).on('click', settings.selectors.cartContainer + ' .motionflow-cart-close', function(e) {
                e.preventDefault();
                self.closeCart();
            });
            
            // Add modal event handlers
            $(document).on('click', '.motionflow-modal-close, .motionflow-modal-overlay', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            // Keep modal open when clicking inside
            $(document).on('click', '.motionflow-modal-container', function(e) {
                e.stopPropagation();
            });
            
            // Initialize grid
            $(document).ready(function() {
                self.initGrid();
            });
        },

        /**
         * Initialize grid
         * 
         * @param {Object} options Grid options
         */
        initGrid: function(options) {
            var self = this;
            var settings = this.settings;
            
            // Merge options
            options = $.extend({
                container: settings.selectors.gridContainer,
                enableDragToCart: settings.settings.drag_to_cart,
                enableModal: settings.settings.modal,
                isLoopIntegration: false
            }, options);
            
            // Skip if no container
            if (!$(options.container).length) {
                return;
            }
            
            // Set up container
            $(options.container).each(function() {
                var $container = $(this);
                
                // Add container class
                $container.addClass('motionflow-container');
                
                // Initialize drag and drop
                if (options.enableDragToCart && window.MotionFlow.DragDrop) {
                    window.MotionFlow.DragDrop.refresh();
                }
                
                // Initialize lazy loading
                if (settings.settings.lazy_load && window.MotionFlow.LazyLoad) {
                    window.MotionFlow.LazyLoad.refresh();
                }
                
                // Initialize animations
                if (settings.settings.animations && window.MotionFlow.Animation) {
                    window.MotionFlow.Animation.refresh();
                }
            });
            
            this.log('Grid initialized', options);
        },

        /**
         * Open cart
         */
        openCart: function() {
            var $cart = $(this.settings.selectors.cartContainer);
            
            $cart.addClass('motionflow-active');
            $('body').addClass('motionflow-cart-open');
            
            $(document).trigger('motionflow:cart:opened', [$cart]);
        },

        /**
         * Close cart
         */
        closeCart: function() {
            var $cart = $(this.settings.selectors.cartContainer);
            
            $cart.removeClass('motionflow-active');
            $('body').removeClass('motionflow-cart-open');
            
            $(document).trigger('motionflow:cart:closed', [$cart]);
        },

        /**
         * Open modal
         * 
         * @param {string} html Modal HTML
         */
        openModal: function(html) {
            // Create modal if it doesn't exist
            if (!$('.motionflow-modal').length) {
                $('body').append('<div class="motionflow-modal-overlay"></div><div class="motionflow-modal"></div>');
            }
            
            // Get modal elements
            var $modal = $('.motionflow-modal');
            var $overlay = $('.motionflow-modal-overlay');
            
            // Set modal content
            $modal.html(html);
            
            // Show modal and overlay
            $overlay.fadeIn(200);
            $modal.fadeIn(200);
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.motionflow-modal-overlay').fadeOut(200);
            $('.motionflow-modal').fadeOut(200);
        },

        /**
         * Get current device
         * 
         * @return {string} The device type (desktop, tablet, mobile)
         */
        getCurrentDevice: function() {
            var width = window.innerWidth;
            
            if (width < 576) {
                return 'mobile';
            } else if (width < 992) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        },

        /**
         * Log message
         * 
         * @param {string} message The log message
         * @param {Object} data    The log data
         * @param {string} type    The log type (log, warn, error)
         */
        log: function(message, data, type) {
            // Skip if debugging is disabled
            if (!this.settings.settings.debug) {
                return;
            }
            
            // Set default type
            type = type || 'log';
            
            // Log message
            switch (type) {
                case 'warn':
                    console.warn('MotionFlow: ' + message, data);
                    break;
                case 'error':
                    console.error('MotionFlow: ' + message, data);
                    break;
                default:
                    console.log('MotionFlow: ' + message, data);
                    break;
            }
        }
    };

    /**
     * LazyLoad component
     */
    MotionFlow.LazyLoad = {
        /**
         * Initialize the lazy load component
         */
        init: function() {
            this.initLazyLoad();
            this.bindEvents();
            MotionFlow.Core.log('LazyLoad component initialized');
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            
            // Listen for scroll events
            $(window).on('scroll', function() {
                self.checkVisibleImages();
            });
            
            // Listen for grid updated event
            $(document).on('motionflow:grid:updated', function() {
                self.refresh();
            });
        },

        /**
         * Initialize lazy loading
         */
        initLazyLoad: function() {
            // Check if IntersectionObserver is supported
            if ('IntersectionObserver' in window) {
                this.initWithIntersectionObserver();
            } else {
                this.initFallback();
            }
        },

        /**
         * Initialize with IntersectionObserver
         */
        initWithIntersectionObserver: function() {
            var self = this;
            
            // Create observer
            this.observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        self.loadImage(entry.target);
                        self.observer.unobserve(entry.target);
                    }
                });
            }, {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            });
            
            // Observe all lazy images
            $('.motionflow-lazy').each(function() {
                self.observer.observe(this);
            });
        },

        /**
         * Initialize fallback (for browsers without IntersectionObserver)
         */
        initFallback: function() {
            // Load all visible images immediately
            this.checkVisibleImages();
        },

        /**
         * Check for visible images and load them
         */
        checkVisibleImages: function() {
            var self = this;
            
            // Skip if using IntersectionObserver
            if (this.observer) {
                return;
            }
            
            // Find all lazy images
            $('.motionflow-lazy').each(function() {
                var $image = $(this);
                
                if (self.isElementInViewport(this)) {
                    self.loadImage(this);
                }
            });
        },

        /**
         * Load an image
         * 
         * @param {Element} img The image element
         */
        loadImage: function(img) {
            var $img = $(img);
            
            // Skip if already loaded
            if ($img.hasClass('motionflow-lazy-loaded') || !$img.hasClass('motionflow-lazy')) {
                return;
            }
            
            // Get source
            var src = $img.data('src');
            
            if (!src) {
                return;
            }
            
            // Load image
            $img.attr('src', src)
                .removeClass('motionflow-lazy')
                .addClass('motionflow-lazy-loaded');
        },

        /**
         * Check if element is in viewport
         * 
         * @param {Element} el The element to check
         * @return {boolean}
         */
        isElementInViewport: function(el) {
            var rect = el.getBoundingClientRect();
            
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        /**
         * Refresh lazy loading
         */
        refresh: function() {
            var self = this;
            
            // If using IntersectionObserver
            if (this.observer) {
                // Find all lazy images that aren't being observed
                $('.motionflow-lazy').each(function() {
                    self.observer.observe(this);
                });
            } else {
                // Check visible images
                this.checkVisibleImages();
            }
        }
    };

    /**
     * Animation component
     */
    MotionFlow.Animation = {
        /**
         * Initialize the animation component
         */
        init: function() {
            this.initAnimations();
            MotionFlow.Core.log('Animation component initialized');
        },

        /**
         * Initialize animations
         */
        initAnimations: function() {
            // Add animation classes based on settings
            this.addEntranceAnimations();
        },

        /**
         * Add entrance animations
         */
        addEntranceAnimations: function() {
            var self = this;
            var core = MotionFlow.Core;
            
            // Check if animations are enabled
            if (!core.settings.settings.animations) {
                return;
            }
            
            // Check if IntersectionObserver is supported
            if (!('IntersectionObserver' in window)) {
                return;
            }
            
            // Create observer
            this.observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        self.animateElement(entry.target);
                        self.observer.unobserve(entry.target);
                    }
                });
            }, {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            });
            
            // Observe all products
            $(core.settings.selectors.product).each(function() {
                self.observer.observe(this);
            });
        },

        /**
         * Animate element
         * 
         * @param {Element} el The element to animate
         */
        animateElement: function(el) {
            var $el = $(el);
            
            // Skip if already animated
            if ($el.hasClass('motionflow-animated')) {
                return;
            }
            
            // Add animation class
            $el.addClass('motionflow-animated motionflow-fade-in');
            
            // Remove animation class after animation completes
            setTimeout(function() {
                $el.removeClass('motionflow-fade-in');
            }, 1000);
        },

        /**
         * Refresh animations
         */
        refresh: function() {
            var self = this;
            var core = MotionFlow.Core;
            
            // If using IntersectionObserver
            if (this.observer) {
                // Find all products that aren't being observed
                $(core.settings.selectors.product).each(function() {
                    if (!$(this).hasClass('motionflow-animated')) {
                        self.observer.observe(this);
                    }
                });
            }
        }
    };

    /**
     * Initialize MotionFlow on document ready
     */
    $(document).ready(function() {
        // Get options from localized script
        var options = window.motionflow_params || {};
        
        // Initialize MotionFlow
        MotionFlow.Core.init(options);
    });

})(jQuery, window, document);