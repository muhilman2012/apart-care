'use strict';

const express = require('express');
const QRCode = require('qrcode');
const { Client, LocalAuth } = require('whatsapp-web.js');

const port = Number(process.env.PORT || 3000);
const apiToken = process.env.WHATSAPP_API_TOKEN;
const chromiumPath = process.env.CHROMIUM_PATH || '/usr/bin/chromium';

if (!apiToken) {
  throw new Error('WHATSAPP_API_TOKEN wajib diisi.');
}

let state = 'starting';
let latestQrDataUrl = null;
let connectedNumber = null;
let lastError = null;

const client = new Client({
  authStrategy: new LocalAuth({
    clientId: 'apart-care',
    dataPath: '/app/session',
  }),
  puppeteer: {
    executablePath: chromiumPath,
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
  },
});

client.on('qr', async (qr) => {
  state = 'awaiting_qr';
  latestQrDataUrl = await QRCode.toDataURL(qr, { width: 360, margin: 1 });
  lastError = null;
  console.log('[whatsapp] QR pairing diperbarui.');
});

client.on('authenticated', () => {
  state = 'authenticated';
  latestQrDataUrl = null;
  console.log('[whatsapp] Sesi terautentikasi.');
});

client.on('ready', () => {
  state = 'ready';
  connectedNumber = client.info?.wid?.user || null;
  latestQrDataUrl = null;
  lastError = null;
  console.log(`[whatsapp] Siap${connectedNumber ? ` sebagai ${connectedNumber}` : ''}.`);
});

client.on('auth_failure', (message) => {
  state = 'auth_failure';
  lastError = message;
  console.error('[whatsapp] Autentikasi gagal:', message);
});

client.on('disconnected', (reason) => {
  state = 'disconnected';
  connectedNumber = null;
  lastError = String(reason);
  console.warn('[whatsapp] Terputus:', reason);
});

client.on('message', (message) => {
  console.log(`[whatsapp] Pesan masuk dari ${message.from}; balasan ditangani manual.`);
});

const normalizePhone = (value) => {
  let phone = String(value || '').replace(/\D/g, '');
  if (phone.startsWith('0')) phone = `62${phone.slice(1)}`;
  if (!phone.startsWith('62')) phone = `62${phone}`;
  return phone;
};

const app = express();
app.use(express.json({ limit: '64kb' }));
app.use((request, response, next) => {
  if (request.get('X-Api-Token') !== apiToken) {
    return response.status(401).json({ error: 'Unauthorized' });
  }
  next();
});

app.get('/health', (_request, response) => {
  response.json({ state, ready: state === 'ready', connected_number: connectedNumber, last_error: lastError });
});

app.get('/qr', (_request, response) => {
  response.json({ state, qr_data_url: latestQrDataUrl });
});

app.post('/send', async (request, response) => {
  if (state !== 'ready') {
    return response.status(503).json({ error: 'WhatsApp belum terhubung.', state });
  }

  const phone = normalizePhone(request.body.phone);
  const text = String(request.body.message || '').trim();
  if (!/^62\d{8,15}$/.test(phone) || !text || text.length > 4000) {
    return response.status(422).json({ error: 'Nomor atau pesan tidak valid.' });
  }

  try {
    const chatId = `${phone}@c.us`;
    if (!(await client.isRegisteredUser(chatId))) {
      return response.status(422).json({ error: 'Nomor tidak terdaftar di WhatsApp.' });
    }
    const sent = await client.sendMessage(chatId, text, { linkPreview: true });
    response.json({ sent: true, message_id: sent?.id?._serialized || null, phone });
  } catch (error) {
    console.error('[whatsapp] Pengiriman gagal:', error);
    response.status(500).json({ error: error.message });
  }
});

app.listen(port, '0.0.0.0', () => {
  console.log(`[whatsapp] API internal aktif di port ${port}.`);
  client.initialize().catch((error) => {
    state = 'error';
    lastError = error.message;
    console.error('[whatsapp] Inisialisasi gagal:', error);
  });
});
