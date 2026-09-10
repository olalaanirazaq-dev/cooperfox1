# Cooper Fox AI Admin - Server-Side Setup Guide

## System Overview

You now have a **complete server-side authenticated admin system** for managing AI responses. All three separate AI systems (Chatway, Vapi, local) have been consolidated into a single, locally-controlled **Cooper Fox AI Assistant**.

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│ Admin Panel (admin-ai.html)                                  │
│ - Login with credentials                                     │
│ - Edit AI branding, welcome message, default reply           │
│ - Edit FAQ answers (with keyword matching)                   │
│ - Save changes securely                                      │
└──────────────────┬──────────────────────────────────────────┘
                   │ fetch() API calls
                   ↓
┌─────────────────────────────────────────────────────────────┐
│ Backend API (admin-ai-api.php)                               │
│ - POST ?action=login (session-based auth)                    │
│ - GET ?action=check (verify session)                         │
│ - GET ?action=read (public: fetch AI knowledge)              │
│ - POST ?action=write (auth required: save AI knowledge)      │
│ - POST ?action=logout (destroy session)                      │
└──────────────────┬──────────────────────────────────────────┘
                   │ reads/writes
                   ↓
┌─────────────────────────────────────────────────────────────┐
│ Persistent Storage (data/ai-knowledge.json)                  │
│ - Brand name, greeting, default reply                        │
│ - 12+ FAQ entries with keyword matching                      │
│ - Survives server restarts, accessible to public chat        │
└─────────────────────────────────────────────────────────────┘
                   ↑ loads on startup
                   │
┌─────────────────────────────────────────────────────────────┐
│ Public Website (index.html)                                  │
│ - Chat button opens local modal                              │
│ - loadAiKnowledge() fetches from data/ai-knowledge.json      │
│ - aiReply() matches keywords against FAQ database            │
│ - No external AI dependency                                  │
└─────────────────────────────────────────────────────────────┘
```

## Files Changed/Created

### New Files
- **admin-ai-api.php** — Server-side authentication + knowledge API
- **admin-ai.html** — Admin dashboard (updated to use API)
- **data/ai-knowledge.json** — Persistent AI knowledge base

### Modified Files
- **admin-ai.html** — Replaced localStorage with server-side session + fetch() API
- **index.html** — Already loads from data/ai-knowledge.json

### Existing Dependencies
- **mail-config.php** — Contains admin credentials (ADMIN_EMAIL, ADMIN_PASSWORD)

## Credentials

All admin features use these credentials from **mail-config.php**:

```
Email: admin@cooperfoxrealty.com
Password: CooperFoxAdmin2026!
```

**⚠️ IMPORTANT**: Before production deployment, change these credentials in `mail-config.php`:

```php
$_ENV['ADMIN_EMAIL'] = $_ENV['ADMIN_EMAIL'] ?? 'admin@cooperfoxrealty.com';
$_ENV['ADMIN_PASSWORD'] = $_ENV['ADMIN_PASSWORD'] ?? 'CHANGE_THIS_PASSWORD';
```

## How It Works

### 1. Admin Login
1. Navigate to `admin-ai.html`
2. Enter email + password
3. Admin-ai.html POSTs to `admin-ai-api.php?action=login`
4. API validates credentials against `mail-config.php`
5. API creates session using PHP `session_start()` + `session_regenerate_id()`
6. Browser receives session cookie (httpOnly, secure on production)
7. Admin panel loads and fetches current AI knowledge from API

### 2. Admin Edits AI Knowledge
1. Admin edits brand name, greeting, FAQ answers in the web form
2. Clicks "Save AI settings" button
3. `storeKnowledge()` POSTs to `admin-ai-api.php?action=write`
4. API validates admin session
5. API writes updated JSON to `data/ai-knowledge.json` (file locking)
6. Response confirms success

### 3. Public Chat Uses Updated Knowledge
1. User visits index.html
2. `loadAiKnowledge()` fetches from `data/ai-knowledge.json`
3. User types message in chat modal
4. `aiReply()` function matches keywords against FAQ
5. Returns appropriate AI response (instant, no external API)

### 4. Admin Session Persistence
- When admin visits admin-ai.html, page checks `admin-ai-api.php?action=check`
- If session exists, admin panel loads automatically
- Session persists across page reloads, browser tabs, devices
- Logout via button calls `admin-ai-api.php?action=logout`, destroys session

## Local Testing (Windows)

### Option 1: Use XAMPP or WAMP
If you have XAMPP/WAMP installed locally:

1. Copy your project to `C:\xampp\htdocs\copper-fox` (XAMPP example)
2. Start Apache + PHP via XAMPP Control Panel
3. Visit `http://localhost/copper-fox/admin-ai.html`
4. Login with admin@cooperfoxrealty.com / CooperFoxAdmin2026!
5. Edit a FAQ, save, verify file updated in `data/ai-knowledge.json`
6. Visit `http://localhost/copper-fox/index.html`, verify chat uses updated knowledge

### Option 2: Use cPanel File Manager (Production Testing)
1. Upload all files to cPanel public_html
2. Visit `https://cooperfoxrealty.com/admin-ai.html`
3. Login with admin credentials
4. Test full admin flow on production server
5. Verify `data/ai-knowledge.json` updated after save

### Option 3: Use VS Code Live Server (Client-Only Testing)
⚠️ **Won't work**: admin-ai.html needs PHP backend. File:// protocol can't access PHP.

