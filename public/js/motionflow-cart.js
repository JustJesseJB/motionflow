/**
 * MotionFlow - Cart
 * Digital Commerce, Redefined
 * 
 * This file handles the cart functionality for the MotionFlow plugin.
 */

// Create MotionFlow namespace
window.MotionFlow = window.MotionFlow || {};

(function($, window, document) {
    'use strict';

    /**
     * Cart component
     */
    MotionFlow.Cart = {
        /**
         * Initialize the cart component
         * 
         * @param {Object} options Configuration options
         */
        init: function(options) {
            this.settings = $.extend({
                enabled: true,
                selectors: {
                    cart: '.motionflow-cart-sidebar',
                    cartButton: '.motionflow-cart-button',
                    cartItem: '.motionflow-cart-item',
                    cartItems: '.motionflow-cart-items',
                    cartTotal: '.motionflow-cart-total-value',
                    cartCount: '.motionflow-cart-button-count',
                    cartClose: '.motionflow-cart-close',
                    cartEmpty: '.motionflow-cart-empty-message',
                    cartItemRemove: '.motionflow-cart-item-remove',
                    cartItemQuantity: '.motionflow-cart-item-quantity-value',
                    cartItemQuantityPlus: '.motionflow-cart-item-quantity-plus',
                    cartItemQuantityMinus: '.motionflow-cart-item-quantity-minus',
                    addToCart: '.motionflow-add-to-cart',
                    modalAddToCart: '.motionflow-modal-add-to-cart'
                },
                ajaxUrl: '',
                nonce: '',
                debug: false
            }, options);

            // Initialize cart state
            this.state = {
                cartOpen: false,
                items: [],
                count: 0,
                total: 0
            };

            // Initialize events
            this.bindEvents();
            
            // Initialize cart position
            this.initCartPosition();
            
            this.log('Cart component initialized');
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            var settings = this.settings;
            
            // Cart toggle
            $(document).on('click', settings.selectors.cartButton, function(e) {
                e.preventDefault();
                self.toggleCart();
            });
            
            // Cart close
            $(document).on('click', settings.selectors.cartClose, function(e) {
                e.preventDefault();
                self.closeCart();
            });
            
            // Add to cart
            $(document).on('click', settings.selectors.addToCart + ', ' + settings.selectors.modalAddToCart, function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var productId = $button.data('product-id');
                var quantity = parseInt($button.data('quantity'), 10) || 1;
                
                if (productId) {
                    self.addToCart(productId, quantity, $button);
                }
            });
            
            // Cart item quantity
            $(document).on('click', settings.selectors.cartItemQuantityPlus, function() {
                var $item = $(this).closest(settings.selectors.cartItem);
                var cartKey = $item.data('cart-key');
                var $quantity = $item.find(settings.selectors.cartItemQuantity);
                var quantity = parseInt($quantity.text(), 10) || 1;
                
                self.updateCartItem(cartKey, quantity + 1);
            });
            
            $(document).on('click', settings.selectors.cartItemQuantityMinus, function() {
                var $item = $(this).closest(settings.selectors.cartItem);
                var cartKey = $item.data('cart-key');
                var $quantity = $item.find(settings.selectors.cartItemQuantity);
                var quantity = parseInt($quantity.text(), 10) || 1;
                
                if (quantity > 1) {
                    self.updateCartItem(cartKey, quantity - 1);
                }
            });
            
            // Cart item remove
            $(document).on('click', settings.selectors.cartItemRemove, function() {
                var $item = $(this).closest(settings.selectors.cartItem);
                var cartKey = $item.data('cart-key');
                
                self.removeCartItem(cartKey);
            });
            
            // Cart open/close when cart button is dragged
            $(document).on('motionflow:dragdrop:start', function() {
                self.openCart();
            });
            
            // Cart open when item is added
            $(document).on('motionflow:cart:added', function() {
                self.openCart();
            });
            
            // ESC key to close cart
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && self.state.cartOpen) {
                    self.closeCart();
                }
            });
            
            // Close cart when clicking outside
            $(document).on('click', function(e) {
                var $cart = $(settings.selectors.cart);
                var $cartButton = $(settings.selectors.cartButton);
                
                if (self.state.cartOpen && 
                    !$cart.is(e.target) && 
                    $cart.has(e.target).length === 0 && 
                    !$cartButton.is(e.target) && 
                    $cartButton.has(e.target).length === 0) {
                    self.closeCart();
                }
            });
        },

        /**
         * Initialize cart position
         */
        initCartPosition: function() {
            var settings = this.settings;
            
            // Try to load position from localStorage
            if (window.localStorage) {
                var savedPosition = localStorage.getItem('motionflow_cart_button_position');
                
                if (savedPosition) {
                    try {
                        var position = JSON.parse(savedPosition);
                        
                        // Apply position
                        $(settings.selectors.cartButton).css(position);
                    } catch (e) {
                        this.log('Error loading cart button position', e, 'error');
                    }
                }
            }
        },

        /**
         * Toggle cart
         */
        toggleCart: function() {
            if (this.state.cartOpen) {
                this.closeCart();
            } else {
                this.openCart();
            }
        },

        /**
         * Open cart
         */
        openCart: function() {
            var settings = this.settings;
            var $cart = $(settings.selectors.cart);
            
            $cart.addClass('motionflow-active');
            $('body').addClass('motionflow-cart-open');
            
            this.state.cartOpen = true;
            
            $(document).trigger('motionflow:cart:opened', [$cart]);
        },

        /**
         * Close cart
         */
        closeCart: function() {
            var settings = this.settings;
            var $cart = $(settings.selectors.cart);
            
            $cart.removeClass('motionflow-active');
            $('body').removeClass('motionflow-cart-open');
            
            this.state.cartOpen = false;
            
            $(document).trigger('motionflow:cart:closed', [$cart]);
        },

        /**
         * Add to cart
         * 
         * @param {number} productId The product ID
         * @param {number} quantity  The quantity
         * @param {jQuery} $button   The button element (optional)
         */
        addToCart: function(productId, quantity, $button) {
            var self = this;
            var settings = this.settings;
            
            // Skip if no product ID
            if (!productId) {
                return;
            }
            
            // Show loading if button is provided
            if ($button) {
                $button.addClass('motionflow-loading');
            }
            
            // Prepare data
            var data = {
                action: 'motionflow_add_to_cart',
                nonce: settings.nonce,
                product_id: productId,
                quantity: quantity
            };
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Remove loading
                    if ($button) {
                        $button.removeClass('motionflow-loading');
                    }
                    
                    if (response.success) {
                        // Update cart
                        self.updateCart(response);
                        
                        // Show notification
                        self.showNotification('Product added to cart');
                        
                        // Trigger event
                        $(document).trigger('motionflow:cart:added', [productId, quantity, response]);
                    } else {
                        // Show error
                        self.showError(response.message || 'Error adding product to cart');
                    }
                },
                error: function(xhr, status, error) {
                    // Remove loading
                    if ($button) {
                        $button.removeClass('motionflow-loading');
                    }
                    
                    // Show error
                    self.showError('Error communicating with server');
                    
                    // Log error
                    self.log('Add to cart error', { xhr: xhr, status: status, error: error }, 'error');
                }
            });
        },

        /**
         * Update cart item
         * 
         * @param {string} cartKey  The cart item key
         * @param {number} quantity The quantity
         */
        updateCartItem: function(cartKey, quantity) {
            var self = this;
            var settings = this.settings;
            
            // Skip if no cart key
            if (!cartKey) {
                return;
            }
            
            // Find cart item
            var $item = $(settings.selectors.cart).find(settings.selectors.cartItem + '[data-cart-key="' + cartKey + '"]');
            
            // Skip if not found
            if (!$item.length) {
                return;
            }
            
            // Show loading
            $item.addClass('motionflow-loading');
            
            // Prepare data
            var data = {
                action: 'motionflow_update_cart_item',
                nonce: settings.nonce,
                cart_item_key: cartKey,
                quantity: quantity
            };
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Remove loading
                    $item.removeClass('motionflow-loading');
                    
                    if (response.success) {
                        // Update cart
                        self.updateCart(response);
                        
                        // Trigger event
                        $(document).trigger('motionflow:cart:updated', [cartKey, quantity, response]);
                    } else {
                        // Show error
                        self.showError(response.message || 'Error updating cart item');
                    }
                },
                error: function(xhr, status, error) {
                    // Remove loading
                    $item.removeClass('motionflow-loading');
                    
                    // Show error
                    self.showError('Error communicating with server');
                    
                    // Log error
                    self.log('Update cart item error', { xhr: xhr, status: status, error: error }, 'error');
                }
            });
        },

        /**
         * Remove cart item
         * 
         * @param {string} cartKey The cart item key
         */
        removeCartItem: function(cartKey) {
            var self = this;
            var settings = this.settings;
            
            // Skip if no cart key
            if (!cartKey) {
                return;
            }
            
            // Find cart item
            var $item = $(settings.selectors.cart).find(settings.selectors.cartItem + '[data-cart-key="' + cartKey + '"]');
            
            // Skip if not found
            if (!$item.length) {
                return;
            }
            
            // Show loading
            $item.addClass('motionflow-loading');
            
            // Prepare data
            var data = {
                action: 'motionflow_remove_cart_item',
                nonce: settings.nonce,
                cart_item_key: cartKey
            };
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Remove loading
                    $item.removeClass('motionflow-loading');
                    
                    if (response.success) {
                        // Update cart
                        self.updateCart(response);
                        
                        // Trigger event
                        $(document).trigger('motionflow:cart:removed', [cartKey, response]);
                    } else {
                        // Show error
                        self.showError(response.message || 'Error removing cart item');
                    }
                },
                error: function(xhr, status, error) {
                    // Remove loading
                    $item.removeClass('motionflow-loading');
                    
                    // Show error
                    self.showError('Error communicating with server');
                    
                    // Log error
                    self.log('Remove cart item error', { xhr: xhr, status: status, error: error }, 'error');
                }
            });
        },

        /**
         * Update cart
         * 
         * @param {Object} response The AJAX response
         */
        updateCart: function(response) {
            var settings = this.settings;
            
            // Update fragments
            if (response.fragments) {
                $.each(response.fragments, function(selector, content) {
                    $(selector).html(content);
                });
            }
            
            // Update count
            if (response.cart_count !== undefined) {
                $(settings.selectors.cartCount).text(response.cart_count);
                this.state.count = response.cart_count;
            }
            
            // Update total
            if (response.cart_total !== undefined) {
                $(settings.selectors.cartTotal).html(response.cart_total);
                this.state.total = response.cart_total;
            }
            
            // Check if cart is empty
            var isEmpty = response.cart_count === 0;
            
            if (isEmpty) {
                $(settings.selectors.cartEmpty).show();
                $(settings.selectors.cartItems).hide();
            } else {
                $(settings.selectors.cartEmpty).hide();
                $(settings.selectors.cartItems).show();
            }
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
                    console.warn('MotionFlow Cart: ' + message, data);
                    break;
                case 'error':
                    console.error('MotionFlow Cart: ' + message, data);
                    break;
                default:
                    console.log('MotionFlow Cart: ' + message, data);
                    break;
            }
        }
    };

    /**
     * Initialize Cart on document ready
     */
    $(document).ready(function() {
        // Get options from core
        var options = {};
        
        if (window.MotionFlow && window.MotionFlow.Core) {
            options = {
                selectors: window.MotionFlow.Core.settings.selectors,
                ajaxUrl: window.MotionFlow.Core.settings.ajaxUrl,
                nonce: window.MotionFlow.Core.settings.nonce.cart,
                debug: window.MotionFlow.Core.settings.settings.debug
            };
        }
        
        // Initialize Cart
        MotionFlow.Cart.init(options);
    });

})(jQuery, window, document);