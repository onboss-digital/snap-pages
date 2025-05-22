document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment page script loaded');

    // Initialize all features
    initCountdownTimer();
    initSpotsLeftCounter();
    initActivityCounter();
    initUpsellModal();
    initDownsellModal();
});

function initCountdownTimer() {
    // Get the countdown element
    const countdownElement = document.getElementById('countdown-timer');
    if (!countdownElement) return;

    // Parse initial time (format: MM:SS)
    let [minutes, seconds] = countdownElement.textContent.split(':').map(Number);
    if (isNaN(minutes)) minutes = 14;
    if (isNaN(seconds)) seconds = 59;

    // Update the countdown every second
    const countdownInterval = setInterval(() => {
        seconds--;
        if (seconds < 0) {
            minutes--;
            seconds = 59;
            if (minutes < 0) {
                clearInterval(countdownInterval);
                return;
            }
        }

        // Update the display
        countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
}

function initSpotsLeftCounter() {
    // Get the spots left element
    const spotsLeftElement = document.getElementById('spots-left');
    if (!spotsLeftElement) return;

    // Get initial spots value
    let spotsLeft = parseInt(spotsLeftElement.textContent, 10);
    if (isNaN(spotsLeft)) spotsLeft = 12;

    // Occasionally decrease the spots count to create urgency
    const decreaseInterval = setInterval(() => {
        // Random chance to decrease (20% chance)
        if (Math.random() < 0.2) {
            spotsLeft--;

            // Update the display with animation
            spotsLeftElement.textContent = spotsLeft;
            spotsLeftElement.classList.add('text-yellow-400');
            setTimeout(() => {
                spotsLeftElement.classList.remove('text-yellow-400');
            }, 1000);

            // Stop if we reach a minimum threshold
            if (spotsLeft <= 3) {
                clearInterval(decreaseInterval);
            }
        }
    }, 15000); // Check every 15 seconds
}

function initActivityCounter() {
    // Get the activity counter element
    const activityElement = document.getElementById('activityCounter');
    if (!activityElement) return;

    // Update the activity counter randomly
    const updateActivityCount = () => {
        // Generate a random number between 3 and 25
        const count = Math.floor(Math.random() * 23) + 3;

        // Update with animation
        activityElement.textContent = count;
        activityElement.classList.add('text-yellow-400');
        setTimeout(() => {
            activityElement.classList.remove('text-yellow-400');
        }, 500);
    };

    // Initial update
    updateActivityCount();

    // Set interval for periodic updates
    setInterval(updateActivityCount, 8000); // Update every 8 seconds
}

function initUpsellModal() {
    // Get the upsell modal elements
    const upsellModal = document.getElementById('upsell-modal');
    const closeUpsellBtn = document.getElementById('close-upsell');
    const upsellAcceptBtn = document.getElementById('upsell-accept');
    const upsellRejectBtn = document.getElementById('upsell-reject');

    if (!upsellModal) return;

    // Function to show the upsell modal
    window.showUpsellModal = function() {
        upsellModal.classList.remove('hidden');
        // Add animation class if needed
        upsellModal.classList.add('animate-fade');
    };

    // Function to hide the upsell modal
    const hideUpsellModal = function() {
        upsellModal.classList.add('hidden');
    };

    // Event handlers for the upsell modal buttons
    if (closeUpsellBtn) {
        closeUpsellBtn.addEventListener('click', function() {
            hideUpsellModal();
            // Show downsell after closing upsell
            setTimeout(() => window.showDownsellModal(), 500);
        });
    }

    if (upsellAcceptBtn) {
        upsellAcceptBtn.addEventListener('click', function() {
            // User accepts the upsell offer (annual plan)
            // Call Livewire method if available
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('acceptUpsell');
            }
            hideUpsellModal();
        });
    }

    if (upsellRejectBtn) {
        upsellRejectBtn.addEventListener('click', function() {
            // User rejects the upsell offer
            // Call Livewire method if available
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('rejectUpsell');
            }
            hideUpsellModal();

            // Show downsell modal after rejecting upsell
            setTimeout(() => window.showDownsellModal(), 500);
        });
    }

    // For testing: allow opening the modal directly
    // Can be called from console or other triggers
    const checkoutButton = document.getElementById('checkout-button');
    if (checkoutButton) {
        checkoutButton.addEventListener('click', function(e) {
            // Prevent default to avoid form submission for testing
            // In production, this would happen after processing
            e.preventDefault();
            // Show upsell modal after a short delay (simulating processing)
            setTimeout(showUpsellModal, 1000);
        });
    }
}

function initDownsellModal() {
    // Get the downsell modal elements
    const downsellModal = document.getElementById('downsell-modal');
    const closeDownsellBtn = document.getElementById('close-downsell');
    const downsellAcceptBtn = document.getElementById('downsell-accept');
    const downsellRejectBtn = document.getElementById('downsell-reject');

    if (!downsellModal) return;

    // Function to show the downsell modal
    window.showDownsellModal = function() {
        downsellModal.classList.remove('hidden');
        // Add animation class if needed
        downsellModal.classList.add('animate-fade');
    };

    // Function to hide the downsell modal
    const hideDownsellModal = function() {
        downsellModal.classList.add('hidden');
    };

    // Event handlers for the downsell modal buttons
    if (closeDownsellBtn) {
        closeDownsellBtn.addEventListener('click', function() {
            hideDownsellModal();
            // Proceed with original plan
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('rejectDownsell');
            }
        });
    }

    if (downsellAcceptBtn) {
        downsellAcceptBtn.addEventListener('click', function() {
            // User accepts the downsell offer (quarterly plan)
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('acceptDownsell');
            }
            hideDownsellModal();
        });
    }

    if (downsellRejectBtn) {
        downsellRejectBtn.addEventListener('click', function() {
            // User rejects the downsell offer
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('rejectDownsell');
            }
            hideDownsellModal();
        });
    }
}
