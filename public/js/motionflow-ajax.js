/**
 * MotionFlow - AJAX Handler
 * Digital Commerce, Redefined
 * 
 * This file handles all AJAX operations for the MotionFlow plugin.
 */

// Create MotionFlow namespace
window.MotionFlow = window.MotionFlow || {};

(function($, window, document) {
    'use strict';

    /**
     * AJAX Handler component
     */
    MotionFlow.AJAX = {
        /**
         * Initialize the AJAX Handler
         * 
         * @param {Object} options Configuration options
         */
        init: function(options) {
            this.settings = $.extend({
                ajaxUrl: '',
                nonce: {
                    filter: '',
                    cart: '',
                    modal: '',
                    analytics: ''
                },
                selectors: {
                    filterForm: '.motionflow-filters-form',
                    filterControl: '.motionflow-filter-control',
                    gridContainer: '.motionflow-grid',
                    pagination: '.motionflow-pagination',
                    productCount: '.motionflow-product-count',
                    addToCart: '.motionflow-add-to-cart',
                    modal: '.motionflow-modal',
                    modalTrigger: '.motionflow-modal-trigger',
                    cartContainer: '.motionflow-cart-sidebar',
                    cartButton: '.motionflow-cart-button',
                    cartItem: '.motionflow-cart-item',
                    cartQuantity: '.motionflow-cart-item-quantity-value',
                    cartItemRemove: '.motionflow-cart-item-remove',
                    cartItemQuantityPlus: '.motionflow-cart-item-quantity-plus',
                    cartItemQuantityMinus: '.motionflow-cart-item-quantity-minus'
                },
                debug: false
            }, options);

            this.bindEvents();
            this.log('AJAX Handler initialized', this.settings);
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Store reference to this
            var self = this;
            var settings = this.settings;

            // Filter events
            $(document).on('change', settings.selectors.filterControl, function() {
                self.handleFilterChange($(this));
            });

            // Pagination events
            $(document).on('click', settings.selectors.pagination + ' a', function(e) {
                e.preventDefault();
                self.handlePagination($(this));
            });

            // Add to cart events
            $(document).on('click', settings.selectors.addToCart, function(e) {
                e.preventDefault();
                self.handleAddToCart($(this));
            });

            // Modal events
            $(document).on('click', settings.selectors.modalTrigger, function(e) {
                e.preventDefault();
                self.handleModalTrigger($(this));
            });

            // Cart quantity events
            $(document).on('click', settings.selectors.cartItemQuantityPlus, function() {
                self.handleCartQuantityChange($(this), 1);
            });

            $(document).on('click', settings.selectors.cartItemQuantityMinus, function() {
                self.handleCartQuantityChange($(this), -1);
            });

            // Cart item remove
            $(document).on('click', settings.selectors.cartItemRemove, function() {
                self.handleCartItemRemove($(this));
            });

            // Form submit prevention
            $(document).on('submit', settings.selectors.filterForm, function(e) {
                e.preventDefault();
                self.handleFilterFormSubmit($(this));
            });
        },

        /**
         * Handle filter change
         * 
         * @param {jQuery} $filter The filter element
         */
        handleFilterChange: function($filter) {
            // Get form
            var $form = $filter.closest(this.settings.selectors.filterForm);
            
            // Handle filter change (trigger form submission)
            this.handleFilterFormSubmit($form);
        },

        /**
         * Handle filter form submit
         * 
         * @param {jQuery} $form The form element
         */
        handleFilterFormSubmit: function($form) {
            var self = this;
            var settings = this.settings;
            
            // Show loading indicator
            self.showLoader();
            
            // Collect filter values
            var filters = {};
            $form.find(settings.selectors.filterControl).each(function() {
                var $control = $(this);
                var name = $control.attr('name');
                var value = $control.val();
                
                if (value) {
                    filters[name] = value;
                }
            });
            
            // Get current page, layout, and per page settings
            var page = 1; // Reset to first page on filter change
            var layout = $form.data('layout') || 'grid';
            var perPage = parseInt($form.data('per-page'), 10) || 20;
            
            // Perform AJAX request
            self.filterProducts(filters, page, perPage, layout);
        },

        /**
         * Handle pagination
         * 
         * @param {jQuery} $link The pagination link
         */
        handlePagination: function($link) {
            var self = this;
            var settings = this.settings;
            
            // Show loading indicator
            self.showLoader();
            
            // Get page number
            var page = parseInt($link.data('page'), 10) || 1;
            
            // Get filter form
            var $form = $(settings.selectors.filterForm);
            
            // Collect filter values
            var filters = {};
            $form.find(settings.selectors.filterControl).each(function() {
                var $control = $(this);
                var name = $control.attr('name');
                var value = $control.val();
                
                if (value) {
                    filters[name] = value;
                }
            });
            
            // Get layout and per page settings
            var layout = $form.data('layout') || 'grid';
            var perPage = parseInt($form.data('per-page'), 10) || 20;
            
            // Perform AJAX request
            self.filterProducts(filters, page, perPage, layout);
        },

        /**
         * Filter products via AJAX
         * 
         * @param {Object} filters  The filter values
         * @param {number} page     The page number
         * @param {number} perPage  The number of items per page
         * @param {string} layout   The grid layout
         */
        filterProducts: function(filters, page, perPage, layout) {
            var self = this;
            var settings = this.settings;
            
            // Prepare data
            var data = {
                action: 'motionflow_filter_products',
                nonce: settings.nonce.filter,
                filters: filters,
                page: page,
                per_page: perPage,
                layout: layout
            };
            
            // Log request
            self.log('Filter request', data);
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Hide loading indicator
                    self.hideLoader();
                    
                    if (response.success) {
                        // Log success
                        self.log('Filter success', response);
                        
                        // Update grid HTML
                        $(settings.selectors.gridContainer).html(response.html);
                        
                        // Update product count
                        if ($(settings.selectors.productCount).length && response.total !== undefined) {
                            self.updateProductCount(response.total, response.current_page, perPage);
                        }
                        
                        // Update URL (history)
                        self.updateFilterURL(filters, page);
                        
                        // Trigger custom event
                        $(document).trigger('motionflow:filters:applied', {
                            filters: filters,
                            page: page,
                            perPage: perPage,
                            layout: layout,
                            response: response
                        });
                        
                        // Refresh components
                        if (window.MotionFlow.LazyLoad && typeof window.MotionFlow.LazyLoad.refresh === 'function') {
                            window.MotionFlow.LazyLoad.refresh();
                        }
                        
                        if (window.MotionFlow.Animation && typeof window.MotionFlow.Animation.refresh === 'function') {
                            window.MotionFlow.Animation.refresh();
                        }
                        
                        if (window.MotionFlow.DragDrop && typeof window.MotionFlow.DragDrop.refresh === 'function') {
                            window.MotionFlow.DragDrop.refresh();
                        }
                        
                        // Track event
                        self.trackEvent('filter_products', {
                            filters: filters,
                            page: page,
                            results: response.total
                        });
                    } else {
                        // Log error
                        self.log('Filter error', response, 'error');
                        
                        // Show error message
                        self.showError(response.message || 'Error filtering products');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide loading indicator
                    self.hideLoader();
                    
                    // Log error
                    self.log('Filter AJAX error', { status: status, error: error }, 'error');
                    
                    // Show error message
                    self.showError('Error communicating with server');
                }
            });
        },

        /**
         * Handle add to cart
         * 
         * @param {jQuery} $button The add to cart button
         */
        handleAddToCart: function($button) {
            var self = this;
            var settings = this.settings;
            
            // Get product data
            var productId = $button.data('product-id');
            var quantity = parseInt($button.data('quantity'), 10) || 1;
            
            // Skip if no product ID
            if (!productId) {
                self.log('Add to cart error: No product ID', {}, 'error');
                return;
            }
            
            // Get variation data if exists
            var variationId = $button.data('variation-id') || 0;
            var variation = {};
            
            // If this is in a form with variation inputs
            var $form = $button.closest('form.variations_form');
            if ($form.length) {
                // Get variation ID and data from form
                variationId = parseInt($form.find('input[name="variation_id"]').val(), 10) || 0;
                
                // Get variation attributes
                $form.find('select[name^="attribute_"]').each(function() {
                    var $select = $(this);
                    var name = $select.attr('name');
                    var value = $select.val();
                    
                    if (name && value) {
                        variation[name] = value;
                    }
                });
            }
            
            // Show loading indicator on button
            $button.addClass('motionflow-loading');
            
            // Prepare data
            var data = {
                action: 'motionflow_add_to_cart',
                nonce: settings.nonce.cart,
                product_id: productId,
                variation_id: variationId,
                variation: variation,
                quantity: quantity
            };
            
            // Log request
            self.log('Add to cart request', data);
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Remove loading indicator
                    $button.removeClass('motionflow-loading');
                    
                    if (response.success) {
                        // Log success
                        self.log('Add to cart success', response);
                        
                        // Update cart fragments
                        self.updateCartFragments(response.fragments);
                        
                        // Show notification
                        self.showNotification('Product added to cart');
                        
                        // Trigger custom event
                        $(document).trigger('motionflow:cart:added', [productId, quantity, response]);
                        
                        // Track event
                        self.trackEvent('add_to_cart', {
                            product_id: productId,
                            quantity: quantity,
                            method: 'click'
                        });
                    } else {
                        // Log error
                        self.log('Add to cart error', response, 'error');
                        
                        // Show error message
                        self.showError(response.message || 'Error adding product to cart');
                    }
                },
                error: function(xhr, status, error) {
                    // Remove loading indicator
                    $button.removeClass('motionflow-loading');
                    
                    // Log error
                    self.log('Add to cart AJAX error', { status: status, error: error }, 'error');
                    
                    // Show error message
                    self.showError('Error communicating with server');
                }
            });
        },

        /**
         * Handle modal trigger
         * 
         * @param {jQuery} $trigger The modal trigger
         */
        handleModalTrigger: function($trigger) {
            var self = this;
            var settings = this.settings;
            
            // Get product ID
            var productId = $trigger.data('product-id');
            
            // Skip if no product ID
            if (!productId) {
                self.log('Modal error: No product ID', {}, 'error');
                return;
            }
            
            // Show loading indicator
            self.showLoader();
            
            // Prepare data
            var data = {
                action: 'motionflow_get_modal_content',
                nonce: settings.nonce.modal,
                product_id: productId
            };
            
            // Log request
            self.log('Modal request', data);
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Hide loading indicator
                    self.hideLoader();
                    
                    if (response.success) {
                        // Log success
                        self.log('Modal success', response);
                        
                        // Show modal
                        self.showModal(response.html);
                        
                        // Trigger custom event
                        $(document).trigger('motionflow:modal:opened', [productId, $(settings.selectors.modal)]);
                        
                        // Track event
                        self.trackEvent('modal_view', {
                            product_id: productId
                        });
                    } else {
                        // Log error
                        self.log('Modal error', response, 'error');
                        
                        // Show error message
                        self.showError(response.message || 'Error loading product information');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide loading indicator
                    self.hideLoader();
                    
                    // Log error
                    self.log('Modal AJAX error', { status: status, error: error }, 'error');
                    
                    // Show error message
                    self.showError('Error communicating with server');
                }
            });
        },

        /**
         * Handle cart quantity change
         * 
         * @param {jQuery} $button The quantity button
         * @param {number} change  The quantity change amount
         */
        handleCartQuantityChange: function($button, change) {
            var self = this;
            var settings = this.settings;
            
            // Get cart item
            var $item = $button.closest(settings.selectors.cartItem);
            var cartKey = $item.data('cart-key');
            
            // Skip if no cart key
            if (!cartKey) {
                self.log('Cart quantity error: No cart key', {}, 'error');
                return;
            }
            
            // Get current quantity
            var $quantity = $item.find(settings.selectors.cartQuantity);
            var currentQuantity = parseInt($quantity.text(), 10) || 1;
            
            // Calculate new quantity
            var newQuantity = Math.max(1, currentQuantity + change);
            
            // Skip if no change
            if (newQuantity === currentQuantity) {
                return;
            }
            
            // Show loading indicator on item
            $item.addClass('motionflow-loading');
            
            // Prepare data
            var data = {
                action: 'motionflow_update_cart_item',
                nonce: settings.nonce.cart,
                cart_item_key: cartKey,
                quantity: newQuantity
            };
            
            // Log request
            self.log('Update cart item request', data);
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Remove loading indicator
                    $item.removeClass('motionflow-loading');
                    
                    if (response.success) {
                        // Log success
                        self.log('Update cart item success', response);
                        
                        // Update cart fragments
                        self.updateCartFragments(response.fragments);
                        
                        // Trigger custom event
                        $(document).trigger('motionflow:cart:updated', [cartKey, newQuantity, response]);
                        
                        // Track event
                        self.trackEvent('update_cart_item', {
                            cart_item_key: cartKey,
                            quantity: newQuantity,
                            change: change
                        });
                    } else {
                        // Log error
                        self.log('Update cart item error', response, 'error');
                        
                        // Show error message
                        self.showError(response.message || 'Error updating cart item');
                    }
                },
                error: function(xhr, status, error) {
                    // Remove loading indicator
                    $item.removeClass('motionflow-loading');
                    
                    // Log error
                    self.log('Update cart item AJAX error', { status: status, error: error }, 'error');
                    
                    // Show error message
                    self.showError('Error communicating with server');
                }
            });
        },

        /**
         * Handle cart item remove
         * 
         * @param {jQuery} $button The remove button
         */
        handleCartItemRemove: function($button) {
            var self = this;
            var settings = this.settings;
            
            // Get cart item
            var $item = $button.closest(settings.selectors.cartItem);
            var cartKey = $item.data('cart-key');
            
            // Skip if no cart key
            if (!cartKey) {
                self.log('Cart remove error: No cart key', {}, 'error');
                return;
            }
            
            // Show loading indicator on item
            $item.addClass('motionflow-loading');
            
            // Prepare data
            var data = {
                action: 'motionflow_remove_cart_item',
                nonce: settings.nonce.cart,
                cart_item_key: cartKey
            };
            
            // Log request
            self.log('Remove cart item request', data);
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Remove loading indicator
                    $item.removeClass('motionflow-loading');
                    
                    if (response.success) {
                        // Log success
                        self.log('Remove cart item success', response);
                        
                        // Update cart fragments
                        self.updateCartFragments(response.fragments);
                        
                        // Trigger custom event
                        $(document).trigger('motionflow:cart:removed', [cartKey, response]);
                        
                        // Track event
                        self.trackEvent('remove_cart_item', {
                            cart_item_key: cartKey
                        });
                    } else {
                        // Log error
                        self.log('Remove cart item error', response, 'error');
                        
                        // Show error message
                        self.showError(response.message || 'Error removing cart item');
                    }
                },
                error: function(xhr, status, error) {
                    // Remove loading indicator
                    $item.removeClass('motionflow-loading');
                    
                    // Log error
                    self.log('Remove cart item AJAX error', { status: status, error: error }, 'error');
                    
                    // Show error message
                    self.showError('Error communicating with server');
                }
            });
        },

        /**
         * Update cart fragments
         * 
         * @param {Object} fragments The cart fragments
         */
        updateCartFragments: function(fragments) {
            // Skip if no fragments
            if (!fragments) {
                return;
            }
            
            // Loop through fragments
            $.each(fragments, function(selector, content) {
                // Replace fragment content
                $(selector).html(content);
            });
        },

        /**
         * Show modal
         * 
         * @param {string} html The modal HTML
         */
        showModal: function(html) {
            var settings = this.settings;
            
            // Create modal if it doesn't exist
            if (!$(settings.selectors.modal).length) {
                $('body').append('<div class="motionflow-modal-overlay"></div><div class="motionflow-modal"></div>');
            }
            
            // Get modal elements
            var $modal = $(settings.selectors.modal);
            var $overlay = $('.motionflow-modal-overlay');
            
            // Set modal content
            $modal.html(html);
            
            // Show modal and overlay
            $overlay.fadeIn(200);
            $modal.fadeIn(200);
            
            // Add close event
            $overlay.on('click', function() {
                $overlay.fadeOut(200);
                $modal.fadeOut(200);
            });
            
            $modal.on('click', '.motionflow-modal-close', function(e) {
                e.preventDefault();
                $overlay.fadeOut(200);
                $modal.fadeOut(200);
            });
        },

        /**
         * Update product count
         * 
         * @param {number} total   The total number of products
         * @param {number} page    The current page number
         * @param {number} perPage The number of items per page
         */
        updateProductCount: function(total, page, perPage) {
            var settings = this.settings;
            
            // Calculate start and end
            var start = (page - 1) * perPage + 1;
            var end = Math.min(page * perPage, total);
            
            // Create count HTML
            var countHtml = '';
            
            if (total === 0) {
                countHtml = 'No products found';
            } else {
                countHtml = 'Showing ' + start + '–' + end + ' of ' + total + ' results';
            }
            
            // Update count element
            $(settings.selectors.productCount).html(countHtml);
        },

        /**
         * Update filter URL
         * 
         * @param {Object} filters The filter values
         * @param {number} page    The page number
         */
        updateFilterURL: function(filters, page) {
            // Skip if history API not available
            if (!window.history || !window.history.pushState) {
                return;
            }
            
            // Get current URL and parameters
            var url = new URL(window.location.href);
            var params = url.searchParams;
            
            // Clear existing filter parameters
            url.searchParams.forEach(function(value, key) {
                // Skip non-filter parameters
                if (key !== 'orderby' && key !== 's' && key !== 'post_type') {
                    params.delete(key);
                }
            });
            
            // Add filter parameters
            $.each(filters, function(key, value) {
                params.set(key, value);
            });
            
            // Add page parameter if not first page
            if (page > 1) {
                params.set('paged', page);
            }
            
            // Update URL
            window.history.pushState({}, '', url.toString());
        },

        /**
         * Show loader
         */
        showLoader: function() {
            $('.motionflow-loader').fadeIn(200);
        },

        /**
         * Hide loader
         */
        hideLoader: function() {
            $('.motionflow-loader').fadeOut(200);
        },

        /**
         * Show notification
         * 
         * @param {string} message The notification message
         */
        showNotification: function(message) {
            // Create notification element if it doesn't exist
            if (!$('.motionflow-notification').length) {
                $('body').append('<div class="motionflow-notification"></div>');
            }
            
            // Get notification element
            var $notification = $('.motionflow-notification');
            
            // Set message
            $notification.html(message);
            
            // Show notification
            $notification.fadeIn(200).delay(2000).fadeOut(200);
        },

        /**
         * Show error
         * 
         * @param {string} message The error message
         */
        showError: function(message) {
            // Create error element if it doesn't exist
            if (!$('.motionflow-error').length) {
                $('body').append('<div class="motionflow-error"></div>');
            }
            
            // Get error element
            var $error = $('.motionflow-error');
            
            // Set message
            $error.html(message);
            
            // Show error
            $error.fadeIn(200).delay(3000).fadeOut(200);
        },

        /**
         * Track event
         * 
         * @param {string} eventType The event type
         * @param {Object} eventData The event data
         */
        trackEvent: function(eventType, eventData) {
            var self = this;
            var settings = this.settings;
            
            // Skip if analytics is disabled or no nonce
            if (!settings.nonce.analytics) {
                return;
            }
            
            // Prepare data
            var data = {
                action: 'motionflow_track_event',
                nonce: settings.nonce.analytics,
                event_type: eventType,
                event_data: eventData
            };
            
            // Log request (debug only)
            if (settings.debug) {
                self.log('Track event', data);
            }
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data
            });
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
            if (!this.settings.debug) {
                return;
            }
            
            // Set default type
            type = type || 'log';
            
            // Log message
            switch (type) {
                case 'warn':
                    console.warn('MotionFlow AJAX: ' + message, data);
                    break;
                case 'error':
                    console.error('MotionFlow AJAX: ' + message, data);
                    break;
                default:
                    console.log('MotionFlow AJAX: ' + message, data);
                    break;
            }
        }
    };

})(jQuery, window, document);