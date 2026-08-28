document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    initNotifications();
    //example window.showNotification('Saved successfully', 'success');
});

const initNotifications = () => {
    let notifications = document.querySelectorAll('.notification');
    notifications?.forEach(notification => {
        setTimeout(() => {
            notification.classList.add('hidden');
        }, 5000);

        setTimeout(() => {
            notification.remove();
        }, 6000);
    });

    document.addEventListener('click', (e) => {

        if (e.target.classList.contains('notification-close')) {
            e.preventDefault();

            let notification = e.target.closest('.notification');
            if (notification) {
                notification.remove();
            }
        }
    });
}

window.showNotification = function (message, status = 'error') {
    let wrapper = document.querySelector('.notifications');

    if (!wrapper) {
        let footer = document.querySelector('footer');
        wrapper = document.createElement('div');
        wrapper.className = 'notifications';
        footer.insertAdjacentElement('afterend', wrapper);
    }

    const item = document.createElement('div');
    item.className = 'notification ' + status;
    item.innerHTML = '<button type="button" class="notification-close" aria-label="Close notification"></button>' + message;

    wrapper.appendChild(item);

    setTimeout(() => {
        item.classList.add('hidden');
    }, 5000);

    setTimeout(() => {
        item.remove();
    }, 6000);
};