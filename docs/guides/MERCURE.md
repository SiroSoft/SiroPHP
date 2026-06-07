---
title: Mercure / WebSocket Guide
description: Real-time Server-Sent Events with Mercure
sidebar_position: 13
sidebar_label: Mercure / WebSocket
---

# Mercure / WebSocket Guide

**Push real-time updates to clients** using Mercure, an open protocol for Server-Sent Events (SSE). SiroPHP provides first-class integration for publishing events from PHP and subscribing from the browser.

---

## 🧠 What is Mercure?

Mercure is a protocol for pushing real-time data to web clients over Server-Sent Events (SSE). Unlike WebSockets:

- **One-way (server → client)** — perfect for notifications, live updates, feeds
- **Runs over standard HTTP** — no special proxy configuration needed
- **Auto-reconnects** — built-in browser reconnection
- **Topic-based** — clients subscribe to specific topics

SiroPHP integrates with Mercure so you can:
- Publish events from PHP anywhere (controllers, jobs, models)
- Auto-publish on Model `created`/`updated`/`deleted` events
- Subscribe to topics from the CLI for debugging

---

## 🛠 Setup

### Option 1: FrankenPHP (Recommended)

[FrankenPHP](https://frankenphp.dev) includes a built-in Mercure hub. Run your app with FrankenPHP and Mercure is ready automatically.

### Option 2: Standalone Mercure Hub

Download and run the Mercure hub from [mercure.rocks](https://mercure.rocks):

```bash
docker run -d \
  -p 80:80 \
  -e MERCURE_PUBLISHER_JWT_KEY=your-secret-key \
  -e MERCURE_SUBSCRIBER_JWT_KEY=your-secret-key \
  -e SERVER_NAME=:80 \
  -e MERCURE_EXTRA_DIRECTIVES='anonymous 1' \
  dunglas/mercure
```

Or install the binary directly: https://mercure.rocks/docs/install

---

## 📄 Configuration

Add these variables to your `.env`:

```env
# The URL of your Mercure hub (default: http://localhost:3000/.well-known/mercure)
MERCURE_HUB_URL=http://localhost:3000/.well-known/mercure

# JWT key for publishing (must match hub config)
MERCURE_PUBLISHER_JWT=your-publisher-jwt-key

# JWT key for subscribing (must match hub config)
MERCURE_SUBSCRIBER_JWT=your-subscriber-jwt-key
```

---

## 📤 Publishing Events from PHP

### Using the Mercure Facade

```php
use Siro\Core\Mercure;

// Publish to a topic
Mercure::publish('order/123', [
    'status' => 'shipped',
    'tracking' => 'TRK-9876',
]);
```

The `publish()` method sends a POST request to the Mercure hub with the topic URL and data as JSON.

### Publishing Updates

```php
// Publish with a custom topic and data
Mercure::publish('user/1/notifications', [
    'type' => 'new_message',
    'title' => 'You have a new message',
    'body' => 'Hello from John!',
]);
```

---

## 🔄 Auto-Publish on Model Events

SiroPHP can automatically publish events when a Model is created, updated, or deleted.

### Enable Auto-Publish

Add the `PublishesToMercure` trait to your model and define a `$mercureTopics` array:

```php
<?php

namespace App\Models;

use Siro\Core\DB\Model;
use Siro\Core\Mercure\PublishesToMercure;

class Order extends Model
{
    use PublishesToMercure;

    protected function mercureTopics(): array
    {
        return [
            'created' => 'order/{id}/created',
            'updated' => 'order/{id}/updated',
            'deleted' => 'order/{id}/deleted',
        ];
    }
}
```

Now whenever an `Order` is created, updated, or deleted, a Mercure event is automatically published to the corresponding topic.

### Customizing the Payload

```php
protected function mercureTopics(): array
{
    return [
        'created' => ['topic' => 'order/{id}/created', 'data' => ['event' => 'created', 'order' => $this->toArray()]],
        'updated' => ['topic' => 'order/{id}/updated', 'data' => ['event' => 'updated', 'changes' => $this->getDirty()]],
    ];
}
```

---

## 💻 CLI: Mercure Subscriber

Subscribe to a Mercure topic directly from the terminal for debugging:

```bash
php siro mercure:subscribe order/123
```

Press `Ctrl+C` to exit. This is useful for testing and debugging real-time events during development.

---

## 🌐 Frontend JavaScript Example

Subscribe to Mercure topics from the browser using the `EventSource` API:

```javascript
// Connect to the Mercure hub
const url = new URL('http://localhost:3000/.well-known/mercure');
url.searchParams.append('topic', 'http://localhost:8080/order/123');

const eventSource = new EventSource(url);

// Listen for all events
eventSource.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log('Mercure update:', data);
  // Example: show notification
  showNotification(data.title, data.body);
};

// Listen for specific event types
eventSource.addEventListener('order/shipped', (event) => {
  const data = JSON.parse(event.data);
  updateOrderStatus(data.tracking);
});

// Handle errors and auto-reconnection
eventSource.onerror = (error) => {
  console.error('Mercure connection error:', error);
  // EventSource will auto-reconnect
};

// Clean up on page unload
window.addEventListener('beforeunload', () => {
  eventSource.close();
});
```

### Multiple Topics

```javascript
// Subscribe to multiple topics
const topics = [
  'user/1/notifications',
  'user/1/orders',
  'order/123/status',
];

const url = new URL('http://localhost:3000/.well-known/mercure');
topics.forEach(topic => {
  url.searchParams.append('topic', `http://localhost:8080/${topic}`);
});

