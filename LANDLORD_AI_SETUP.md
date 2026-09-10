# Landlord AI Assistant - Complete Setup Guide

## Overview

You now have a **separate AI assistant specifically for the application fee process**. This "Landlord AI" handles questions about:
- Application fee ($70 refundable)
- Payment methods and timeline
- Application status and next steps
- Security deposits and lease terms
- Move-in process and required documents

The Landlord AI appears on the **application-chat.html** page and is completely independent from the main Cooper Fox AI Assistant.

---

## System Architecture

```
Application Form (apply.html)
    ↓
Application Confirmation Page (application-chat.html)
    ├─ Shows application summary
    ├─ Displays application details
    └─ Chat with Landlord button
        ↓
    Landlord Chat Modal (local, instant)
        ├─ Loads landlord-knowledge.json on startup
        ├─ Keyword matching for FAQ responses
        └─ No external AI dependency

Admin Management (admin-landlord-ai.html)
    ├─ Login with admin credentials
    ├─ Edit landlord AI greeting, message, FAQ
    ├─ Save changes to server
    └─ Fetches via admin-landlord-api.php
        ↓
    Server API (admin-landlord-api.php)
        ├─ Session authentication
        ├─ Read/write landlord-knowledge.json
        └─ File locking for safety
```

---

## Files Created / Modified

### New Files
- **data/landlord-knowledge.json** — Landlord-specific FAQ knowledge base
- **admin-landlord-ai.html** — Admin dashboard for landlord AI
- **admin-landlord-api.php** — Server API for landlord knowledge

### Modified Files
- **application-chat.html** — Replaced Chatway with landlord AI modal
- Removed Chatway script entirely
- Added landlord chat modal UI and logic

---

## How It Works

### For Applicants

1. **User fills out application** at `apply.html`
2. **Form submits**, user redirected to `application-chat.html`
3. **Application confirmation page loads**, showing:
   - Application summary (property, location, price)
   - Submitted details (name, email, phone, etc.)
   - "Chat with Landlord" button + quick prompts
4. **User clicks "Chat with Landlord"** or clicks a prompt
5. **Landlord AI modal opens** with welcome message
6. **User types question** (e.g., "How much is the fee?")
7. **AI matches keywords** against FAQ database
8. **AI responds instantly** with relevant answer
9. **Conversation continues** with real-time responses

**Result:** Instant, friendly, helpful guidance through the application fee process.

### For Admin

1. **Visit admin panel:** `admin-landlord-ai.html`
2. **Login** with admin credentials (email + password from mail-config.php)
3. **Edit landlord AI:**
   - Brand name (e.g., "Landlord Assistant")
   - Welcome message (shown when modal opens)
   - Default reply (fallback if no FAQ matches)
   - FAQ answers (keywords + responses)
4. **Click "Save Landlord settings"**
5. **Changes saved to `data/landlord-knowledge.json`**
6. **Applicants instantly see updated knowledge**

---

## Configuration

### Admin Credentials
**File:** `mail-config.php`

```php
$_ENV['ADMIN_EMAIL'] = 'admin@cooperfoxrealty.com';
$_ENV['ADMIN_PASSWORD'] = 'YourSecurePassword';
```

These same credentials work for:
- Main AI Admin (`admin-ai.html`)
- Landlord AI Admin (`admin-landlord-ai.html`)

### Landlord AI Knowledge
**File:** `data/landlord-knowledge.json`

Example structure:
```json
{
  "brandName": "Cooper Fox Landlord Assistant",
  "welcome": "Hello! I'm here to help with your application.",
  "defaultReply": "Thanks for your message. What can I help with?",
  "faqs": [
    {
      "keywords": ["fee", "70", "application fee"],
      "answer": "The application fee is $70 and fully refundable..."
    }
  ]
}
```

**Public read:** Any visitor can fetch this via API
**Admin write:** Only authenticated admins can update

---

## Deployment Steps

### Step 1: Upload Files to Production
Upload these files via cPanel File Manager or FTP:

```
✅ application-chat.html       (updated)
✅ admin-landlord-ai.html      (new)
✅ admin-landlord-api.php      (new)
✅ data/landlord-knowledge.json (new)
```

