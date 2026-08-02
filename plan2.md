GullakPe Admin Panel – Pending Work & Required Updates (Developer Documentation v2.0)
SECTION 1 – Basic Information
Pending
❌ Add Manual Plan Icon Upload field.
Rules:
• Plan Icon should be uploaded manually.
• Plan Icon must be displayed on: 
• Plan Card
• Plan Details
• Portfolio Card
• Thumbnail Upload remains unchanged.
• Explore Icon Upload remains unchanged.
SECTION 2 – Fixed Investment Plan
Pending Improvements
Developer must ensure:
• Fixed Investment Amount field is visible only for Fixed Plans.
• Min/Max Investment fields must remain hidden.
• Slider must never appear.
• User can purchase only the configured fixed amount.
Current Status: 🟡 Need Dynamic Visibility Verification.
SECTION 3 – Flexible Investment Plan
Pending Improvements
Developer must ensure:
Visible only when Flexible Plan is selected.
Should include:
• Minimum Investment
• Maximum Investment
• Slider Step
• Live Amount Preview
• Dynamic Profit Calculator
Current Status: 🟡 Admin fields exist but full flexible workflow is incomplete.
SECTION 4 – Investment Duration (COMMON)
Missing Features
Add Duration Type Selector.
Duration Type
○ Trust Builder (1 Day)
○ Multiple Duration
If Trust Builder selected
Automatically:
• Duration = 1 Day
• Auto Wallet Credit = ON
• Withdrawal Enabled after 1 Day
• Hide Multiple Duration Options
If Multiple Duration selected
Admin should be able to add:
☑ 1 Month
☑ 3 Months
☑ 6 Months
☑ 1 Year
☑ 2 Years
☑ Custom
Custom:
Duration Value
Unit:
• Days
• Months
• Years
Current Status: ❌ Missing.
SECTION 5 – Auto Profit Engine
Pending
Developer must implement automatic calculation.
Admin should never enter:
• Daily Profit
• Total Profit
• Total Return
System calculates automatically using:
• Investment
• Interest
• Duration
Current Status: 🟡 UI available but backend logic not verified.
SECTION 6 – Withdrawal Rules (COMMON)
Missing
Add Withdrawal Rule section.
Withdrawal Type
○ Trust Builder Plan
○ Normal Investment Plan
Trust Builder Logic
Purchase
↓
Run 1 Day
↓
Plan Complete
↓
ONLY Profit Credited
↓
Withdrawal Enabled
Important Rule
Investment Amount MUST NEVER return.
Wallet receives ONLY Profit.
Example
Investment ₹200
Profit ₹400
Wallet Credit ₹400
Investment Returned ❌ NO
Normal Investment Logic
Purchase
↓
Running
↓
Daily Profit Update
↓
Portfolio Update
↓
Withdrawal Locked
↓
Maturity
↓
ONLY Profit Credit
↓
Withdrawal Enabled
Current Status: ❌ Missing.
SECTION 7 – Withdrawal Limits
Add Global Withdrawal Settings.
Daily Withdrawal Limit
Maximum Requests Per Day
Minimum Withdrawal Amount
Processing Type
Instant
Manual Approval
Current Status: ❌ Missing.
SECTION 8 – Unlock System
UI exists.
Backend Business Logic still required.
Workflow
Buy Click
↓
Required Plan Purchased?
↓
NO
↓
Show Unlock Popup
↓
Buy Required Plan
↓
Unlock Current Plan
↓
Enable Buy Button
Current Status: 🟡 Backend logic pending.
SECTION 9 – Wallet Balance Validation
Missing
Before every purchase system must check:
Wallet Balance
↓
Enough?
YES
↓
Continue Purchase
NO
↓
Show Add Money Popup
Popup Text
Insufficient Wallet Balance.
Please add money first.
Current Status: ❌ Missing.
SECTION 10 – Plan Availability
Current UI only contains Active.
Developer must add:
Draft
Hidden
Expired
Out Of Stock
Automatic Rule
Purchase Limit Reached
↓
Automatically Out Of Stock
↓
Buy Button Disabled
Current Status: ❌ Missing.
SECTION 11 – Purchase Limit
Need verification.
System should automatically:
100
↓
99
↓
50
↓
10
↓
0
↓
Out Of Stock
Current Status: 🟡 Needs backend implementation.
SECTION 12 – Marketing
Current UI available.
Need verification:
Most Popular
Trending
Hot
Recommended
Limited Time
Status: 🟢 Mostly Completed.
SECTION 13 – Live Preview
Missing
Admin must see:
Real-time Plan Card Preview
Real-time Profit Preview
Real-time Calculator Preview
Current Status: ❌ Missing.
SECTION 14 – Dynamic Fixed/Flexible Form
Developer must ensure:
If Fixed selected
Hide:
• Min Investment
• Max Investment
• Slider
• Flexible-only fields
If Flexible selected
Hide:
• Fixed Investment Amount
Show:
• Min Investment
• Max Investment
• Slider Settings
Current Status: 🟡 Needs verification.
SECTION 15 – Trust Builder Business Logic
Still Missing
Rules
• Always 1 Day
• Auto Mature
• Profit Credit Only
• Investment Never Returned
• Withdrawal Enabled after Maturity
• Supports Unlock Rule
Current Status: ❌ Missing.
SECTION 16 – Backend Engines (Not Verified)
Developer must complete and demonstrate:
❌ Portfolio Engine
❌ Daily Profit Engine
❌ Profit Scheduler (12 AM)
❌ Auto Maturity Engine
❌ Wallet Credit Engine
❌ Wallet Transaction Engine
❌ Withdrawal Engine
❌ Purchase Validation
❌ Purchase Flow
❌ Notification Engine
❌ Progress Engine
❌ Portfolio History
SECTION 17 – Portfolio Logic
Must implement:
Running Plan
Progress
Today's Profit
Accumulated Profit
Remaining Days
Completed Status
Profit Credited
Withdrawal Enabled
Current Status: ❌ Not Verified.
SECTION 18 – Wallet Logic
Important Rule
Wallet must receive ONLY Profit.
Investment Amount must NEVER return.
Apply to:
• Fixed Plans
• Flexible Plans
• Trust Builder Plans
Current Status: ❌ Backend verification required.
SECTION 19 – Notifications
Missing automation:
Plan Purchased
Today's Profit Updated
Plan Matured
Profit Credited
Withdrawal Success
Current Status: ❌ Missing.
SECTION 20 – Final Business Rule Verification
Developer must verify all business rules:
✔ Investment Never Returned
✔ Profit Only Credit
✔ Auto Daily Profit
✔ Auto Portfolio Update
✔ Auto Wallet Credit
✔ Auto Maturity
✔ Unlock Logic
✔ Purchase Validation
✔ Dynamic Duration
✔ Flexible Slider
✔ Purchase Limits
✔ Out Of Stock
✔ Wallet Validation
✔ Live Preview
✔ Complete Portfolio Flow
Overall Status
UI Completion
🟢 ~75% Complete
Admin Form
🟢 ~80% Complete
Business Logic
🟡 ~35% Complete
Backend Automation
🔴 Not Verified / Pending
Overall Project Completion
🟡 Approximately 60–65% Complete
Ye feature bahut important hai. Isko Withdrawal Management Module ke andar add karna chahiye. Main isko professional format me update kar raha hoon.



