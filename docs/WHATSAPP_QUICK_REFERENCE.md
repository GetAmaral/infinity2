# WhatsApp Integration - Quick Reference

**Status:** Phase 2 Complete ✅
**Version:** 2.0 (Media + Idempotency)

---

## 🎯 What It Does

- ✅ Receive & send text messages via WhatsApp
- ✅ Receive & send media (images, videos, audio, documents)
- ✅ Download & save media files automatically
- ✅ Auto-create contacts from WhatsApp messages
- ✅ AI-powered responses via TreeFlow
- ✅ Multi-tenant organization isolation
- ✅ Retry logic (3 attempts) + webhook idempotency

**Limitations:**
- ❌ No group chats (@g.us ignored)
- ❌ No read receipts or delivery status
- ❌ No rate limiting on webhook endpoint

---

## ⚡ Quick Setup (3 Steps)

### 1. Configure Agent in Database

```sql
UPDATE agent SET
    whatsapp_instance_name = 'sdr-agent-1',                    -- Must match Evolution API instance
    whatsapp_phone = '+5511999999999',                         -- Your WhatsApp number (E.164 format)
    whatsapp_server_url = 'https://evolution.yourdomain.com',  -- Evolution API URL
    whatsapp_api_key = 'your-instance-api-key',                -- Instance-specific token
    whatsapp_active = true,
    whatsapp_status = 'connected'
WHERE id = 'your-agent-id';
```

**Important:** `whatsapp_instance_name` must be globally unique.

### 2. Configure Evolution API Webhook

```bash
curl -X POST https://evolution.yourdomain.com/webhook/set/sdr-agent-1 \
  -H "apikey: your-instance-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://luminai.yourdomain.com/api/webhooks/whatsapp/evolution",
    "webhook_by_events": false,
    "events": ["MESSAGES_UPSERT", "CONNECTION_UPDATE"]
  }'
```

### 3. Verify Connection

```bash
curl -X GET https://evolution.yourdomain.com/instance/connectionState/sdr-agent-1 \
  -H "apikey: your-instance-api-key"

# Expected response: { "instance": { "state": "open" } }
```

---

## 🔄 How It Works

```
WhatsApp User → Evolution API → Webhook → Luminai
                                            ↓
                              1. Find Agent by instance name
                              2. Normalize phone (+5511999999999)
                              3. Find/Create Contact (no dummy email!)
                              4. Find/Create Talk (channel=2)
                              5. Create TalkMessage (inbound)
                              6. Download media if present
                                            ↓
                                    AI Processing (TreeFlow)
                                            ↓
                              7. Create TalkMessage (outbound)
                              8. Send via Evolution API
                                            ↓
WhatsApp User ← Evolution API ← Luminai Response
```

**Channel Constants (Talk.channel):**
- `0` = Unknown
- `1` = Web
- `2` = WhatsApp ← Used for WhatsApp conversations
- `3` = Email

---

## 📱 Media Files

### Incoming Media
- **Supported:** Images, videos, audio, documents
- **Storage:** `public/uploads/talk_attachments/`
- **Behavior:** Files downloaded → Attachment entity created → Linked to TalkMessage
- **Caption:** Extracted to `TalkMessage.body`

### Outgoing Media
```php
// Send image with caption
$this->evolutionApiClient->sendMediaMessage(
    $serverUrl,
    $apiKey,
    $instanceName,
    $phoneNumber,
    'https://example.com/image.jpg',  // Media URL
    'Check our new product!',          // Caption
    'image'                            // Type: image|video|audio|document
);
```

**Supported Types:**
- **Images:** JPEG, PNG, GIF, WebP
- **Videos:** MP4, MOV, WebM, AVI
- **Audio:** MP3, OGG, WAV, AAC
- **Documents:** PDF, DOC, XLSX, TXT, ZIP

---

## 🧪 Testing

### Test Webhook
```bash
curl -X POST https://localhost/api/webhooks/whatsapp/evolution \
  -H "Content-Type: application/json" \
  -d '{
    "event": "messages.upsert",
    "instance": "sdr-agent-1",
    "data": {
      "key": {
        "remoteJid": "5511999999999@s.whatsapp.net",
        "fromMe": false,
        "id": "TEST123"
      },
      "pushName": "Test User",
      "message": {
        "conversation": "Hello, I need help"
      },
      "messageTimestamp": 1699876543
    }
  }'

# Expected: {"success":true,"message":"Webhook processed"}
```

### Verify Contact Created
```sql
SELECT name, phone, email, lead_source
FROM contact
WHERE phone = '+5511999999999';

-- Expected: email = NULL, lead_source = 'WhatsApp'
```

### Verify Talk Created
```sql
SELECT subject, channel, status
FROM talk
WHERE contact_id = (SELECT id FROM contact WHERE phone = '+5511999999999')
  AND channel = 2;

-- Expected: channel = 2 (WhatsApp)
```

### Check Messages
```sql
SELECT direction, channel, body, metadata
FROM talk_message
WHERE talk_id = 'your-talk-id'
ORDER BY sent_at DESC;

-- Inbound: direction='inbound', channel='whatsapp'
-- Outbound: direction='outbound', metadata contains whatsappSent=true
```