### Step 2: Set File Permissions
**Via cPanel:**
1. Right-click `data/` folder → Permissions → **755**
2. Right-click `data/landlord-knowledge.json` → Permissions → **644**

**Via SSH:**
```bash
chmod 755 data
chmod 644 data/landlord-knowledge.json
```

### Step 3: Verify Credentials in mail-config.php
Ensure admin credentials are set correctly:
```php
$_ENV['ADMIN_EMAIL'] = 'admin@cooperfoxrealty.com';
$_ENV['ADMIN_PASSWORD'] = 'YOUR_SECURE_PASSWORD';
```

### Step 4: Test the System

**Test 1: Application Flow**
1. Visit `https://yourdomain.com/apply.html`
2. Fill out and submit the application
3. You should be redirected to `application-chat.html`
4. Verify application summary displays
5. Click "Chat with Landlord" button
6. Chat modal should open with welcome message
7. Type "What's the fee?" and verify AI responds

**Test 2: Admin Panel**
1. Visit `https://yourdomain.com/admin-landlord-ai.html`
2. Login with admin@cooperfoxrealty.com and your password
3. Admin panel should load
4. Verify you can see current landlord AI settings
5. Edit one FAQ answer slightly
6. Click "Save Landlord settings"
7. Verify success message appears

**Test 3: Changes Appear in Chat**
1. Go back to application-chat.html
2. Open Landlord chat
3. Ask about the edited FAQ
4. Verify your change appears

---

## FAQ Breakdown

The landlord AI comes with **12 pre-configured FAQs** covering:

| Topic | Keywords | Purpose |
|-------|----------|---------|
| Application Fee | fee, 70, $70, charge | Explain $70 refundable fee |
| Payment Methods | payment, pay, credit card | How to pay the fee |
| Payment Timeline | how long, when, days | Timeline for payment & review |
| Refunds | refund, money back, denied | When/how refunds are issued |
| Next Steps | what happens, approved, status | Steps after application |
| Documents | documents, required, credit report | What documents are needed |
| Lease Terms | lease, agreement, duration | Lease length and details |
| Move-in | move-in date, occupancy, start | Move-in instructions |
| Security Deposit | deposit, how much | Deposit amount and purpose |
| Application Confirmation | submitted, received, confirmation | Confirm application received |
| Contact Info | contact, reach, call, email | How to reach landlord |
| Greeting | hello, hi, hey, help | Welcome & offer assistance |

Each FAQ has **multiple keywords** for flexible matching:
- User: "How much to move in?" 
- Matches: "move in", "move-in date", "occupancy"
- Returns: Move-in FAQ answer

---

## Admin Panel Features

### Landlord Settings
- **Bot name** — What applicants see (e.g., "Landlord Assistant")
- **Welcome message** — Greeting when modal opens
- **Default reply** — Fallback if no FAQ matches

### FAQ Management
- **Edit existing FAQs** — Keywords + answers
- **Keywords** — Comma-separated list (e.g., "rent, price, cost")
- **Answers** — Full text response to user questions
- **Add more FAQs** — Edit JSON directly or via UI

### Save & Reset
- **Save button** — Update all settings + FAQs
- **Reset button** — Restore to default FAQs
- **Status message** — Confirms successful save

---

## Testing Checklist

- [ ] Application form (`apply.html`) submits successfully
- [ ] Redirects to `application-chat.html` after submit
- [ ] Application summary displays correctly
- [ ] "Chat with Landlord" button visible
- [ ] Click button opens chat modal
- [ ] Modal shows landlord welcome message
- [ ] Can type messages in chat
- [ ] AI responds to keyword queries (e.g., "fee", "payment")
- [ ] Can close modal with X button
- [ ] Quick prompt buttons work (suggested questions)
- [ ] Admin login works at `admin-landlord-ai.html`
- [ ] Can edit FAQ and save
- [ ] Changes appear in chat on next message
- [ ] Session persists across page refreshes
- [ ] Logout destroys session
- [ ] Mobile access works (responsive chat modal)

---

## Security Notes

