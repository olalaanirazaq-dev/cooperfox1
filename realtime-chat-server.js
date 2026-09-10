const express = require('express');
const http = require('http');
const fs = require('fs');
const path = require('path');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: '*' } });

const projectRoot = __dirname;
const dataDir = path.join(projectRoot, 'data');
const storeFile = path.join(dataDir, 'chat-conversations.json');

function ensureStoreFile() {
  if (!fs.existsSync(dataDir)) {
    fs.mkdirSync(dataDir, { recursive: true });
  }

  if (!fs.existsSync(storeFile)) {
    fs.writeFileSync(storeFile, JSON.stringify([], null, 2));
  }
}

function readStore() {
  ensureStoreFile();
  try {
    const content = fs.readFileSync(storeFile, 'utf8');
    const parsed = JSON.parse(content);
    return Array.isArray(parsed) ? parsed : [];
  } catch (error) {
    return [];
  }
}

function writeStore(store) {
  ensureStoreFile();
  fs.writeFileSync(storeFile, JSON.stringify(store, null, 2));
}

function normalizeKey(value) {
  return String(value || '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function upsertConversation(payload) {
  const store = readStore();
  const listingKey = normalizeKey(payload.listing_key || payload.listingKey || 'property');
  const property = payload.property || 'Property';
  const city = payload.city || 'Local';
  const participantName = payload.participant_name || 'Cooper Fox';

  let conversation = store.find((entry) => normalizeKey(entry.listing_key || '') === listingKey);

  if (!conversation) {
    conversation = {
      id: `chat-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`,
      listing_key: listingKey,
      property,
      city,
      participant_name: participantName,
      messages: [],
      updated_at: new Date().toISOString(),
    };
    store.push(conversation);
  }

  const message = {
    id: `msg-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`,
    sender: payload.sender || 'guest',
    participant_name: payload.participant_name || participantName,
    text: String(payload.text || '').trim(),
    created_at: new Date().toISOString(),
    status: payload.status || 'sent',
    read_at: payload.read_at || '',
  };

  if (message.text) {
    conversation.messages.push(message);
  }

  conversation.property = property;
  conversation.city = city;
  conversation.participant_name = participantName;
  conversation.updated_at = new Date().toISOString();

  writeStore(store);
  return { conversation, message };
}

function getConversation(listingKey) {
  const key = normalizeKey(listingKey);
  const store = readStore();
  return store.find((entry) => normalizeKey(entry.listing_key || '') === key) || null;
}

app.use(express.json({ limit: '2mb' }));
app.use(express.static(projectRoot));

app.get('/api/chat/list', (req, res) => {
  const listingKey = normalizeKey(req.query.listing_key || '');
  const store = readStore();

  const conversations = listingKey
    ? store.filter((entry) => normalizeKey(entry.listing_key || '') === listingKey)
    : store;

  res.json({ success: true, conversations });
});

app.post('/api/chat/send', (req, res) => {
  const payload = req.body || {};
  const result = upsertConversation(payload);
  const room = normalizeKey(payload.listing_key || 'property');

  io.to(room).emit('chat:message', {
    room,
    conversation: result.conversation,
    message: result.message,
  });

  res.json({ success: true, conversation: result.conversation, message: result.message });
});

app.post('/api/chat/read', (req, res) => {
  const payload = req.body || {};
  const room = normalizeKey(payload.listing_key || 'property');
  const store = readStore();

  store.forEach((entry) => {
    if (normalizeKey(entry.listing_key || '') !== room) return;
    (entry.messages || []).forEach((message) => {
      if ((message.sender || '').toLowerCase() !== 'guest') {
        message.status = 'read';
        message.read_at = new Date().toISOString();
      }
    });
  });

  writeStore(store);
  io.to(room).emit('chat:read', { room, time: new Date().toISOString() });
  res.json({ success: true });
});

app.post('/api/chat/typing', (req, res) => {
  const payload = req.body || {};
  const room = normalizeKey(payload.listing_key || 'property');
  const isTyping = Boolean(payload.is_typing);
  io.to(room).emit('chat:typing', {
    room,
    sender: payload.sender || 'guest',
    isTyping,
    participantName: payload.participant_name || 'Cooper Fox',
  });
  res.json({ success: true });
});

io.on('connection', (socket) => {
  socket.on('join-room', (listingKey) => {
    const room = normalizeKey(listingKey || 'property');
    if (room) {
      socket.join(room);
    }
  });

  socket.on('chat:send', (payload) => {
    const room = normalizeKey(payload.listing_key || 'property');
    const result = upsertConversation(payload);
    socket.to(room).emit('chat:message', {
      room,
      conversation: result.conversation,
      message: result.message,
    });
    socket.emit('chat:message', {
      room,
      conversation: result.conversation,
      message: result.message,
    });
  });

  socket.on('chat:typing', (payload) => {
    const room = normalizeKey(payload.listing_key || 'property');
    socket.to(room).emit('chat:typing', {
      room,
      sender: payload.sender || 'guest',
      isTyping: Boolean(payload.is_typing),
      participantName: payload.participant_name || 'Cooper Fox',
    });
  });

  socket.on('chat:read', (payload) => {
    const room = normalizeKey(payload.listing_key || 'property');
    const store = readStore();
    store.forEach((entry) => {
      if (normalizeKey(entry.listing_key || '') !== room) return;
      (entry.messages || []).forEach((message) => {
        if ((message.sender || '').toLowerCase() !== 'guest') {
          message.status = 'read';
          message.read_at = new Date().toISOString();
        }
      });
    });
    writeStore(store);
    socket.to(room).emit('chat:read', { room, time: new Date().toISOString() });
  });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`Realtime chat server running on http://localhost:${PORT}`);
});