const eventSource = new EventSource(url);
```

### React Hook Example

```tsx
import { useEffect, useState } from 'react';

function useMercure(topic: string) {
  const [data, setData] = useState(null);

  useEffect(() => {
    const url = new URL('http://localhost:3000/.well-known/mercure');
    url.searchParams.append('topic', topic);

    const eventSource = new EventSource(url);
    eventSource.onmessage = (event) => {
      setData(JSON.parse(event.data));
    };

    return () => eventSource.close();
  }, [topic]);

  return data;
}

// Usage
function OrderStatus({ orderId }: { orderId: string }) {
  const update = useMercure(`http://localhost:8080/order/${orderId}`);

  return (
    <div>
      Latest update: {JSON.stringify(update)}
    </div>
  );
}
```

---

## 🏗 Production Deployment

### With FrankenPHP

Deploy your app normally — FrankenPHP includes the Mercure hub internally:

```dockerfile
FROM dunglas/frankenphp

COPY . /app
RUN cd /app && composer install --no-dev --optimize-autoloader

ENV MERCURE_PUBLISHER_JWT=your-publisher-key \
    MERCURE_SUBSCRIBER_JWT=your-subscriber-key
```

### With Standalone Mercure Hub

```yaml
# docker-compose.yml
services:
  mercure:
    image: dunglas/mercure
    environment:
      SERVER_NAME: ':80'
      MERCURE_PUBLISHER_JWT_KEY: '!ChangeThisPublisherKey!'
      MERCURE_SUBSCRIBER_JWT_KEY: '!ChangeThisSubscriberKey!'
      MERCURE_EXTRA_DIRECTIVES: 'anonymous 1'
    ports:
      - '3000:80'

  app:
    build: .
    environment:
      MERCURE_HUB_URL: 'http://mercure/.well-known/mercure'
      MERCURE_PUBLISHER_JWT: '!ChangeThisPublisherKey!'
      MERCURE_SUBSCRIBER_JWT: '!ChangeThisSubscriberKey!'
```

---

## 🔍 Troubleshooting

### "Mercure hub connection refused"
Verify `MERCURE_HUB_URL` is correct and the hub is running.

### "JWT authentication failed"
Ensure `MERCURE_PUBLISHER_JWT` and `MERCURE_SUBSCRIBER_JWT` match the hub configuration.

### Client not receiving events
- Check browser console for SSE errors
- Verify the topic URL format matches between publisher and subscriber
- Ensure CORS is configured if using a different origin

### Events not auto-publishing
Verify the model uses `PublishesToMercure` trait and `mercureTopics()` method is defined correctly.

---

## 📖 Related Guides

- **[Queue Guide](QUEUE.md)** — Background job processing
- **[Event System](EVENTS.md)** — Pub/sub event system
- **[Quick Start](QUICKSTART.md)** — Build your first API