1. **No hardcoded passwords** in frontend (HTML/JS)
2. **Admin credentials** stored in mail-config.php only
3. **Session-based auth** using PHP $_SESSION
4. **File locking** on write to prevent concurrent corruption
5. **JSON validation** before saving
6. **httpOnly cookies** on production (secure against XSS)
7. **Public read** endpoint (`?action=read`) allows public fetch
8. **Admin write** endpoint requires authentication

---

## Troubleshooting

### Chat modal doesn't open
- Check browser console for errors
- Verify `data/landlord-knowledge.json` exists and is readable
- Test API: Visit `admin-landlord-api.php?action=read` in browser
- Should return JSON with landlord knowledge

### AI doesn't respond to messages
- Check that FAQ keywords match user message
- Verify `landlord-knowledge.json` loaded successfully (check console)
- Try very obvious keywords (e.g., type "fee" exactly)
- Check if defaultReply is set (fallback if no match)

### Save doesn't work
- Verify file permissions: `data/` = 755, `data/landlord-knowledge.json` = 644
- Check cPanel error logs for PHP errors
- Verify admin is logged in (check session)
- Test API directly: `admin-landlord-api.php?action=check` should return authenticated

### Changes don't appear
- Refresh `application-chat.html` page
- Check that save was successful (green status message)
- Verify `data/landlord-knowledge.json` file was modified (check timestamp)
- Try closing and reopening chat modal

### Permission errors
- Via cPanel File Manager:
  - Right-click `data/` → Permissions → 755
  - Right-click `data/landlord-knowledge.json` → Permissions → 644
- Via SSH:
  - `chmod 755 data/`
  - `chmod 644 data/landlord-knowledge.json`

### Can't login to admin panel
- Verify credentials in `mail-config.php`
- Email must be lowercase
- Check for trailing spaces in credentials
- Try password reset if available

---

## Production Checklist

Before going live:

- [ ] Update `ADMIN_PASSWORD` in mail-config.php (secure password)
- [ ] Test application flow end-to-end
- [ ] Test admin login and FAQ edit
- [ ] Verify file permissions are correct (755/644)
- [ ] Check cPanel error logs for any issues
- [ ] Test on mobile device
- [ ] Verify admin panel loads quickly
- [ ] Confirm chat responds without delays
- [ ] Test with various question types
- [ ] Verify no JavaScript errors in browser console

---

## What's Different from Main AI?

### Main Cooper Fox AI (index.html)
- **File:** `data/ai-knowledge.json`
- **Admin Panel:** `admin-ai.html` → `admin-ai-api.php`
- **Purpose:** General property inquiries, tours, rentals
- **Appearance:** Main website, anywhere chat button used

### Landlord AI (application-chat.html)
- **File:** `data/landlord-knowledge.json`
- **Admin Panel:** `admin-landlord-ai.html` → `admin-landlord-api.php`
- **Purpose:** Application fee process, payments, next steps
- **Appearance:** Application confirmation page only

**Both use same admin credentials** but manage separate knowledge bases.

---

## Next Steps

1. **Deploy to production** following deployment steps above
2. **Test thoroughly** using testing checklist
3. **Update FAQ knowledge** as needed via admin panel
4. **Monitor applicant feedback** for new FAQ requests
5. **Scale FAQ** over time as patterns emerge

---

## Support

All configuration happens via:
1. **Admin panel:** `admin-landlord-ai.html` (login + edit)
2. **Knowledge file:** `data/landlord-knowledge.json` (data storage)
3. **Server API:** `admin-landlord-api.php` (backend logic)

Everything is self-contained and requires no external vendors or APIs.

---

## Quick Reference

| Item | Value |
|------|-------|
| **Application Form** | `apply.html` |
| **Application Confirmation** | `application-chat.html` |
| **Admin Panel** | `admin-landlord-ai.html` |
| **Admin API** | `admin-landlord-api.php` |
| **Knowledge Base** | `data/landlord-knowledge.json` |
| **Admin Email** | admin@cooperfoxrealty.com |
| **Admin Password** | (set in mail-config.php) |

---

**Status: ✅ READY FOR DEPLOYMENT**

All files in place. Follow deployment steps to go live.
