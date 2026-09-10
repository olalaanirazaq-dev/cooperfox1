importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js');

self.addEventListener('message', (event) => {
  if (event.data?.type !== 'firebase-config') return;
  firebase.initializeApp(event.data.config);
  firebase.messaging();
});

self.addEventListener('push', (event) => {
  if (event.data) {
    const payload = event.data.json();
    const notification = payload.notification || payload.data || payload;
    event.waitUntil(self.registration.showNotification(notification.title || 'Cooper Fox Realty', {
      body: notification.body || 'You have a new message.',
      icon: '/cooper%20fox%20icon.png',
      data: { url: notification.url || payload.data?.url || '/' }
    }));
  }
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data?.url || '/';
  event.waitUntil(clients.openWindow(url));
});