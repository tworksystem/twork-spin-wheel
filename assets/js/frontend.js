/**
 * Modern Frontend JavaScript for T-Work Spin Wheel
 * Enhanced with animations and interactions
 */

(function($) {
    'use strict';

    // ============================================
    // Global Variables
    // ============================================
    var spinWheelInstance = null;
    var isSpinning = false;
    var confettiActive = false;

    $(document).ready(function() {
        
        // ============================================
        // Initialize Spin Wheel Containers
        // ============================================
        $('.twork-spin-wheel-container').each(function() {
            var $container = $(this);
            var userId = $container.data('user-id') || getCurrentUserId();
            var wheelId = $container.data('wheel-id') || 1;

            if (userId) {
                loadWheelConfig($container, userId, wheelId);
            } else {
                $container.html('<div class="twork-error-message">Please login to use the spin wheel.</div>');
            }
        });

        // ============================================
        // Spin Button Handler
        // ============================================
        $(document).on('click', '.spin-button', function(e) {
            e.preventDefault();
            
            if (isSpinning) {
                return;
            }

            var $button = $(this);
            var $container = $button.closest('.twork-spin-wheel-container');
            var userId = $container.data('user-id') || getCurrentUserId();
            var wheelId = $container.data('wheel-id') || 1;

            processSpin($container, userId, wheelId, $button);
        });

        // ============================================
        // Close Modal Handler
        // ============================================
        $(document).on('click', '.close-modal, .twork-spin-result-modal', function(e) {
            if ($(e.target).hasClass('twork-spin-result-modal') || $(e.target).hasClass('close-modal')) {
                $('.twork-spin-result-modal').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });

        // ============================================
        // Keyboard Shortcuts
        // ============================================
        $(document).on('keydown', function(e) {
            // ESC to close modal
            if (e.key === 'Escape') {
                $('.twork-spin-result-modal').fadeOut(300, function() {
                    $(this).remove();
                });
            }
            
            // Space to spin (when wheel is focused)
            if (e.key === ' ' && !isSpinning && $('.spin-button:visible').length) {
                e.preventDefault();
                $('.spin-button:visible').first().click();
            }
        });

    });

    // ============================================
    // Load Wheel Configuration
    // ============================================
    function loadWheelConfig($container, userId, wheelId) {
        var apiUrl = tworkSpinWheel?.apiUrl || '/wp-json/twork/v1/spin-wheel/';
        
        $container.find('.spin-wheel-loading').html('<div class="twork-loading"></div> Loading...');

        $.ajax({
            url: apiUrl + 'config/' + userId,
            type: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                if (response.success && response.data) {
                    renderWheel($container, response.data);
                } else {
                    showError($container, response.message || 'Error loading spin wheel configuration.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Spin Wheel Error:', error);
                showError($container, 'Unable to load spin wheel. Please try again later.');
            }
        });
    }

    // ============================================
    // Render Wheel
    // ============================================
    function renderWheel($container, config) {
        var html = '<div class="twork-wheel-wrapper">';
        
        if (config.title) {
            html += '<h2 class="wheel-title">' + escapeHtml(config.title) + '</h2>';
        }
        
        if (config.description) {
            html += '<p class="wheel-description">' + escapeHtml(config.description) + '</p>';
        }

        html += '<div class="twork-spin-wheel-canvas-wrapper">';
        html += '<canvas class="twork-spin-wheel-canvas" width="400" height="400"></canvas>';
        html += '</div>';

        if (config.can_spin) {
            html += '<button class="spin-button" data-user-id="' + config.user_id + '">';
            html += '<span class="spin-button-text">Spin Now</span>';
            html += '<span class="spin-button-icon">🎰</span>';
            html += '</button>';
        } else {
            html += '<div class="twork-error-message">' + (config.message || 'You cannot spin at this time.') + '</div>';
        }

        if (config.spins_left !== undefined) {
            html += '<div class="spins-left">Spins Remaining: <strong>' + config.spins_left + '</strong></div>';
        }

        html += '</div>';

        $container.html(html);

        // Initialize canvas wheel
        initCanvasWheel($container.find('.twork-spin-wheel-canvas'), config);
    }

    // ============================================
    // Initialize Canvas Wheel
    // ============================================
    function initCanvasWheel($canvas, config) {
        var canvas = $canvas[0];
        var ctx = canvas.getContext('2d');
        var centerX = canvas.width / 2;
        var centerY = canvas.height / 2;
        var radius = Math.min(centerX, centerY) - 20;
        
        var prizes = config.prizes || [];
        var totalWeight = prizes.reduce(function(sum, prize) {
            return sum + (prize.probability_weight || 1);
        }, 0);

        // Draw wheel
        var currentAngle = -Math.PI / 2;
        prizes.forEach(function(prize, index) {
            var angle = (2 * Math.PI * (prize.probability_weight || 1)) / totalWeight;
            
            // Draw sector
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + angle);
            ctx.closePath();
            ctx.fillStyle = prize.sector_color || '#667eea';
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Draw text with enhanced visibility
            ctx.save();
            ctx.translate(centerX, centerY);
            ctx.rotate(currentAngle + angle / 2);
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            // Enhanced text shadow
            ctx.shadowColor = 'rgba(0, 0, 0, 0.8)';
            ctx.shadowBlur = 5;
            ctx.shadowOffsetX = 2;
            ctx.shadowOffsetY = 2;
            
            // Better contrast for text color
            var textColor = prize.text_color || '#ffffff';
            var baseColor = prize.sector_color || '#667eea';
            var isLightColor = isColorLight(baseColor);
            if (!prize.text_color) {
                textColor = isLightColor ? '#1f2937' : '#ffffff';
            }
            
            // Larger font size
            var fontSize = Math.max(16, Math.min(20, radius / 15));
            ctx.font = 'bold ' + fontSize + 'px Arial, sans-serif';
            ctx.fillStyle = textColor;
            
            var text = prize.label || prize.prize_name || 'Prize';
            var maxLength = Math.floor(radius / 18);
            var displayText = text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
            
            // Draw with stroke for better visibility
            ctx.strokeStyle = 'rgba(0, 0, 0, 0.6)';
            ctx.lineWidth = 2;
            ctx.strokeText(displayText, radius * 0.7, 0);
            ctx.fillText(displayText, radius * 0.7, 0);
            
            // Reset shadow
            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 0;
            
            ctx.restore();

            currentAngle += angle;
        });

        // Draw center circle
        ctx.beginPath();
        ctx.arc(centerX, centerY, 30, 0, 2 * Math.PI);
        ctx.fillStyle = '#fff';
        ctx.fill();
        ctx.strokeStyle = '#667eea';
        ctx.lineWidth = 3;
        ctx.stroke();

        // Draw pointer
        ctx.beginPath();
        ctx.moveTo(centerX, centerY - radius - 10);
        ctx.lineTo(centerX - 15, centerY - radius - 30);
        ctx.lineTo(centerX + 15, centerY - radius - 30);
        ctx.closePath();
        ctx.fillStyle = '#ef4444';
        ctx.fill();
    }

    // ============================================
    // Process Spin
    // ============================================
    function processSpin($container, userId, wheelId, $button) {
        if (isSpinning) {
            return;
        }

        isSpinning = true;
        $button.prop('disabled', true).addClass('spinning');
        $button.find('.spin-button-text').text('Spinning...');

        var apiUrl = tworkSpinWheel?.apiUrl || '/wp-json/twork/v1/spin-wheel/';

        // Add spinning animation to canvas
        $container.find('.twork-spin-wheel-canvas').addClass('spinning');

        $.ajax({
            url: apiUrl + 'spin',
            type: 'POST',
            data: {
                user_id: userId
            },
            dataType: 'json',
            timeout: 15000,
            success: function(response) {
                setTimeout(function() {
                    $container.find('.twork-spin-wheel-canvas').removeClass('spinning');
                    
                    if (response.success && response.data) {
                        handleSpinResult(response.data);
                    } else {
                        showError($container, response.message || 'Spin failed. Please try again.');
                        resetSpinButton($button);
                    }
                }, 3000);
            },
            error: function(xhr, status, error) {
                $container.find('.twork-spin-wheel-canvas').removeClass('spinning');
                showError($container, 'Network error. Please check your connection and try again.');
                resetSpinButton($button);
            }
        });
    }

    // ============================================
    // Handle Spin Result
    // ============================================
    function handleSpinResult(data) {
        isSpinning = false;

        // Show confetti
        if (!confettiActive) {
            showConfetti();
        }

        // Show result modal
        showResultModal(data);

        // Update spins left if available
        if (data.spins_left !== undefined) {
            $('.spins-left strong').text(data.spins_left);
        }

        // Disable button if no spins left
        if (data.spins_left === 0) {
            $('.spin-button').prop('disabled', true).text('No Spins Left');
        }
    }

    // ============================================
    // Show Result Modal
    // ============================================
    function showResultModal(data) {
        var prize = data.prize || {};
        var html = '<div class="twork-spin-result-modal">';
        html += '<div class="twork-spin-result-content">';
        
        if (prize.label) {
            html += '<div class="prize-icon">' + (prize.icon || '🎁') + '</div>';
            html += '<h2>Congratulations!</h2>';
            html += '<div class="prize-name">' + escapeHtml(prize.label) + '</div>';
            
            if (prize.type === 'points' && prize.value) {
                html += '<p class="prize-value">You won ' + escapeHtml(prize.value) + ' points!</p>';
            } else if (prize.type === 'coupon' && data.coupon_code) {
                html += '<p class="prize-value">Coupon Code: <strong>' + escapeHtml(data.coupon_code) + '</strong></p>';
            }
        } else {
            html += '<h2>Try Again!</h2>';
            html += '<div class="prize-name">Better luck next time!</div>';
        }

        html += '<button class="close-modal">Close</button>';
        html += '</div>';
        html += '</div>';

        $('body').append(html);
        $('.twork-spin-result-modal').fadeIn(300);
    }

    // ============================================
    // Show Confetti
    // ============================================
    function showConfetti() {
        confettiActive = true;
        var $confetti = $('<div class="twork-confetti"></div>');
        $('body').append($confetti);

        var colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#10b981', '#f59e0b'];
        
        for (var i = 0; i < 50; i++) {
            var $piece = $('<div class="twork-confetti-piece"></div>');
            $piece.css({
                left: Math.random() * 100 + '%',
                background: colors[Math.floor(Math.random() * colors.length)],
                animationDelay: Math.random() * 3 + 's',
                animationDuration: (Math.random() * 2 + 2) + 's'
            });
            $confetti.append($piece);
        }

        setTimeout(function() {
            $confetti.fadeOut(500, function() {
                $(this).remove();
                confettiActive = false;
            });
        }, 3000);
    }

    // ============================================
    // Show Error
    // ============================================
    function showError($container, message) {
        var $error = $('<div class="twork-error-message">' + escapeHtml(message) + '</div>');
        $container.prepend($error);
        
        setTimeout(function() {
            $error.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // ============================================
    // Reset Spin Button
    // ============================================
    function resetSpinButton($button) {
        isSpinning = false;
        $button.prop('disabled', false).removeClass('spinning');
        $button.find('.spin-button-text').text('Spin Now');
    }

    // ============================================
    // Get Current User ID
    // ============================================
    function getCurrentUserId() {
        return tworkSpinWheel?.userId || null;
    }

    // ============================================
    // Escape HTML
    // ============================================
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
    }

    // ============================================
    // Statistics Animation
    // ============================================
    function animateStatistics() {
        $('.stat-value').each(function() {
            var $this = $(this);
            var target = parseInt($this.text().replace(/[^0-9]/g, ''));
            if (!isNaN(target) && target > 0) {
                animateCounter($this, 0, target, 2000);
            }
        });
    }

    // ============================================
    // Animate Counter
    // ============================================
    function animateCounter($element, start, end, duration) {
        var startTimestamp = null;
        var step = function(timestamp) {
            if (!startTimestamp) startTimestamp = timestamp;
            var progress = Math.min((timestamp - startTimestamp) / duration, 1);
            var current = Math.floor(progress * (end - start) + start);
            $element.text(current.toLocaleString());
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Initialize statistics animation on load
    $(window).on('load', function() {
        animateStatistics();
    });

    // ============================================
    // Advanced Particle System
    // ============================================
    function createParticleSystem($container) {
        var particleCount = 30;
        var colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#10b981', '#f59e0b'];
        
        for (var i = 0; i < particleCount; i++) {
            var $particle = $('<div class="twork-particle"></div>');
            $particle.css({
                left: Math.random() * 100 + '%',
                top: Math.random() * 100 + '%',
                background: colors[Math.floor(Math.random() * colors.length)],
                animationDelay: Math.random() * 15 + 's',
                animationDuration: (Math.random() * 10 + 10) + 's'
            });
            $container.append($particle);
        }
    }

    // ============================================
    // Advanced Confetti System
    // ============================================
    function showConfettiAdvanced() {
        if (confettiActive) {
            return;
        }
        
        confettiActive = true;
        var $confetti = $('<div class="twork-confetti"></div>');
        $('body').append($confetti);

        var colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#10b981', '#f59e0b', '#3b82f6'];
        var confettiCount = 100;
        
        for (var i = 0; i < confettiCount; i++) {
            var $piece = $('<div class="twork-confetti-piece"></div>');
            var randomColor = colors[Math.floor(Math.random() * colors.length)];
            var randomLeft = Math.random() * 100;
            var randomDelay = Math.random() * 2;
            var randomDuration = Math.random() * 2 + 3;
            
            $piece.css({
                left: randomLeft + '%',
                background: randomColor,
                animationDelay: randomDelay + 's',
                animationDuration: randomDuration + 's',
                transform: 'rotate(' + (Math.random() * 360) + 'deg)'
            });
            
            $confetti.append($piece);
        }

        setTimeout(function() {
            $confetti.fadeOut(500, function() {
                $(this).remove();
                confettiActive = false;
            });
        }, 4000);
    }

    // ============================================
    // Advanced Sound Effects (Optional)
    // ============================================
    function playSoundEffect(type) {
        // Sound effects can be added here if audio files are available
        // For now, we'll use Web Audio API for simple beep sounds
        try {
            var audioContext = new (window.AudioContext || window.webkitAudioContext)();
            var oscillator = audioContext.createOscillator();
            var gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            if (type === 'spin') {
                oscillator.frequency.value = 200;
                oscillator.type = 'sine';
            } else if (type === 'win') {
                oscillator.frequency.value = 400;
                oscillator.type = 'triangle';
            } else if (type === 'lose') {
                oscillator.frequency.value = 150;
                oscillator.type = 'sawtooth';
            }
            
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            // Audio not supported or user hasn't interacted with page
            console.log('Audio not available');
        }
    }

    // ============================================
    // Enhanced Spin Processing with Effects
    // ============================================
    function processSpin($container, userId, wheelId, $button) {
        if (isSpinning) {
            return;
        }

        isSpinning = true;
        $button.prop('disabled', true).addClass('spinning');
        $button.find('.spin-button-text').text('Spinning...');

        var apiUrl = tworkSpinWheel?.apiUrl || '/wp-json/twork/v1/spin-wheel/';

        // Add spinning animation to canvas
        var $canvas = $container.find('.twork-spin-wheel-canvas');
        $canvas.addClass('spinning');
        
        // Play spin sound
        playSoundEffect('spin');

        // Add particle effects
        createParticleSystem($container);

        $.ajax({
            url: apiUrl + 'spin',
            type: 'POST',
            data: {
                user_id: userId
            },
            dataType: 'json',
            timeout: 15000,
            success: function(response) {
                setTimeout(function() {
                    $canvas.removeClass('spinning');
                    
                    if (response.success && response.data) {
                        // Play win sound
                        playSoundEffect('win');
                        handleSpinResult(response.data);
                    } else {
                        // Play lose sound
                        playSoundEffect('lose');
                        showError($container, response.message || 'Spin failed. Please try again.');
                        resetSpinButton($button);
                    }
                }, 3000);
            },
            error: function(xhr, status, error) {
                $canvas.removeClass('spinning');
                playSoundEffect('lose');
                showError($container, 'Network error. Please check your connection and try again.');
                resetSpinButton($button);
            }
        });
    }

    // ============================================
    // Enhanced Result Modal with Animations
    // ============================================
    function showResultModal(data) {
        var prize = data.prize || {};
        var html = '<div class="twork-spin-result-modal">';
        html += '<div class="twork-spin-result-content">';
        
        if (prize.label) {
            html += '<div class="prize-icon">' + (prize.icon || '🎁') + '</div>';
            html += '<h2>Congratulations!</h2>';
            html += '<div class="prize-name">' + escapeHtml(prize.label) + '</div>';
            
            if (prize.type === 'points' && prize.value) {
                html += '<p class="prize-value">You won <strong>' + escapeHtml(prize.value) + ' points</strong>!</p>';
            } else if (prize.type === 'coupon' && data.coupon_code) {
                html += '<p class="prize-value">Coupon Code: <strong>' + escapeHtml(data.coupon_code) + '</strong></p>';
                html += '<button class="button copy-coupon" data-copy="' + escapeHtml(data.coupon_code) + '">Copy Code</button>';
            } else if (prize.type === 'product' && data.product_id) {
                html += '<p class="prize-value">Product Prize Unlocked!</p>';
                html += '<a href="' + (data.product_url || '#') + '" class="button button-primary">View Product</a>';
            }
        } else {
            html += '<div class="prize-icon">😊</div>';
            html += '<h2>Try Again!</h2>';
            html += '<div class="prize-name">Better luck next time!</div>';
        }

        html += '<button class="close-modal">Close</button>';
        html += '</div>';
        html += '</div>';

        $('body').append(html);
        var $modal = $('.twork-spin-result-modal');
        $modal.fadeIn(300);
        
        // Show confetti for wins
        if (prize.label) {
            showConfettiAdvanced();
        }
        
        // Copy coupon code functionality
        $modal.find('.copy-coupon').on('click', function() {
            var code = $(this).data('copy');
            copyToClipboard(code);
            $(this).text('Copied!').prop('disabled', true);
            setTimeout(function() {
                $(this).text('Copy Code').prop('disabled', false);
            }.bind(this), 2000);
        });
    }

    // ============================================
    // Copy to Clipboard Function
    // ============================================
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showToast('Copied to clipboard!', 'success');
            }).catch(function() {
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    }

    function fallbackCopyToClipboard(text) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        try {
            document.execCommand('copy');
            showToast('Copied to clipboard!', 'success');
        } catch (err) {
            showToast('Failed to copy', 'error');
        }
        $temp.remove();
    }

    // ============================================
    // Toast Notification System
    // ============================================
    function showToast(message, type) {
        type = type || 'info';
        var $toast = $('<div class="twork-toast ' + type + '">' + message + '</div>');
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }

    // ============================================
    // Enhanced Canvas Wheel with Better Graphics
    // ============================================
    function initCanvasWheel($canvas, config) {
        var canvas = $canvas[0];
        var ctx = canvas.getContext('2d');
        var centerX = canvas.width / 2;
        var centerY = canvas.height / 2;
        var radius = Math.min(centerX, centerY) - 20;
        
        var prizes = config.prizes || [];
        var totalWeight = prizes.reduce(function(sum, prize) {
            return sum + (prize.probability_weight || 1);
        }, 0);

        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Draw wheel with gradient effects
        var currentAngle = -Math.PI / 2;
        prizes.forEach(function(prize, index) {
            var angle = (2 * Math.PI * (prize.probability_weight || 1)) / totalWeight;
            
            // Create gradient for each sector
            var gradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius);
            var baseColor = prize.sector_color || '#667eea';
            gradient.addColorStop(0, lightenColor(baseColor, 20));
            gradient.addColorStop(1, baseColor);
            
            // Draw sector
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + angle);
            ctx.closePath();
            ctx.fillStyle = gradient;
            ctx.fill();
            
            // Draw border
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 3;
            ctx.stroke();

            // Draw text with better formatting and enhanced visibility
            ctx.save();
            ctx.translate(centerX, centerY);
            ctx.rotate(currentAngle + angle / 2);
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            // Enhanced text shadow for better visibility
            ctx.shadowColor = 'rgba(0, 0, 0, 0.8)';
            ctx.shadowBlur = 6;
            ctx.shadowOffsetX = 2;
            ctx.shadowOffsetY = 2;
            
            // Calculate optimal text color with better contrast
            var textColor = prize.text_color || '#ffffff';
            // Ensure text color has sufficient contrast
            var baseColor = prize.sector_color || '#667eea';
            var isLightColor = isColorLight(baseColor);
            if (!prize.text_color) {
                textColor = isLightColor ? '#1f2937' : '#ffffff';
            }
            
            // Larger, more readable font
            var fontSize = Math.max(18, Math.min(24, radius / 12));
            ctx.font = 'bold ' + fontSize + 'px Arial, sans-serif';
            ctx.fillStyle = textColor;
            
            var text = prize.label || prize.prize_name || 'Prize';
            var maxLength = Math.floor(radius / 20);
            if (text.length > maxLength) {
                text = text.substring(0, maxLength) + '...';
            }
            
            // Draw text with stroke for better visibility
            ctx.strokeStyle = 'rgba(0, 0, 0, 0.6)';
            ctx.lineWidth = 2;
            ctx.strokeText(text, radius * 0.7, 0);
            ctx.fillText(text, radius * 0.7, 0);
            
            // Reset shadow
            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 0;
            
            ctx.restore();

            currentAngle += angle;
        });

        // Draw center circle with gradient
        var centerGradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, 30);
        centerGradient.addColorStop(0, '#fff');
        centerGradient.addColorStop(1, '#f0f0f0');
        
        ctx.beginPath();
        ctx.arc(centerX, centerY, 30, 0, 2 * Math.PI);
        ctx.fillStyle = centerGradient;
        ctx.fill();
        
        // Center border
        ctx.strokeStyle = '#667eea';
        ctx.lineWidth = 4;
        ctx.stroke();

        // Draw pointer with shadow
        ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
        ctx.shadowBlur = 10;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 5;
        
        ctx.beginPath();
        ctx.moveTo(centerX, centerY - radius - 10);
        ctx.lineTo(centerX - 20, centerY - radius - 40);
        ctx.lineTo(centerX + 20, centerY - radius - 40);
        ctx.closePath();
        ctx.fillStyle = '#ef4444';
        ctx.fill();
        ctx.strokeStyle = '#dc2626';
        ctx.lineWidth = 2;
        ctx.stroke();
        
        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;
    }

    // ============================================
    // Color Utility Functions
    // ============================================
    function lightenColor(color, percent) {
        var num = parseInt(color.replace("#", ""), 16);
        var amt = Math.round(2.55 * percent);
        var R = (num >> 16) + amt;
        var G = (num >> 8 & 0x00FF) + amt;
        var B = (num & 0x0000FF) + amt;
        return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
            (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
            (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    }
    
    // Check if a color is light (for contrast calculation)
    function isColorLight(color) {
        var hex = color.replace("#", "");
        var r = parseInt(hex.substr(0, 2), 16);
        var g = parseInt(hex.substr(2, 2), 16);
        var b = parseInt(hex.substr(4, 2), 16);
        // Calculate relative luminance
        var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luminance > 0.5;
    }

    // ============================================
    // Enhanced Statistics with Real-time Updates
    // ============================================
    function updateStatistics() {
        $('.twork-spin-wheel-stats .stat-value').each(function() {
            var $this = $(this);
            var target = parseInt($this.text().replace(/[^0-9]/g, ''));
            if (!isNaN(target) && target > 0) {
                animateCounter($this, 0, target, 2000);
            }
        });
    }

    // Auto-update statistics every 30 seconds
    setInterval(function() {
        if ($('.twork-spin-wheel-stats').length) {
            // Refresh statistics from API if needed
            updateStatistics();
        }
    }, 30000);

})(jQuery);