---

## 🔧 Troubleshooting

### No Messages Received

**Check webhook configuration:**
```bash
curl https://evolution.yourdomain.com/webhook/find/sdr-agent-1 \
  -H "apikey: your-api-key"
```

**Check agent is active:**
```sql
SELECT whatsapp_instance_name, whatsapp_active, active
FROM agent
WHERE whatsapp_instance_name = 'sdr-agent-1';
```

**Check logs:**
```bash
docker-compose logs app | grep -i whatsapp
```

### Messages Not Sending

**Check Evolution API connection:**
```bash
curl https://evolution.yourdomain.com/instance/connectionState/sdr-agent-1 \
  -H "apikey: your-api-key"

# Expected: { "instance": { "state": "open" } }
```

**Check outbound message errors:**
```sql
SELECT metadata
FROM talk_message
WHERE direction = 'outbound' AND channel = 'whatsapp'
ORDER BY created_at DESC LIMIT 1;

-- Look for:
-- whatsappSent = false → Failed
-- whatsappError → Error message
-- whatsappAttempts = 3 → All retries exhausted
```

### Duplicate Contacts

**Find duplicates:**
```sql
SELECT phone, COUNT(*), array_agg(id) as contact_ids
FROM contact
WHERE organization_id = 'your-org-id'
GROUP BY phone
HAVING COUNT(*) > 1;
```

**Verify unique indexes exist:**
```sql
SELECT indexname FROM pg_indexes
WHERE tablename = 'contact' AND indexname LIKE '%phone%';

-- Should show: idx_contact_phone_org, idx_contact_mobile_org
```

### Media Not Downloading

**Check attachment was created:**
```sql
SELECT id, file_type, file_size, filename
FROM attachment
WHERE talk_message_id = 'your-message-id';
```

**Verify file on disk:**
```bash
ls -lh /home/user/inf/app/public/uploads/talk_attachments/
```

**Check logs for download errors:**
```bash
docker-compose logs app | grep -i "media download"
```

---

## 📝 Environment Variables

Add to `/home/user/inf/app/.env`:

```bash
###> WhatsApp Evolution API Configuration ###
EVOLUTION_API_DEFAULT_SERVER_URL=https://evolution-api.yourdomain.com
EVOLUTION_API_DEFAULT_APIKEY=your-default-api-key
WHATSAPP_WEBHOOK_BASE_URL=https://localhost/api/webhooks/whatsapp/evolution
###< WhatsApp Evolution API Configuration ###
```

**Note:** These are defaults. Each agent has its own config in the database (recommended).

---

## 🗂️ Key Files

| File | Purpose |
|------|---------|
| `src/Service/WhatsApp/WhatsAppService.php` | Main business logic |
| `src/Service/WhatsApp/EvolutionApiClient.php` | HTTP client for Evolution API |
| `src/Service/WhatsApp/MediaDownloadService.php` | Media file downloads |
| `src/Service/WhatsApp/WebhookIdempotencyService.php` | Duplicate prevention |
| `src/Controller/Api/WhatsAppWebhookController.php` | Webhook endpoint |
| `src/EventSubscriber/TalkMessageSubscriber.php` | Sends outbound messages |
| `src/Entity/Talk.php` | Channel constants |

---

## 🔐 Security Notes

**Current Setup:**
- ✅ Organization isolation (multi-tenant safe)
- ✅ Unique phone per organization
- ✅ Unique instance names globally
- ✅ Webhook idempotency (prevents duplicate processing)
- ⚠️ Webhook endpoint is public (no auth)
- ⚠️ No rate limiting

**Production Recommendations:**
1. Add IP whitelist for Evolution API server
2. Implement rate limiting (100 req/min)
3. Monitor webhook endpoint for abuse
4. Encrypt API keys at rest

---

## 📊 Quick Commands

```bash
# View WhatsApp logs
docker-compose logs -f app | grep -i whatsapp

# Clear cache
docker-compose exec app php bin/console cache:clear

# Check webhook endpoint
curl -k https://localhost/api/webhooks/whatsapp/evolution

# View media files
ls -lh app/public/uploads/talk_attachments/
```

---

## 🎓 Common Scenarios

### Scenario 1: User sends text message
1. Webhook receives message
2. Contact auto-created (if new)
3. Talk auto-created (if new)
4. TalkMessage created (inbound)
5. AI generates response
6. TalkMessage created (outbound)
7. Response sent via Evolution API

### Scenario 2: User sends image with caption
1. Same as text message flow
2. **Additionally:** Image downloaded to disk
3. Attachment entity created and linked
4. Caption extracted to message body

### Scenario 3: Agent sends media reply
1. Use `sendMediaMessage()` with media URL
2. Evolution API sends to WhatsApp
3. TalkMessage updated with delivery status

---

**That's it! Send a WhatsApp message to your configured number and watch it work.**

For detailed Evolution API documentation, visit: https://doc.evolution-api.com/