## Production Deployment

### Before Upload
1. **Update credentials in mail-config.php**:
   ```php
   $_ENV['ADMIN_EMAIL'] = 'admin@cooperfoxrealty.com';
   $_ENV['ADMIN_PASSWORD'] = 'YOUR_SECURE_PASSWORD';
   ```

2. **Verify data directory permissions** (cPanel):
   - Set `data/` folder to 755 or 750 (readable/writable by PHP)
   - Set `data/ai-knowledge.json` to 644 (readable by PHP)

3. **Test locally** if possible before production

### Upload Steps (cPanel)
1. Use cPanel File Manager or FTP:
   - Upload `admin-ai-api.php` to `public_html/`
   - Upload `admin-ai.html` to `public_html/`
   - Ensure `data/ai-knowledge.json` exists and is writable

2. Verify file permissions:
   - SSH: `chmod 644 data/ai-knowledge.json`
   - cPanel: Right-click → Permissions → 644

3. Test production:
   - Visit `https://cooperfoxrealty.com/admin-ai.html`
   - Login with your admin credentials
   - Edit a FAQ and save
   - Verify `data/ai-knowledge.json` updated
   - Visit `https://cooperfoxrealty.com/index.html`, test chat with new FAQ

4. Mobile testing:
   - On your phone (same network), visit admin-ai.html
   - Session should work cross-device
   - Make changes and verify on desktop

## API Endpoints Reference

All endpoints use JSON for request/response.

### POST ?action=login
**Request:**
```json
{
  "email": "admin@cooperfoxrealty.com",
  "password": "CooperFoxAdmin2026!"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Admin login successful"
}
```

**Response (Failure):**
```json
{
  "success": false,
  "message": "Incorrect email or password"
}
```

### GET ?action=check
**Response (Authenticated):**
```json
{
  "authenticated": true
}
```

**Response (Not Authenticated):**
```json
{
  "authenticated": false
}
```

### GET ?action=read
**Response:**
```json
{
  "success": true,
  "data": {
    "brandName": "Cooper Fox AI Assistant",
    "welcome": "Hello! I'm...",
    "defaultReply": "Thanks for your message...",
    "faqs": [
      {
        "keywords": ["rent", "price", "cost"],
        "answer": "The monthly rent depends..."
      }
    ]
  }
}
```

### POST ?action=write
**Requires:** Valid session (admin authenticated)

**Request:**
```json
{
  "brandName": "Cooper Fox AI Assistant",
  "welcome": "Hello!...",
  "defaultReply": "Thanks for your message...",
  "faqs": [
    {
      "keywords": ["rent", "price"],
      "answer": "The rent is..."
    }
  ]
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "AI knowledge saved",
  "data": { ... }
}
```

**Response (Unauthorized):**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### POST ?action=logout
**Response:**
```json
{
  "success": true,
  "message": "Logged out"
}
```

## Troubleshooting

### Admin login fails with "Incorrect email or password"
- Check credentials in mail-config.php
- Verify email is lowercase (admin-ai-api.php forces lowercase comparison)
- On production, check if mail-config.php was updated

### Save AI settings fails with "Unauthorized"
- Verify you're logged in (check admin-ai-api.php?action=check)
- Session might have expired; log out and log back in
- Check browser cookies (should see PHP session cookie)

### Changes to AI knowledge don't appear in public chat
- Verify data/ai-knowledge.json was updated (check modification time)
- Refresh index.html (might be cached)
- Check browser console for errors in index.html's loadAiKnowledge()

### Permission errors on data/ai-knowledge.json
- On cPanel: Right-click file → Change Permissions → 644
- On SSH: `chmod 644 data/ai-knowledge.json`
- Parent `data/` folder should be at least 755

### Session expires after page refresh
- Check that PHP session.save_path is writable
- On shared hosting, this usually works by default
- Verify session.auto_start is on (usually default)

## Security Notes

1. **Session-based**: Uses PHP $_SESSION with httpOnly cookies on production (secure against XSS)
2. **Credentials in code**: admin credentials stored in mail-config.php (not exposed in HTML)
3. **Authentication check**: All write operations verify `isAdminAuthenticated()` before proceeding
4. **File locking**: writeAiKnowledge() uses flock() to prevent concurrent writes
5. **JSON validation**: Input validated as array before writing to file
6. **No SQL**: All data stored in JSON files, no database exposure

## What Happened to Chatway & Vapi?

- ✅ Chatway widget script removed from index.html, application-chat.html, apply.html, mortgage.html
- ✅ Vapi voice assistant disabled (script kept but non-functional)
- ✅ All public chat routed to local `openLocalChat()` modal
- ✅ All AI responses now come from local Cooper Fox AI Assistant
- ✅ Admin can manage all AI knowledge without external vendor access

## Next Steps

1. **Update credentials** in mail-config.php before production
2. **Test locally** using XAMPP/WAMP or cPanel
3. **Verify file permissions** on server (data/ folder writable by PHP)
4. **Deploy admin-ai-api.php** to production
5. **Update admin-ai.html** if you made any custom styling
6. **Monitor logs** after deployment for any errors

## Support

If you encounter issues:
1. Check error logs in cPanel (public_html/../error_log)
2. Open browser console (F12) for JavaScript errors
3. Verify admin-ai-api.php responds with JSON (test in browser: visit `?action=read`)
4. Check file permissions: `data/ai-knowledge.json` should be 644, `data/` should be 755
