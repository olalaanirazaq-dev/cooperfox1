# Two AI Systems - Cooper Fox vs Landlord AI

## Quick Comparison

| Feature | Cooper Fox AI | Landlord AI |
|---------|---------------|-------------|
| **Purpose** | General property inquiries | Application fee process |
| **Location** | Main website (index.html) | Application page (application-chat.html) |
| **Appears On** | Home, listings, property pages | After applicant submits form |
| **Knowledge File** | data/ai-knowledge.json | data/landlord-knowledge.json |
| **Admin Panel** | admin-ai.html | admin-landlord-ai.html |
| **Admin API** | admin-ai-api.php | admin-landlord-api.php |
| **FAQ Topics** | Rent, tours, pets, neighborhood | Fees, payments, timeline, deposits |
| **Users** | Site visitors | Applicants only |
| **Button Label** | "Chat with Agent" or "Schedule Tour" | "Chat with Landlord" |

---

## Both Systems Share

✅ **Same admin credentials** (email + password)
✅ **Same session authentication** (PHP $_SESSION)
✅ **Same knowledge base structure** (JSON files)
✅ **Same API pattern** (login/logout/read/write/check)
✅ **Same security** (file locking, validation)
✅ **Same admin experience** (edit FAQ, save, reset)
✅ **Same chat UI** (modal with messages)

---

## Key Differences

### Cooper Fox AI
**What is it?**
- Main site AI assistant
- Available to everyone visiting the site
- Handles general rental questions

**Where is it?**
- Appears on: index.html, apply.html, mortgage.html, etc.
- Button: "Chat with Agent" / "Schedule a Tour"
- Modal opens inline

**What does it know?**
- Rent pricing and details
- Tour scheduling
- Pet policies
- Neighborhood information
- Application process overview
- FAQ matching with broad keywords

**Admin controls:**
- Brand name: "Cooper Fox AI Assistant"
- Welcome: General greeting about rentals
- Default reply: General real estate help
- FAQs: 12 topics (rent, pets, tours, neighborhood, etc.)

---

### Landlord AI
**What is it?**
- Application-specific AI assistant
- Only appears after application submission
- Helps applicants understand the fee and payment

**Where is it?**
- Appears on: application-chat.html only
- Button: "Chat with Landlord"
- Modal opens with application context

**What does it know?**
- $70 refundable application fee details
- Payment methods and timeline
- Refund policies
- Application status and review process
- Lease terms and security deposits
- Move-in instructions
- FAQ matching with fee/payment-specific keywords

**Admin controls:**
- Brand name: "Cooper Fox Landlord Assistant"
- Welcome: Fee process greeting
- Default reply: Fee process help
- FAQs: 12 topics (fee, payment, timeline, deposits, etc.)

---

## User Experience Flow

### Visitor → Cooper Fox AI
```
1. User visits index.html (home page)
2. User sees "Chat with Agent" button
3. User clicks button
4. Cooper Fox AI modal opens
5. User asks about rent, pets, tours, etc.
6. AI responds from ai-knowledge.json FAQ
```

### Applicant → Landlord AI
```
1. User fills out apply.html form
2. User clicks "Submit Application"
3. Redirected to application-chat.html
4. Sees: Application summary + status
5. Sees: "Chat with Landlord" button
6. User clicks button
7. Landlord AI modal opens with application context
8. User asks about fee, payment, next steps
9. AI responds from landlord-knowledge.json FAQ
```

---

## Admin Management

### Both accessible at:
- Email: admin@cooperfoxrealty.com
- Password: (set in mail-config.php)

### Cooper Fox AI Admin
**Visit:** `admin-ai.html`

**Manage:**
- AI brand name for main site
- Greeting for general visitors
- FAQ for rent, tours, pets, neighborhood
- General real estate knowledge

### Landlord AI Admin
**Visit:** `admin-landlord-ai.html`

**Manage:**
- AI brand name for application process
- Greeting for applicants
- FAQ for fees, payments, timeline
- Application-specific knowledge

---

## When to Edit Each AI

### Edit Cooper Fox AI when...
- You want to change tour scheduling message
- You need to update rent pricing information
- You want to improve pet policy messaging
- You need to add neighborhood information
- You want to change the general welcome greeting

