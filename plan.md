# GullakPe — Admin Panel & Business Logic Blueprint

> Developer documentation. Language is Hinglish (as originally written) — meaning preserved, structure cleaned up.
> Reference any **Section number** below when telling me how a feature should behave.

**Table of contents**
- [Part 1 — Create Plan System (Sections 1–13)](#part-1--create-plan-system)
- [Part 2 — Portfolio + Profit + Wallet Engines (Sections 14–25)](#part-2--portfolio--profit--wallet-engines)
- [Part 3 — Admin Dashboard, Referral, Analytics, Users, Security (Sections 26–42)](#part-3--admin-dashboard-referral-analytics-users--security)
- [Final Business Rules (VERY IMPORTANT)](#final-business-rules-very-important)
- [GullakPe Master Flow](#gullakpe-master-flow)

---

# Part 1 — Create Plan System

### Step 1 — Select Plan Type (first thing the admin does)

Admin sabse pehle **sirf Plan Type** select karega. Baaki form uske baad hi dynamically khulega.

- ⬜ **Fixed Investment Plan**
- ⬜ **Flexible Investment Plan**

---

## OPTION 1 — Fixed Investment Plan

### Section 1 — Basic Information

| Field | Notes |
|---|---|
| Plan Name * | required |
| Short Title | |
| Subtitle | |
| Category | |
| Plan Icon (manual upload) * | Shows on **Plan Card + Plan Details** |
| Plan Thumbnail (manual upload) * | Used as the **Plan Details** image |

### Section 2 — Fixed Investment

- Investment Amount: a single **fixed amount** (e.g. ₹99).

**Rules**
- Sirf ek fixed amount.
- No Minimum / Maximum field.
- No slider.
- User sirf isi amount ko purchase kar sakta hai.

### Section 3 — Interest Rate

- Quick presets: `1% · 2% · 3% · 5% · 8% · 10% · 12% · 15% · 20%`
- Or **Custom %** → admin types the interest % manually.

### Section 4 — Investment Duration *(COMMON — same for Fixed & Flexible)*

**Duration Type**
- ⬜ **Trust Builder (1 Day Only)**
- ⬜ **Multiple Duration Options**

**If Trust Builder selected → automatically apply:**
- Duration = **1 Day**
- Auto Wallet Credit = **ON**
- Withdrawal enabled after 1 Day
- No other duration options.

**If Multiple Duration selected:**
- Admin can create multiple duration options, e.g. `1 Month · 3 Months · 6 Months · 1 Year · 2 Years · Custom`.
- **If Custom:** Duration Value `__` + Unit (`Days / Months / Years`).

**User flow (website):**
1. User pehle **Duration select** karega (e.g. 1 Month / 3 Months / 6 Months / 1 Year).
2. Uske baad investment section open hoga.

### Section 5 — Profit Calculation *(fully automatic)*

Admin kuch manually enter nahi karega. System auto-calculates:

- Investment → Interest % → Duration → **Daily Profit → Total Profit → Total Return** (all auto).

### Section 6 — Withdrawal Rules *(COMMON — same for Fixed & Flexible)*

**Withdrawal Type**
- ⬜ **Trust Builder Plan**
- ⬜ **Normal Investment Plan**

**Trust Builder logic:**
> User purchases → plan runs 1 day → plan completes → **ONLY profit amount** auto-credited to wallet → user can withdraw profit.
> **Investment amount is never returned.**
> Example: Investment ₹200, Profit ₹400 → Wallet credit ₹400 (₹200 not returned).

**Normal Investment Plan logic:**
> User chooses e.g. 3 Months → plan starts → daily profit updates → portfolio shows running profit → **withdrawal locked** → plan ends → **ONLY total profit** auto-credited to wallet → user can withdraw profit.
> **Investment amount is never returned.**

**Portfolio behaviour**
- **Running:** `Dream Bike Plan · Running · Day 42 / 90 · Today's Profit · Accumulated Profit · Withdrawal LOCKED`
- **Completed:** `Completed · Profit Credited ₹850 · Withdrawal Enabled`

### Section 7 — Withdrawal Limits *(Global Settings)*

- Daily Withdrawal Limit
- Maximum Requests Per Day: **3**
- Minimum Withdrawal: **₹300**
- Processing Type: **Instant** / **Manual Approval**

### Section 8 — Unlock System

- Enable Unlock Rule: **YES / NO**
- If **YES**: choose **Required Plan** (dropdown) + an **Unlock Message**.
  - Example message: *"Complete Premium Plan ₹399 first. Then this plan will unlock."*

**Business logic:**
> User clicks Buy → Required plan purchased? → **NO:** show popup "Purchase required plan first" → **YES:** current plan unlocks, Buy button enabled.

Ye rule Fixed aur Flexible dono me same rahega.

### Section 9 — Wallet Balance Check (before purchase)

> Wallet balance enough? → **YES:** continue. **NO:** show "Add Money" popup: *"Insufficient wallet balance. Please add money first."* → Add Money.

### Section 10 — Plan Availability

Statuses: **Draft · Active · Hidden · Expired · Out Of Stock**

- Purchase limit finish hone par → automatically **Out Of Stock**.
- **Website behaviour:** plan visible, Buy button disabled, "Out Of Stock" badge shown.

### Section 11 — Purchase Limit

- **Unlimited** OR **Limited** purchases.
- Example: Maximum Purchases = 100 → after `100 → 99 → 98 → … → 0` → automatically **Out Of Stock**.

### Section 12 — Marketing Badge

- Options: `Most Popular · Trending · Hot · Recommended · Limited Time`

### Section 13 — Live Preview

- Real-time preview: same card, same UI, same calculator as the live site.

---

## OPTION 2 — Flexible Investment Plan

Saare sections Fixed jaise hi rahenge. **Sirf the Investment Section is different.**

### Investment Range (replaces Section 2)

- Minimum Investment (e.g. ₹100)
- Maximum Investment (e.g. ₹1,000)
- Slider Step (e.g. ₹100)

**User flow:**
> Choose Duration → Choose Investment Amount (slider) → Auto Profit Calculator → Buy Plan.
> Example: Duration 3 Months, Slider ₹100–₹1,000, Selected ₹500 → Daily Profit ₹6.50 → Total Profit ₹585 → Total Return ₹1,085. Everything auto.

---

## Common Rules (apply to BOTH Fixed & Flexible)

- ✅ Manual Plan Icon upload
- ✅ Manual Thumbnail upload
- ✅ Manual Explore Icon upload
- ✅ Wallet Balance check
- ✅ Unlock System
- ✅ Multiple Duration Options
- ✅ Auto Profit Calculation
- ✅ Daily Portfolio Updates
- ✅ Withdrawal Locked Until Maturity
- ✅ Profit Auto-Credit
- ✅ **Investment Never Returned**
- ✅ Purchase Limit / Out Of Stock logic
- ✅ Live Preview
- ✅ Marketing Badge

**Final "Create Plan" flow:**
> Create Plan → Select Plan Type → Fixed/Flexible Form → Basic Info → Investment Setup → Duration Setup → Interest Setup → Auto Profit Calc → Withdrawal Rules → Unlock Rules → Purchase Limits → Marketing → Live Preview → Publish → User Purchases → Portfolio Updates Daily → Plan Matures → **ONLY Profit Credited to Wallet** → User Withdraws Profit.

---

# Part 2 — Portfolio + Profit + Wallet Engines

### Section 14 — Portfolio Engine

Plan purchase hote hi automatically **Portfolio create** hona chahiye.

> User purchases plan → payment success → Portfolio created → Status = **Running**.

**Portfolio stores:** Plan Name · Plan ID · Investment Amount · Selected Duration · Interest Rate · Start Date · Maturity Date · Today's Profit · Total Profit · Status.

**Portfolio Status:** `Running · Completed · Matured · Cancelled · Expired`

**Portfolio Progress (running plans):**
- Auto-show progress: `Day 25 / 90 · Progress 27%` — updates every day.

**Today's Profit** — auto-updates every day. Example: Investment ₹500, Interest 12%, 3 Months → Today ₹6.50, Tomorrow ₹13.00, Next day ₹19.50. User manually change nahi kar sakta.

**Total Profit** — auto-updates (Day 1 → Day 30 → Day 90…).

**Portfolio History (per portfolio):** Purchase Date · Investment · Duration · Daily Profit History · Maturity Date · Profit Credit Date · Withdrawal Status.

### Section 15 — Daily Profit Engine

**Schedule:** every day at **2:00 AM**.

> Running plans → calculate today's profit → update portfolio → update total profit.

**Daily Profit Formula:** `Investment × Interest % × Duration → Daily Profit` (auto; admin never enters manually).

**Conditions:**
- **Trust Builder:** 1 Day → only one calculation.
- **Normal Plans:** every day until maturity.

### Section 16 — Auto Maturity Engine

System checks daily: `Current Date ≥ Maturity Date → Completed?`

- **If YES:** Plan Completed → Wallet Credit → Portfolio Status = **Completed**.

### Section 17 — Wallet Credit Logic ⚠️ VERY IMPORTANT

- Wallet me **ONLY Profit** jayega. **Never Investment.**
- Example: Investment ₹500, Total Profit ₹820 → **Wallet Credit ₹820**, Investment ₹500 never returned.
- Applies to: **Fixed · Flexible · Trust Builder — all plans.**
- Auto-create a **Wallet Transaction Entry:** `Credit · Profit Credit ₹820 · Date · Reference ID`.

### Section 18 — Wallet Engine

**Supports:** Add Money · Profit Credit · Referral Bonus · Cashback · Manual Credit · Withdrawal Debit.

**Balance Formula:**
`Opening Balance + Profit Credit + Referral + Cashback − Withdrawal = Available Balance`

### Section 19 — Withdrawal Engine

User can withdraw **ONLY Wallet Balance** — never running profit.

- Example: Portfolio running, today's profit ₹20 → Wallet withdrawal **NOT allowed**.
- After maturity: Wallet ₹820 → withdrawal **allowed**.

**Withdrawal validation:** Wallet Balance → Minimum Withdrawal → Daily Limit → Request Limit → if all valid, create withdrawal request.

**Withdrawal Status:** `Pending · Approved · Rejected · Processing · Completed`

### Section 20 — Trust Builder Logic

Trust Builder is a special plan.

- Rules: Duration 1 Day → Auto Mature → Profit Credit → Wallet → Withdrawal Enabled. **Investment never returned.**
- Unlock rule: if locked → buy required plan → Trust Builder becomes available automatically.

### Section 21 — Purchase Validation

Before purchase, system checks: Wallet Balance · Plan Status · Purchase Limit · Unlock Rule · Duration Selected · Investment Valid.
- If all valid → purchase. Otherwise → show error.

### Section 22 — Purchase Flow

> Open Plan → Choose Duration → Choose Amount → Profit Calculator → Buy → Wallet Check → Purchase Success → Portfolio Created → Running.

### Section 23 — Portfolio Screen Behaviour

- **Portfolio Card (running):** `Dream Bike · Running · Day 24 / 90 · Today's Profit · Total Profit ₹320 · Withdrawal LOCKED`
- **After maturity:** `Completed · Profit Credited ₹820 · Wallet Updated · Withdrawal Enabled`

### Section 24 — System Automation (every night)

Automatically run: Profit Engine → Portfolio Update → Progress Update → Maturity Check → Wallet Credit → Notification. **No manual work.**

### Section 25 — Notifications

Auto-send: Plan Purchased · Today's Profit Updated · Plan Matured · Profit Credited · Withdrawal Successful.

**Final Engine Flow:**
> Plan Purchase → Portfolio Created → Daily Profit Engine → Portfolio Updates Daily → Maturity Check → **ONLY Profit** → Wallet Credit → Withdrawal Enabled → Transaction Created → Notification Sent.

---

# Part 3 — Admin Dashboard, Referral, Analytics, Users & Security

### Section 26 — Admin Dashboard

Dashboard open hote hi complete overview dikhe:

- Total Users · Active Users · Today's New Users
- Total Plans · Running Plans · Completed Plans · Expired Plans
- Total Investments · Today's Investments
- Total Profit Paid · Today's Withdrawals · Pending Withdrawals
- Wallet Balance · Referral Bonus Paid · Cashback Paid

**Dashboard Charts:** Daily Investment · Monthly Investment · User Growth · Profit Distribution · Withdrawal.

### Section 27 — Plan Analytics

Each plan has analytics: Plan Name · Total Views · Total Clicks · Total Purchases · Conversion Rate · Total Investment · Running Users · Completed Users · Revenue Generated.
- Example: `Dream Bike · Views 5,000 · Purchases 320 · Conversion 6.4% · Revenue …`

### Section 28 — User Management

**User List:** User ID · Name · Mobile · Wallet Balance · Active Plans · Completed Plans · Referral Code · Status.

**User Actions:** View Profile · Edit User · Block/Unblock User · Reset MPIN · View Portfolio · View Transactions · Wallet History.

**Search:** by Name / Mobile / User ID. **Filters:** Active · Blocked · New Users · VIP · Referral Users.

### Section 29 — Wallet Management

**Controls:** Credit Wallet · Debit Wallet · Adjust Balance · Wallet History · Transaction Search.
- Every manual change **requires a reason**. Example: `Manual Credit ₹500 · Reason: Promotion Bonus`.

### Section 30 — Transaction Management

**Transaction List:** Transaction ID · User · Amount · Type · Status · Date.

**Types:** Add Money · Plan Purchase · Profit Credit · Referral Bonus · Cashback · Withdrawal · Manual Credit · Manual Debit.
**Status:** Pending · Success · Failed · Cancelled.

### Section 31 — Referral Engine

- **Global:** Referral Enabled YES / NO.
- **Rewards:** Referrer Bonus · Friend Bonus.
- **Rules:** Minimum Purchase Required · Eligible Plans · Maximum Referral Reward.
- **Dashboard:** Total Referrals · Successful Referrals · Pending Rewards · Paid Rewards.

### Section 32 — Cashback Engine

- Cashback Enabled YES / NO.
- **Type:** Fixed Cashback · Percentage Cashback.
- **Conditions:** Eligible Plans · Minimum Purchase · Maximum Cashback · Validity.

### Section 33 — Notification Engine

- Admin can send: Push · In-App · Banner notifications.
- **Templates:** Plan Purchased · Profit Credited · Withdrawal Approved · Referral Reward · Offer · Maintenance.

### Section 34 — Banner Management

- Admin uploads: Home Banner · Offer Banner · Explore Banner · Popup Banner.
- Each banner: Image · Redirect Link · Status · Priority · Start Date · End Date.

### Section 35 — App Settings (General)

App Name · Logo · Primary Color · Support Email · Support Mobile · Website URL · Privacy Policy · Terms & Conditions.

### Section 36 — Withdrawal Management

- **Requests:** User · Amount · Bank/UPI · Status · Date.
- **Actions:** Approve · Reject · View Details.

### Section 37 — Reports

- Generate: Daily · Weekly · Monthly · Yearly reports.
- Export: PDF · Excel · CSV.

### Section 38 — Security

- **Admin Security:** Two-Factor Authentication · IP Restriction · Login History · Device History · Failed Login Attempts.
- **Audit Logs** — every admin action (Create/Edit/Delete Plan · Wallet Credit · Withdrawal Approval · Settings Change) stores: Admin Name · Time · IP Address · Action.

### Section 39 — Role Management

- **Roles:** Super Admin · Manager · Support · Finance · Marketing.
- **Permissions example:** Create Plan YES · Delete Plan NO · Approve Withdrawal YES · Wallet Credit NO.

### Section 40 — Backup System

- Database Backup · Download Backup · Restore Backup.
- Schedule: Daily · Weekly · Monthly.

### Section 41 — System Settings

- Maintenance Mode: YES / NO
- Allow New Registration: YES / NO
- Allow New Investment: YES / NO
- Allow Withdrawals: YES / NO

### Section 42 — Admin Workflow

> Create Plan → Preview → Save Draft → Publish → Users Purchase → Portfolio Created → Daily Profit Updates → Maturity Check → **ONLY Profit Credited** → Wallet Updated → Withdrawal Enabled → Plan Analytics Updated → Reports Generated.

---

## Final Business Rules (VERY IMPORTANT)

1. ✅ Investment Amount is **NEVER** returned.
2. ✅ **ONLY Profit** is credited to Wallet after maturity.
3. ✅ Trust Builder Plan is always **1 Day**.
4. ✅ Trust Builder can be locked using the Unlock Rule.
5. ✅ All other plans support **multiple duration options**.
6. ✅ Duration options are **user-selectable before purchase**.
7. ✅ Profit calculation is always **automatic**.
8. ✅ Daily Profit updates automatically.
9. ✅ Portfolio updates automatically.
10. ✅ Wallet updates automatically.
11. ✅ Withdrawal is only allowed **after** Profit Credit.
12. ✅ Fixed and Flexible plans share the same business rules **except the investment method**.
13. ✅ Manual Plan Icon / Thumbnail / Explore Icon upload.
14. ✅ Wallet Balance check before purchase.
15. ✅ Purchase Limit + Out Of Stock support.
16. ✅ Live Preview before Publish.

---

## GullakPe Master Flow

> Admin Login → Create Plan → Select Plan Type → Configure Plan → Publish → User Opens Plan → Select Duration → Select Investment → Profit Calculator → Wallet Check → Purchase → Portfolio Created → Daily Profit Engine → Portfolio Updates → Maturity Engine → **ONLY Profit Credited to Wallet** → Withdrawal Request → Admin Approval (if required) → Money Sent → Reports & Analytics Updated.
