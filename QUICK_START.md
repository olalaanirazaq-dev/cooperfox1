# 🚀 Quick Start Guide - Cooper Fox AI Admin

## What You Have Now

✅ **Complete server-side authenticated admin system** for managing AI responses  
✅ **All Chatway & Vapi integrations removed** from public website  
✅ **Single branded AI assistant**: Cooper Fox AI Assistant  
✅ **Admin panel accessible from phone** (no website login needed)  
✅ **Persistent knowledge base** (survives server restarts)  

---

## 3-Step Setup

### Step 1: Update Admin Password (Security)
**File:** `mail-config.php`  
**Line:** ~21
```php
// CHANGE THIS:
$_ENV['ADMIN_PASSWORD'] = $_ENV['ADMIN_PASSWORD'] ?? 'CooperFoxAdmin2026!';

// TO THIS (YOUR SECURE PASSWORD):
$_ENV['ADMIN_PASSWORD'] = $_ENV['ADMIN_PASSWORD'] ?? 'YourNewSecurePassword123!';
```

### Step 2: Set File Permissions (Production Only)
**Via cPanel File Manager:**
1. Right-click `data/` folder → Change Permissions → **755**
2. Right-click `data/ai-knowledge.json` → Change Permissions → **644**

**Via SSH:**
```bash
chmod 755 data
chmod 644 data/ai-knowledge.json
```

### Step 3: Upload Files to Production
**Via cPanel File Manager or FTP, upload these files:**
```
✅ admin-ai-api.php       → public_html/
✅ admin-ai.html          → public_html/
✅ data/ai-knowledge.json → public_html/data/
✅ index.html             → public_html/ (already updated)
```

---

## Test It Works

### Local Testing (If You Have XAMPP/WAMP)
1. Start Apache + PHP
2. Navigate to `http://localhost/copper-fox/admin-ai.html`
3. Login: `admin@cooperfoxrealty.com` / `CooperFoxAdmin2026!`
4. Edit a FAQ, click "Save AI settings"
5. Verify `data/ai-knowledge.json` updated
6. Visit `http://localhost/copper-fox/index.html`, test chat

### Production Testing (After Upload)
1. Navigate to `https://cooperfoxrealty.com/admin-ai.html`
2. Login with your NEW admin password
3. Edit a FAQ, click "Save AI settings"
4. Visit `https://cooperfoxrealty.com/index.html`
5. Open chat and verify new FAQ response appears
6. On your phone: visit admin panel, verify session works

---

## How It Works (60-second overview)

**Before:**
- Chatway widget (public)
- Vapi voice assistant (public)
- Custom local assistant (not connected)
- Three separate systems = confusion

**After:**
- One AI: Cooper Fox AI Assistant
- Admin controls it from phone
- Changes appear instantly in public chat
- No external vendors needed

**Flow:**
```
User visits site → clicks "Chat with Agent"
    ↓
Opens local chat modal
    ↓
Message matched against FAQ keywords
    ↓
AI replies with relevant answer
    ↓
Admin can update all replies from phone (admin-ai.html)
    ↓
Changes saved to data/ai-knowledge.json
    ↓
Next user sees updated knowledge
```

---

## Admin Features

### Login
- Email: `admin@cooperfoxrealty.com`
- Password: *(Your new password from mail-config.php)*

### Edit
- **Brand Name**: What users see as AI name
- **Welcome Message**: Greeting when modal opens
- **Default Reply**: Fallback if no FAQ matches
- **FAQ Answers**: Keywords + response pairs

### Example FAQ Edit
```
Keywords: rent, price, cost, how much, monthly
Answer: The monthly rent is $1,200-$2,500 depending on the property. 
        Contact us for specific pricing on your chosen home.
```

When user types "How much is rent?", AI responds with this answer.

---

## Important Files

| File | Purpose | Writable? |
|------|---------|-----------|
| admin-ai-api.php | Backend API server | Read-only (PHP executes) |
| admin-ai.html | Admin dashboard UI | Read-only (browser loads) |
| data/ai-knowledge.json | AI knowledge storage | **YES** (admin saves here) |
| mail-config.php | Admin credentials | **YES** (update password) |
| index.html | Public website | Read-only (already updated) |

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Login fails | Verify password in mail-config.php, check email is lowercase |
| Changes don't save | Check data/ folder permissions (should be 755) |
| Chat doesn't show new FAQ | Refresh index.html, check browser console for errors |
| Permission error | Set data/ to 755, data/ai-knowledge.json to 644 |
| Session keeps logging out | Check PHP session.save_path is writable, disable browser cookie clearing |

**Full troubleshooting:** See ADMIN_AI_SETUP.md

---

## What Changed on Public Site

### Removed
- ❌ Chatway widget script
- ❌ Vapi voice assistant
- ❌ External AI vendor buttons

### Added
- ✅ All chat → local modal
- ✅ All AI responses from data/ai-knowledge.json
- ✅ No external dependencies

### Result
- ✅ Faster chat (no external API)
- ✅ No vendor costs
- ✅ Full control
- ✅ Works offline if needed

---

## FAQ - Frequently Asked Questions

**Q: Do I need to update website code to change AI?**  
A: No! Just login to admin-ai.html and edit FAQ. Changes appear instantly.

**Q: Can admin panel be on a different server?**  
A: It needs to be on same server as index.html (uses same PHP + JSON file).

**Q: Will old Chatway conversations be lost?**  
A: Yes, Chatway is removed. But all FAQ responses now controlled by you.

**Q: Can I add new FAQ entries?**  
A: Not yet via UI (would need to code that). For now, 12 FAQs are available.

**Q: What if the server goes down?**  
A: data/ai-knowledge.json is backed up in version control. Easy to restore.

**Q: How many users can use chat at once?**  
A: Unlimited (it's just keyword matching, no database connections).

**Q: Can I make AI responses dynamic (pull from database)?**  
A: Yes, but would require code changes to aiReply() function in index.html.

---

## Next Actions Checklist

- [ ] Read ADMIN_AI_SETUP.md (full technical guide)
- [ ] Read CONSOLIDATION_NOTES.md (what was changed)
- [ ] Update ADMIN_PASSWORD in mail-config.php
- [ ] Test locally (if possible)
- [ ] Upload files to production
- [ ] Set file permissions (755/644)
- [ ] Login to admin panel on live site
- [ ] Test FAQ edit and save
- [ ] Test public chat sees changes
- [ ] Test on mobile
- [ ] Monitor first week for issues

---

## Support Documentation

Located in your project folder:

1. **ADMIN_AI_SETUP.md** — Complete technical setup guide
   - System architecture
   - API endpoint reference
   - Production deployment steps
   - Troubleshooting with examples

2. **CONSOLIDATION_NOTES.md** — Migration documentation
   - What was removed from each file
   - What was created
   - Benefits of consolidation
   - Cost savings breakdown

3. **FILE_VERIFICATION.md** — Pre-deployment checklist
   - File integrity check
   - Security verification
   - Pre-deployment testing
   - Rollback plan

---

## Questions?

Refer to the appropriate guide above. Most questions are answered in:
- **"How do I...?"** → ADMIN_AI_SETUP.md
- **"What changed?"** → CONSOLIDATION_NOTES.md
- **"Is everything ready?"** → FILE_VERIFICATION.md

---

## You're All Set! 🎉

Everything is ready to deploy. Follow the 3-step setup above and you're live!

**Current Status: ✅ READY FOR PRODUCTION**

Next step: Update password + set permissions + upload to cPanel
