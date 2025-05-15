/**
 * MotionFlow - Main JavaScript File
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
        // ... [Previous content] ...
    };

    /**
     * Modal component
     */
    MotionFlow.Modal = {
        // ... [Previous content] ...
    };

    /**
     * Cart component
     */
    MotionFlow.Cart = {
        // ... [Previous content] ...
    };

    /**
     * Filters component
     */
    MotionFlow.Filters = {
        // ... [Previous content] ...
    };

    /**
     * DragDrop component
     */
    MotionFlow.DragDrop = {
        // ... [Previous content] ...
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
                
                if (self.isElementInViewport($image[0])) {
                    self.loadImage($image[0]);
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
            
            // Log
            MotionFlow.Core.log('Lazy image loaded: ' + src);
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
            if (!core.settings.animations) {
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
     * Analytics component
     */
    MotionFlow.Analytics = {
        /**
         * Initialize the analytics component
         */
        init: function() {
            this.bindEvents();
            MotionFlow.Core.log('Analytics component initialized');
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            var core = MotionFlow.Core;
            
            // Track product views
            $(document).on('click', core.settings.selectors.product + ' a', function() {
                var $product = $(this).closest(core.settings.selectors.product);
                var productId = $product.data('product-id');
                
                if (productId) {
                    self.trackEvent('product_click', {
                        product_id: productId
                    });
                }
            });
            
            // Track modal views
            $(document).on('motionflow:modal:opened', function(e, productId, $modal) {
                self.trackEvent('modal_view', {
                    product_id: productId
                });
            });
            
            // Track add to cart
            $(document).on('motionflow:cart:added', function(e, productId, quantity, data) {
                self.trackEvent('add_to_cart', {
                    product_id: productId,
                    quantity: quantity,
                    method: 'click'
                });
            });
            
            // Track drag to cart
            $(document).on('motionflow:dragdrop:dropped', function(e, productId, $dropZone, dropZoneType) {
                self.trackEvent('add_to_cart', {
                    product_id: productId,
                    quantity: 1,
                    method: 'drag'
                });
            });
            
            // Track filter usage
            $(document).on('motionflow:filters:applied', function(e, data) {
                self.trackEvent('filter_products', {
                    filters: data.active_filters
                });
            });
        },

        /**
         * Track an event
         * 
         * @param {string} eventType The event type
         * @param {Object} eventData The event data
         */
        trackEvent: function(eventType, eventData) {
            var core = MotionFlow.Core;
            
            // Skip if analytics is disabled
            if (!core.settings.enable_analytics) {
                return;
            }
            
            // Add timestamp
            eventData.timestamp = new Date().toISOString();
            
            // Add page info
            eventData.page_url = window.location.href;
            eventData.page_title = document.title;
            
            // Add device info
            eventData.device_type = core.getCurrentDevice();
            
            // AJAX request
            $.ajax({
                url: core.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'motionflow_track_event',
                    event_type: eventType,
                    event_data: eventData,
                    nonce: core.settings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        core.log('Event tracked: ' + eventType);
                    } else {
                        core.log('Failed to track event: ' + eventType, 'warn');
                    }
                }
            });
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

    /**
     * Global initialization function
     * 
     * @param {string} selector The container selector
     * @param {Object} options Configuration options
     */
    window.initMotionFlow = function(selector, options) {
        // Add container selector to options
        options = options || {};
        options.selectors = options.selectors || {};
        options.selectors.container = selector;
        
        // Initialize MotionFlow
        MotionFlow.Core.init(options);
    };

})(jQuery, window, document);