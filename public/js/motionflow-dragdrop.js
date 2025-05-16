/**
 * MotionFlow - Drag and Drop
 * Digital Commerce, Redefined
 * 
 * This file handles the drag and drop functionality for the MotionFlow plugin.
 */

// Create MotionFlow namespace
window.MotionFlow = window.MotionFlow || {};

(function($, window, document) {
    'use strict';

    /**
     * DragDrop component
     */
    MotionFlow.DragDrop = {
        /**
         * Initialize the DragDrop component
         * 
         * @param {Object} options Configuration options
         */
        init: function(options) {
            this.settings = $.extend({
                enabled: true,
                selectors: {
                    product: '.motionflow-product',
                    dragHandle: '.motionflow-drag-handle',
                    cartContainer: '.motionflow-cart-sidebar',
                    cartButton: '.motionflow-cart-button',
                    dropZones: '[data-drop-zone]'
                },
                dragThreshold: 5,  // Pixel threshold to start drag
                touchDragThreshold: 10,  // Pixel threshold for touch drag
                longPressDuration: 500,  // Milliseconds for long press on mobile
                useHammer: 'Hammer' in window,  // Use Hammer.js if available
                preventBodyScroll: true,  // Prevent body scroll during drag on mobile
                ghost: {
                    useImage: true,
                    className: 'motionflow-drag-ghost',
                    opacity: 0.8,
                    scale: 0.7
                },
                ajaxUrl: '',
                nonce: '',
                debug: false
            }, options);

            // Skip if disabled
            if (!this.settings.enabled) {
                return;
            }

            // Initialize state
            this.state = {
                dragging: false,
                dragProduct: null,
                dragImage: null,
                dragPosition: { x: 0, y: 0 },
                startPosition: { x: 0, y: 0 },
                touchDragging: false,
                longPressTimer: null,
                touchIdentifier: null,
                scrollPosition: { x: 0, y: 0 }
            };

            // Initialize Hammer.js if available
            this.initHammer();

            // Bind events
            this.bindEvents();
            
            this.log('DragDrop component initialized');
        },

        /**
         * Initialize Hammer.js (if available)
         */
        initHammer: function() {
            if (!this.settings.useHammer) {
                return;
            }

            var self = this;
            var settings = this.settings;

            // Create Hammer manager for products
            $(settings.selectors.product).each(function() {
                var hammer = new Hammer(this);
                
                // Configure Hammer
                hammer.get('press').set({ time: settings.longPressDuration });
                
                // Add press event
                hammer.on('press', function(e) {
                    self.handleHammerPress(e, $(this.element));
                });
                
                // Store Hammer instance
                $(this).data('hammer', hammer);
            });

            // Create Hammer manager for cart button
            if ($(settings.selectors.cartButton).length) {
                var cartHammer = new Hammer($(settings.selectors.cartButton)[0]);
                
                // Configure Hammer
                cartHammer.get('pan').set({ direction: Hammer.DIRECTION_ALL });
                
                // Add pan events
                cartHammer.on('panstart', function(e) {
                    self.handleCartButtonPanStart(e, $(this.element));
                });
                
                cartHammer.on('panmove', function(e) {
                    self.handleCartButtonPanMove(e, $(this.element));
                });
                
                cartHammer.on('panend', function(e) {
                    self.handleCartButtonPanEnd(e, $(this.element));
                });
                
                // Store Hammer instance
                $(settings.selectors.cartButton).data('hammer', cartHammer);
            }
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;
            var settings = this.settings;

            // Mouse events on desktop
            if (!this.isTouchDevice()) {
                // Drag start
                $(document).on('mousedown', settings.selectors.dragHandle, function(e) {
                    self.handleDragStart(e, $(this).closest(settings.selectors.product));
                });
                
                // Drag move
                $(document).on('mousemove', function(e) {
                    self.handleDragMove(e);
                });
                
                // Drag end
                $(document).on('mouseup', function(e) {
                    self.handleDragEnd(e);
                });
            } else {
                // Touch events on mobile (fallback if Hammer.js is not available)
                if (!settings.useHammer) {
                    // Touch start
                    $(document).on('touchstart', settings.selectors.dragHandle, function(e) {
                        self.handleTouchStart(e, $(this).closest(settings.selectors.product));
                    });
                    
                    // Touch move
                    $(document).on('touchmove', function(e) {
                        self.handleTouchMove(e);
                    });
                    
                    // Touch end
                    $(document).on('touchend touchcancel', function(e) {
                        self.handleTouchEnd(e);
                    });
                }
            }

            // Cart button click
            $(document).on('click', settings.selectors.cartButton, function(e) {
                // Skip if dragging
                if (self.state.dragging) {
                    e.preventDefault();
                    return;
                }
                
                self.toggleCart();
            });

            // Track whether dropzones are visible
            $(window).on('scroll resize', function() {
                self.updateDropZoneVisibility();
            });
        },

        /**
         * Handle drag start
         * 
         * @param {Event}  e        The mouse event
         * @param {jQuery} $product The product element
         */
        handleDragStart: function(e, $product) {
            // Skip if already dragging
            if (this.state.dragging) {
                return;
            }
            
            // Skip if not left button
            if (e.which !== 1) {
                return;
            }
            
            // Prevent default
            e.preventDefault();
            
            // Get product data
            var productId = $product.data('product-id');
            
            // Skip if no product ID
            if (!productId) {
                return;
            }
            
            // Store start position
            this.state.startPosition = {
                x: e.clientX,
                y: e.clientY
            };
            
            // Store product
            this.state.dragProduct = $product;
            
            // Store scroll position
            this.state.scrollPosition = {
                x: window.pageXOffset,
                y: window.pageYOffset
            };
        },

        /**
         * Handle drag move
         * 
         * @param {Event} e The mouse event
         */
        handleDragMove: function(e) {
            // Skip if not dragging
            if (!this.state.dragProduct) {
                return;
            }
            
            // Calculate distance
            var distX = Math.abs(e.clientX - this.state.startPosition.x);
            var distY = Math.abs(e.clientY - this.state.startPosition.y);
            
            // Check if drag threshold is reached
            if (!this.state.dragging && (distX > this.settings.dragThreshold || distY > this.settings.dragThreshold)) {
                // Start dragging
                this.startDrag(e.clientX, e.clientY);
            }
            
            // Update drag position
            if (this.state.dragging) {
                this.state.dragPosition = {
                    x: e.clientX,
                    y: e.clientY
                };
                
                // Move drag ghost
                this.moveDragGhost();
                
                // Check drop zones
                this.checkDropZones();
            }
        },

        /**
         * Handle drag end
         * 
         * @param {Event} e The mouse event
         */
        handleDragEnd: function(e) {
            // Skip if not dragging
            if (!this.state.dragging && !this.state.dragProduct) {
                return;
            }
            
            // Check if dropped on a drop zone
            if (this.state.dragging) {
                this.handleDrop();
            }
            
            // Clean up
            this.endDrag();
        },

        /**
         * Handle touch start
         * 
         * @param {Event}  e        The touch event
         * @param {jQuery} $product The product element
         */
        handleTouchStart: function(e, $product) {
            // Skip if already dragging
            if (this.state.dragging || this.state.touchDragging) {
                return;
            }
            
            // Get touch
            var touch = e.originalEvent.touches[0];
            
            // Skip if no touch
            if (!touch) {
                return;
            }
            
            // Get product data
            var productId = $product.data('product-id');
            
            // Skip if no product ID
            if (!productId) {
                return;
            }
            
            // Store touch identifier
            this.state.touchIdentifier = touch.identifier;
            
            // Store start position
            this.state.startPosition = {
                x: touch.clientX,
                y: touch.clientY
            };
            
            // Store product
            this.state.dragProduct = $product;
            
            // Store scroll position
            this.state.scrollPosition = {
                x: window.pageXOffset,
                y: window.pageYOffset
            };
            
            // Start long press timer
            this.state.longPressTimer = setTimeout(function() {
                // Start touch dragging
                this.startTouchDrag(touch.clientX, touch.clientY);
            }.bind(this), this.settings.longPressDuration);
        },

        /**
         * Handle touch move
         * 
         * @param {Event} e The touch event
         */
        handleTouchMove: function(e) {
            // Skip if not touch dragging
            if (!this.state.dragProduct) {
                return;
            }
            
            // Find touch
            var touch = null;
            
            for (var i = 0; i < e.originalEvent.changedTouches.length; i++) {
                if (e.originalEvent.changedTouches[i].identifier === this.state.touchIdentifier) {
                    touch = e.originalEvent.changedTouches[i];
                    break;
                }
            }
            
            // Skip if touch not found
            if (!touch) {
                return;
            }
            
            // Calculate distance
            var distX = Math.abs(touch.clientX - this.state.startPosition.x);
            var distY = Math.abs(touch.clientY - this.state.startPosition.y);
            
            // Check if moving (cancel long press)
            if (!this.state.touchDragging && (distX > this.settings.touchDragThreshold || distY > this.settings.touchDragThreshold)) {
                // Cancel long press timer
                clearTimeout(this.state.longPressTimer);
            }
            
            // Update drag position
            if (this.state.touchDragging) {
                // Prevent default (scroll)
                e.preventDefault();
                
                this.state.dragPosition = {
                    x: touch.clientX,
                    y: touch.clientY
                };
                
                // Move drag ghost
                this.moveDragGhost();
                
                // Check drop zones
                this.checkDropZones();
            }
        },

        /**
         * Handle touch end
         * 
         * @param {Event} e The touch event
         */
        handleTouchEnd: function(e) {
            // Skip if not touch dragging
            if (!this.state.touchDragging && !this.state.dragProduct) {
                return;
            }
            
            // Find touch
            var found = false;
            
            for (var i = 0; i < e.originalEvent.changedTouches.length; i++) {
                if (e.originalEvent.changedTouches[i].identifier === this.state.touchIdentifier) {
                    found = true;
                    break;
                }
            }
            
            // Skip if touch not found
            if (!found) {
                return;
            }
            
            // Cancel long press timer
            clearTimeout(this.state.longPressTimer);
            
            // Check if dropped on a drop zone
            if (this.state.touchDragging) {
                this.handleDrop();
            }
            
            // Clean up
            this.endDrag();
        },

        /**
         * Handle Hammer press
         * 
         * @param {Event}  e        The Hammer event
         * @param {jQuery} $product The product element
         */
        handleHammerPress: function(e, $product) {
            // Skip if already dragging
            if (this.state.dragging || this.state.touchDragging) {
                return;
            }
            
            // Get product data
            var productId = $product.data('product-id');
            
            // Skip if no product ID
            if (!productId) {
                return;
            }
            
            // Start touch dragging
            this.startTouchDrag(e.center.x, e.center.y, $product);
        },

        /**
         * Handle cart button pan start
         * 
         * @param {Event}  e         The Hammer event
         * @param {jQuery} $cartButton The cart button element
         */
        handleCartButtonPanStart: function(e, $cartButton) {
            // Skip if already dragging
            if (this.state.dragging || this.state.touchDragging) {
                return;
            }
            
            // Store original position
            $cartButton.data('original-position', {
                top: parseInt($cartButton.css('top'), 10),
                left: parseInt($cartButton.css('left'), 10),
                right: parseInt($cartButton.css('right'), 10),
                bottom: parseInt($cartButton.css('bottom'), 10)
            });
            
            // Add dragging class
            $cartButton.addClass('motionflow-dragging');
        },

        /**
         * Handle cart button pan move
         * 
         * @param {Event}  e         The Hammer event
         * @param {jQuery} $cartButton The cart button element
         */
        handleCartButtonPanMove: function(e, $cartButton) {
            // Skip if not dragging
            if (!$cartButton.hasClass('motionflow-dragging')) {
                return;
            }
            
            // Get original position
            var originalPosition = $cartButton.data('original-position');
            
            // Skip if no original position
            if (!originalPosition) {
                return;
            }
            
            // Update position
            if (originalPosition.right !== 'auto' && originalPosition.right !== undefined) {
                // Right-aligned
                $cartButton.css({
                    right: originalPosition.right - e.deltaX,
                    bottom: originalPosition.bottom + e.deltaY
                });
            } else {
                // Left-aligned
                $cartButton.css({
                    left: originalPosition.left + e.deltaX,
                    top: originalPosition.top + e.deltaY
                });
            }
        },

        /**
         * Handle cart button pan end
         * 
         * @param {Event}  e         The Hammer event
         * @param {jQuery} $cartButton The cart button element
         */
        handleCartButtonPanEnd: function(e, $cartButton) {
            // Skip if not dragging
            if (!$cartButton.hasClass('motionflow-dragging')) {
                return;
            }
            
            // Remove dragging class
            $cartButton.removeClass('motionflow-dragging');
            
            // Save new position in localStorage
            if (window.localStorage) {
                localStorage.setItem('motionflow_cart_button_position', JSON.stringify({
                    top: parseInt($cartButton.css('top'), 10),
                    left: parseInt($cartButton.css('left'), 10),
                    right: parseInt($cartButton.css('right'), 10),
                    bottom: parseInt($cartButton.css('bottom'), 10)
                }));
            }
            
            // Clear original position
            $cartButton.data('original-position', null);
        },

        /**
         * Start drag
         * 
         * @param {number} x The X position
         * @param {number} y The Y position
         */
        startDrag: function(x, y) {
            var self = this;
            var settings = this.settings;
            
            // Set dragging state
            this.state.dragging = true;
            
            // Set drag position
            this.state.dragPosition = {
                x: x,
                y: y
            };
            
            // Create drag ghost
            this.createDragGhost();
            
            // Add body class
            $('body').addClass('motionflow-dragging');
            
            // Update drop zone visibility
            this.updateDropZoneVisibility();
            
            // Show cart if not visible
            if (!$(settings.selectors.cartContainer).hasClass('motionflow-active')) {
                this.openCart();
            }
            
            // Trigger event
            $(document).trigger('motionflow:dragdrop:start', [this.state.dragProduct]);
            
            this.log('Drag started', {
                product: this.state.dragProduct.data('product-id'),
                position: this.state.dragPosition
            });
        },

        /**
         * Start touch drag
         * 
         * @param {number} x        The X position
         * @param {number} y        The Y position
         * @param {jQuery} $product The product element (optional)
         */
        startTouchDrag: function(x, y, $product) {
            var self = this;
            var settings = this.settings;
            
            // Use provided product or stored one
            var product = $product || this.state.dragProduct;
            
            // Skip if no product
            if (!product) {
                return;
            }
            
            // Store product
            this.state.dragProduct = product;
            
            // Set touch dragging state
            this.state.touchDragging = true;
            
            // Set drag position
            this.state.dragPosition = {
                x: x,
                y: y
            };
            
            // Create drag ghost
            this.createDragGhost();
            
            // Add body class
            $('body').addClass('motionflow-touch-dragging');
            
            // Prevent body scroll
            if (settings.preventBodyScroll) {
                $('body').css({
                    overflow: 'hidden'
                });
            }
            
            // Update drop zone visibility
            this.updateDropZoneVisibility();
            
            // Show cart if not visible
            if (!$(settings.selectors.cartContainer).hasClass('motionflow-active')) {
                this.openCart();
            }
            
            // Add haptic feedback on supported devices
            if ('vibrate' in navigator) {
                navigator.vibrate(50);
            }
            
            // Trigger event
            $(document).trigger('motionflow:dragdrop:start', [this.state.dragProduct]);
            
            this.log('Touch drag started', {
                product: this.state.dragProduct.data('product-id'),
                position: this.state.dragPosition
            });
        },

        /**
         * End drag
         */
        endDrag: function() {
            // Remove drag ghost
            if (this.state.dragImage) {
                this.state.dragImage.remove();
                this.state.dragImage = null;
            }
            
            // Remove body classes
            $('body').removeClass('motionflow-dragging motionflow-touch-dragging');
            
            // Restore body scroll
            if (this.settings.preventBodyScroll) {
                $('body').css({
                    overflow: ''
                });
            }
            
            // Remove hover class from drop zones
            $(this.settings.selectors.dropZones).removeClass('motionflow-drop-zone-hover');
            
            // Reset state
            var wasProduct = this.state.dragProduct ? this.state.dragProduct.data('product-id') : null;
            
            this.state = {
                dragging: false,
                dragProduct: null,
                dragImage: null,
                dragPosition: { x: 0, y: 0 },
                startPosition: { x: 0, y: 0 },
                touchDragging: false,
                longPressTimer: null,
                touchIdentifier: null,
                scrollPosition: { x: 0, y: 0 }
            };
            
            // Trigger event
            $(document).trigger('motionflow:dragdrop:end', [wasProduct]);
            
            this.log('Drag ended');
        },

        /**
         * Create drag ghost
         */
        createDragGhost: function() {
            var self = this;
            var settings = this.settings;
            
            // Skip if no product
            if (!this.state.dragProduct) {
                return;
            }
            
            // Create ghost
            var $ghost = $('<div>').addClass(settings.ghost.className);
            
            // Add product data
            $ghost.attr('data-product-id', this.state.dragProduct.data('product-id'));
            
            // Get product image
            var $productImage = this.state.dragProduct.find('img').first();
            
            if (settings.ghost.useImage && $productImage.length) {
                // Clone image
                var $ghostImage = $('<img>').attr('src', $productImage.attr('src'));
                
                // Add image to ghost
                $ghost.append($ghostImage);
            } else {
                // Use product title
                var productTitle = this.state.dragProduct.find('.motionflow-product-title').text();
                
                // Add title to ghost
                $ghost.text(productTitle);
            }
            
            // Apply styles
            $ghost.css({
                position: 'fixed',
                zIndex: 9999,
                opacity: settings.ghost.opacity,
                transform: 'translate(-50%, -50%) scale(' + settings.ghost.scale + ')',
                pointerEvents: 'none'
            });
            
            // Add ghost to body
            $('body').append($ghost);
            
            // Store ghost
            this.state.dragImage = $ghost;
            
            // Position ghost
            this.moveDragGhost();
        },

        /**
         * Move drag ghost
         */
        moveDragGhost: function() {
            // Skip if no ghost
            if (!this.state.dragImage) {
                return;
            }
            
            // Position ghost
            this.state.dragImage.css({
                left: this.state.dragPosition.x + 'px',
                top: this.state.dragPosition.y + 'px'
            });
        },

        /**
         * Check drop zones
         */
        checkDropZones: function() {
            var self = this;
            var settings = this.settings;
            
            // Skip if not dragging
            if (!this.state.dragging && !this.state.touchDragging) {
                return;
            }
            
            // Get drop zones
            var $dropZones = $(settings.selectors.dropZones);
            
            // Remove hover class from all drop zones
            $dropZones.removeClass('motionflow-drop-zone-hover');
            
            // Check each drop zone
            $dropZones.each(function() {
                // Get drop zone
                var $dropZone = $(this);
                
                // Skip if not visible
                if (!$dropZone.is(':visible')) {
                    return;
                }
                
                // Get drop zone position
                var dropZoneRect = $dropZone[0].getBoundingClientRect();
                
                // Check if drag position is inside drop zone
                if (
                    self.state.dragPosition.x >= dropZoneRect.left &&
                    self.state.dragPosition.x <= dropZoneRect.right &&
                    self.state.dragPosition.y >= dropZoneRect.top &&
                    self.state.dragPosition.y <= dropZoneRect.bottom
                ) {
                    // Add hover class
                    $dropZone.addClass('motionflow-drop-zone-hover');
                }
            });
        },

        /**
         * Handle drop
         */
        handleDrop: function() {
            var self = this;
            var settings = this.settings;
            
            // Skip if not dragging
            if (!this.state.dragging && !this.state.touchDragging) {
                return;
            }
            
            // Get product data
            var productId = this.state.dragProduct.data('product-id');
            
            // Skip if no product ID
            if (!productId) {
                return;
            }
            
            // Get drop zones
            var $dropZones = $(settings.selectors.dropZones);
            
            // Check each drop zone
            var dropped = false;
            
            $dropZones.each(function() {
                // Skip if already dropped
                if (dropped) {
                    return;
                }
                
                // Get drop zone
                var $dropZone = $(this);
                
                // Skip if not visible
                if (!$dropZone.is(':visible')) {
                    return;
                }
                
                // Get drop zone position
                var dropZoneRect = $dropZone[0].getBoundingClientRect();
                
                // Check if drag position is inside drop zone
                if (
                    self.state.dragPosition.x >= dropZoneRect.left &&
                    self.state.dragPosition.x <= dropZoneRect.right &&
                    self.state.dragPosition.y >= dropZoneRect.top &&
                    self.state.dragPosition.y <= dropZoneRect.bottom
                ) {
                    // Handle drop on this zone
                    self.handleDropOnZone($dropZone, productId);
                    
                    // Set dropped flag
                    dropped = true;
                }
            });
            
            // Add haptic feedback on supported devices
            if (dropped && 'vibrate' in navigator) {
                navigator.vibrate(100);
            }
        },

        /**
         * Handle drop on zone
         * 
         * @param {jQuery} $dropZone The drop zone
         * @param {number} productId The product ID
         */
        handleDropOnZone: function($dropZone, productId) {
            var self = this;
            var settings = this.settings;
            
            // Get drop zone type
            var dropZoneType = $dropZone.data('drop-zone');
            
            // Skip if no drop zone type
            if (!dropZoneType) {
                return;
            }
            
            // Handle drop based on type
            switch (dropZoneType) {
                case 'cart':
                case 'cart-button':
                    // Add to cart
                    this.addToCart(productId);
                    break;
                
                // Add more drop zone types here
                
                default:
                    // Trigger custom event
                    $(document).trigger('motionflow:dragdrop:dropped', [productId, $dropZone, dropZoneType]);
                    break;
            }
            
            this.log('Dropped on zone', {
                product: productId,
                zone: dropZoneType
            });
        },

        /**
         * Add to cart
         * 
         * @param {number} productId The product ID
         */
        addToCart: function(productId) {
            var self = this;
            var settings = this.settings;
            
            // Skip if no product ID
            if (!productId) {
                return;
            }
            
            // Skip if no AJAX URL or nonce
            if (!settings.ajaxUrl || !settings.nonce) {
                this.log('Add to cart error: No AJAX URL or nonce', {}, 'error');
                return;
            }
            
            // Show loading indicator
            this.showLoading();
            
            // Prepare data
            var data = {
                action: 'motionflow_add_to_cart',
                nonce: settings.nonce,
                product_id: productId,
                quantity: 1
            };
            
            // Log request
            this.log('Add to cart request', data);
            
            // Send AJAX request
            $.ajax({
                url: settings.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    // Hide loading indicator
                    self.hideLoading();
                    
                    if (response.success) {
                        // Log success
                        self.log('Add to cart success', response);
                        
                        // Update cart fragments
                        if (response.fragments && typeof response.fragments === 'object') {
                            $.each(response.fragments, function(selector, content) {
                                $(selector).html(content);
                            });
                        }
                        
                        // Show notification
                        self.showNotification('Product added to cart');
                        
                        // Show cart if not visible
                        if (!$(settings.selectors.cartContainer).hasClass('motionflow-active')) {
                            self.openCart();
                        }
                        
                        // Animate cart item
                        self.animateCartItem(productId);
                        
                        // Trigger custom event
                        $(document).trigger('motionflow:cart:added', [productId, 1, response]);
                    } else {
                        // Log error
                        self.log('Add to cart error', response, 'error');
                        
                        // Show error message
                        self.showError(response.message || 'Error adding product to cart');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide loading indicator
                    self.hideLoading();
                    
                    // Log error
                    self.log('Add to cart AJAX error', { status: status, error: error }, 'error');
                    
                    // Show error message
                    self.showError('Error communicating with server');
                }
            });
        },

        /**
         * Toggle cart
         */
        toggleCart: function() {
            var settings = this.settings;
            
            // Get cart container
            var $cart = $(settings.selectors.cartContainer);
            
            // Toggle active class
            if ($cart.hasClass('motionflow-active')) {
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
            
            // Get cart container
            var $cart = $(settings.selectors.cartContainer);
            
            // Add active class
            $cart.addClass('motionflow-active');
            
            // Add cart-open class to body
            $('body').addClass('motionflow-cart-open');
            
            // Trigger custom event
            $(document).trigger('motionflow:cart:opened', [$cart]);
        },

        /**
         * Close cart
         */
        closeCart: function() {
            var settings = this.settings;
            
            // Get cart container
            var $cart = $(settings.selectors.cartContainer);
            
            // Remove active class
            $cart.removeClass('motionflow-active');
            
            // Remove cart-open class from body
            $('body').removeClass('motionflow-cart-open');
            
            // Trigger custom event
            $(document).trigger('motionflow:cart:closed', [$cart]);
        },

        /**
         * Update drop zone visibility
         */
        updateDropZoneVisibility: function() {
            var settings = this.settings;
            
            // Get drop zones
            var $dropZones = $(settings.selectors.dropZones);
            
            // Update each drop zone
            $dropZones.each(function() {
                // Get drop zone
                var $dropZone = $(this);
                
                // Get drop zone position
                var dropZoneRect = $dropZone[0].getBoundingClientRect();
                
                // Check if visible
                var isVisible = (
                    dropZoneRect.width > 0 &&
                    dropZoneRect.height > 0 &&
                    dropZoneRect.top >= 0 &&
                    dropZoneRect.left >= 0 &&
                    dropZoneRect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    dropZoneRect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
                
                // Update visibility class
                if (isVisible) {
                    $dropZone.addClass('motionflow-drop-zone-visible');
                } else {
                    $dropZone.removeClass('motionflow-drop-zone-visible');
                }
            });
        },

        /**
         * Animate cart item
         * 
         * @param {number} productId The product ID
         */
        animateCartItem: function(productId) {
            var settings = this.settings;
            
            // Find cart item
            var $cartItem = $(settings.selectors.cartContainer)
                .find('.motionflow-cart-item[data-product-id="' + productId + '"]');
            
            // Skip if not found
            if (!$cartItem.length) {
                return;
            }
            
            // Add animation class
            $cartItem.addClass('motionflow-cart-item-adding');
            
            // Remove class after animation
            setTimeout(function() {
                $cartItem.removeClass('motionflow-cart-item-adding');
            }, 500);
        },

        /**
         * Show loading
         */
        showLoading: function() {
            // Create loading element if it doesn't exist
            if (!$('.motionflow-loading').length) {
                $('body').append('<div class="motionflow-loading"><div class="motionflow-loading-spinner"></div></div>');
            }
            
            // Show loading
            $('.motionflow-loading').fadeIn(200);
        },

        /**
         * Hide loading
         */
        hideLoading: function() {
            // Hide loading
            $('.motionflow-loading').fadeOut(200);
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
         * Refresh drag and drop
         */
        refresh: function() {
            var self = this;
            var settings = this.settings;
            
            // Reinitialize Hammer.js if available
            if (settings.useHammer) {
                this.initHammer();
            }
            
            // Update drop zone visibility
            this.updateDropZoneVisibility();
            
            // Load cart button position from localStorage
            if (window.localStorage && $(settings.selectors.cartButton).length) {
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
         * Check if touch device
         * 
         * @return {boolean}
         */
        isTouchDevice: function() {
            return (
                ('ontouchstart' in window) ||
                (navigator.maxTouchPoints > 0) ||
                (navigator.msMaxTouchPoints > 0)
            );
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
                    console.warn('MotionFlow DragDrop: ' + message, data);
                    break;
                case 'error':
                    console.error('MotionFlow DragDrop: ' + message, data);
                    break;
                default:
                    console.log('MotionFlow DragDrop: ' + message, data);
                    break;
            }
        }
    };

})(jQuery, window, document);