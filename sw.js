self.addEventListener('push', (event) => {
  const payload = event.data && event.data.text() ? JSON.parse(event.data.text()) : {
    title: 'Cooper Fox Realty',
    body: 'You have a new update.',
    url: '/'
  };

  const notificationOptions = {
    body: payload.body || 'You have a new update.',
    icon: '/cooper%20fox%20icon.png',
    badge: '/cooper%20fox%20icon.png',
    data: {
      url: payload.url || '/'
    }
  };

  event.waitUntil(
    self.registration.showNotification(payload.title || 'Cooper Fox Realty', notificationOptions)
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification.data && event.notification.data.url ? event.notification.data.url : '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      const matchedClient = clientList.find((client) => client.url.includes(self.location.origin));

      if (matchedClient) {
        return matchedClient.focus();
      }

      return clients.openWindow(url);
    })
  );
});