### Edit Landlord AI when...
- You want to clarify the $70 fee structure
- You need to update payment methods available
- You want to speed up payment timeline
- You need to explain move-in better
- You want to clarify required documents

---

## They Share Admin Credentials

**Same login works for both:**
```
Email: admin@cooperfoxrealty.com
Password: YourPassword (from mail-config.php)
```

**But they manage different knowledge:**
- admin-ai.html → manages Cooper Fox AI knowledge
- admin-landlord-ai.html → manages Landlord AI knowledge

**And they use different files:**
- admin-ai-api.php → updates data/ai-knowledge.json
- admin-landlord-api.php → updates data/landlord-knowledge.json

---

## Technical Summary

### Architecture
Both AIs use the **same architecture pattern:**

```
HTML File (UI)
    ↓
JavaScript (Logic)
    ↓
fetch() API calls
    ↓
PHP API (Backend)
    ↓
JSON File (Data)
```

### Scalability
This design allows **unlimited separate AIs**:
- Main Site AI (Cooper Fox) ✅
- Application AI (Landlord) ✅
- Property Detail AI (coming?)
- Move-in Instructions AI (coming?)
- etc.

Each would have:
- Separate HTML file
- Separate API endpoint
- Separate knowledge JSON
- Same admin credential system

---

## Files Organized by System

### Cooper Fox AI System
```
admin-ai.html              Admin panel
admin-ai-api.php           Backend API
data/ai-knowledge.json     Knowledge base
index.html                 Uses this knowledge
```

### Landlord AI System
```
admin-landlord-ai.html     Admin panel
admin-landlord-api.php     Backend API
data/landlord-knowledge.json Knowledge base
application-chat.html      Uses this knowledge
```

### Shared
```
mail-config.php            Both use this for credentials
```

---

## Benefits of Separation

**Separate AIs allow:**
✅ **Different personalities** (main vs landlord)
✅ **Different focus** (general vs fee-specific)
✅ **Targeted knowledge** (relevant to each page)
✅ **Independent management** (separate admin panels)
✅ **Easy to scale** (add more AIs without conflict)
✅ **Clear user experience** (right AI in right place)

---

## What Happens if Both Are Used?

If user visits main site, then applies:

1. **User on index.html**
   - Sees Cooper Fox AI
   - Asks about property details
   - Gets response from ai-knowledge.json

2. **Same user applies**
   - Fills out apply.html form
   - Submits application

3. **User on application-chat.html**
   - Now sees Landlord AI
   - Asks about fee payment
   - Gets response from landlord-knowledge.json

Both AIs work independently, no confusion.

---

## Admin Should Know

When editing Landlord AI:
- ✅ Changes apply to application page only
- ✅ Don't affect main site AI
- ✅ Won't confuse general visitors
- ✅ Specific to application fee process

When editing Cooper Fox AI:
- ✅ Changes apply to main site only
- ✅ Don't affect application process
- ✅ General real estate knowledge
- ✅ For any visitor

---

## Future Expansion

This system is designed for easy expansion:

**Current:**
- ✅ Cooper Fox AI (main site)
- ✅ Landlord AI (application)

**Possible Future AIs:**
- Property Detail AI (on each listing)
- Move-in AI (move-in process)
- Lease Agreement AI (lease terms)
- Tenant Portal AI (after move-in)
- etc.

Each would follow the same pattern:
1. Create knowledge JSON file
2. Create HTML admin panel
3. Create PHP API endpoint
4. Integrate into relevant page
5. Done!

---

## Summary

**You now have two AI systems:**

1. **Cooper Fox AI** — For general visitors on main site
2. **Landlord AI** — For applicants on application page

**Both are:**
- ✅ Locally hosted (no external vendors)
- ✅ Controlled by you (via admin panels)
- ✅ Independent (separate knowledge bases)
- ✅ Secure (same credentials, session-based)
- ✅ Scalable (can add more AIs easily)

**Admin accesses both via same login,** but manages separate knowledge bases for each system.

---

## Next Steps

1. **Deploy both systems** to production
2. **Test Cooper Fox AI** on main site
3. **Test Landlord AI** on application page
4. **Update FAQ knowledge** as needed
5. **Monitor user interactions** for improvements
6. **Consider additional AIs** as business grows

All done via simple admin panels—no coding required!
