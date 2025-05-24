document.addEventListener('DOMContentLoaded', function() {
    // Livewire event listeners
    if (typeof Livewire !== 'undefined') {
        Livewire.on('spots-updated', () => {
            const spotsLeftElement = document.getElementById('spots-left');
            if (spotsLeftElement) {
                spotsLeftElement.classList.add('text-yellow-400');
                setTimeout(() => {
                    spotsLeftElement.classList.remove('text-yellow-400');
                }, 1000);
            }
        });

        Livewire.on('activity-updated', () => {
            const activityElement = document.getElementById('activityCounter');
            if (activityElement) {
                activityElement.classList.add('text-yellow-400');
                setTimeout(() => {
                    activityElement.classList.remove('text-yellow-400');
                }, 500);
            }
        });
    } else {
        console.warn('Livewire not available for event listeners setup.');
    }
});