SECTION 7 – Withdrawal Management (UPDATED)
Withdrawal Method


Admin can enable/disable available withdrawal methods.
Available Withdrawal Methods ☑ Bank Account ☑ UPI ☑ USDT (TRC20) 
OPTION 1 – Bank Account Withdrawal
User can withdraw directly to a bank account.
Required Fields
Account Holder Name * Bank Account Number * Confirm Account Number * Bank Name * IFSC Code * Branch Name (Optional) 
Validation
• Account Number and Confirm Account Number must match.
• IFSC Code validation required.
• Bank Account Number must be numeric only.
• Account Holder Name should match KYC (if KYC enabled).
OPTION 2 – UPI Withdrawal
User can withdraw using UPI.
Required Fields
UPI ID * Example: name@okaxis name@ybl name@ibl 
Optional Fields
UPI Mobile Number (Optional) UPI QR Code (Optional Upload) 
Validation
• UPI ID format validation.
• QR upload optional.
• Mobile number optional.
OPTION 3 – USDT Withdrawal (TRC20)
User can withdraw in USDT.
Required Fields
Network TRC20 Wallet Address * Example Txxxxxxxxxxxxxxxxxxxxxxxx 
Optional
Wallet QR Code (Optional Upload) 
Validation
• Only TRC20 network supported.
• Wallet address format validation.
• QR upload optional.
Withdrawal Request Flow
User Opens Wallet ↓ Click Withdraw ↓ Choose Withdrawal Method ○ Bank Account ○ UPI ○ USDT (TRC20) ↓ Enter Details ↓ Enter Withdrawal Amount ↓ Submit Request ↓ Validation ↓ Request Created ↓ Admin Review (If Manual) ↓ Approved ↓ Payment Sent ↓ Status Updated 
Withdrawal Status
Pending Processing Approved Completed Rejected Cancelled 
Withdrawal Validation
System must check:
✔ Wallet Balance Available ✔ Minimum Withdrawal Amount ✔ Maximum Daily Withdrawal Limit ✔ Maximum Daily Requests ✔ Selected Withdrawal Method ✔ Required Details Filled ✔ Profit Already Credited 
Important Business Rules
✔ User can withdraw ONLY Wallet Balance. ✔ Running Plan Profit cannot be withdrawn. ✔ Investment Amount is NEVER withdrawable. ✔ ONLY Matured Profit can be withdrawn. ✔ Bank, UPI and USDT methods can be enabled/disabled by Admin. ✔ Admin can approve or reject withdrawal requests. ✔ Every withdrawal creates a transaction record. 
⭐ Additional Recommendation (Professional)
Developer ko ek aur section add karna chahiye:
Withdrawal Method Settings (Admin)
Bank Withdrawal Enable / Disable UPI Withdrawal Enable / Disable USDT (TRC20) Enable / Disable Minimum Withdrawal Amount ₹300 Maximum Per Transaction ₹5,000 Maximum Daily Withdrawal ₹10,000 Daily Request Limit 3 Processing Mode ○ Automatic ○ Manual Approval 
Ye feature future me bahut useful hoga, kyunki agar kisi time Bank ya UPI service maintenance me ho, to Admin sirf toggle off karke us method ko temporarily disable kar sakta hai, bina code change kiye.