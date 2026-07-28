GullakPe Admin Panel Create Plan System
(Developer Documentation
STEP 1 - Select Plan Type
Admin sabse pehle sirf Plan Type select karega.
Choose Plan Type o Fixed Investment Plan o Flexible Investment Plan
Iske baad hi remaining form dynamically open hoga.
OPTION 1 - FIXED INVESTMENT PLAN
SECTION 1 - Basic
Information
Plan Name * Short Title Subtitle Category
Plan Icon (Manual Upload)
* Plan Thumbnail (Manual Upload)
Rules
 	Plan Icon -+ Plan Card + Plan Details me show hoga.
 	Plan Thumbnail Plan Details image ke Iiye.
SECTION 2 - Fixed Investment
Investment Amount 99
Rules
 	Sirf ek Fixed Amount.
 	Minimum/Maximum field nahi.
 	Slider nahi.
 	User sirf isi amount ko purchase kar sakta hai.
SECTION 3 - Interest Rate
Interest Rate 1% 2% 3% 5% 8% 10% 12%
1 5% 20% Custom %
Custom select hone par
Interest %	 
SECTION 4 - Investment
Duration (COMMON)
Ye section Fixed aur Flexible dono me same rahega.
Duration Type
o Trust Builder (1 Day Only) o Multiple Duration Options
If Trust Builder Selected
Automatically Apply
Duration 1 Day Auto Wallet Credit ON Withdrawal Enabled after 1 Day No other duration options.
If Multiple Duration Selected
Admin multiple duration options create kar sakta hai.
Example
Available Durations 1 Month @ 3
Months 6 Months 1 	2 Years  
Custom
If Custom
Duration Value __ Unit Days Months
Years
User Flow
Website par user ko pehle duration select karna hoga.
Choose Duration 1 1 Month 3 Months 6 Months 1 Year
Uske baad investment section open hoga.
SECTION 5 - Profit Calculation
Admin kuch manually enter nahi karega.
System automatically calculate karega. Investment 1 Interest % 1 Duration 1 Daily Profit 1 Total Profit 1 Total Return Sab Auto.
SECTION 6 - Withdrawal
Rules (COMMON)
Ye section Fixed aur Flexible dono me same rahega.
Withdrawal Type
o Trust Builder Plan o Normal Investment plan
Trust Builder Logic
User Purchase 1 Plan Runs 1 Day 1 Plan
Complete 1 ONLY Profit Amount Auto Credit to Wallet 1 User Can Withdraw Profit Important
Investment Amount Never Returned Only
Profit Amount Credited to Wallet
Example
Investment ?200 Profit ?400 Wallet Credit
?400 Investment ?200 Not Returned
Normal Investment Plan Logic
Example
User Chooses 3 Months 1 Plan Starts 1
Daily Profit Updates 1 Portfolio Shows
Running Profit 1 Withdrawal Locked 1 Plan
Ends 1 ONLY Total Profit Auto Credit to
Wallet 1 User Can Withdraw Profit
Important
Investment Amount Never Returned Only
Profit Credited to Wallet
Portfolio Behaviour
Running
Dream Bike Plan Running Day 42 / 90
Today's Profit 2 Accumulated Profit  Withdrawal LOCKED
Completed
Completed Profit Credited ?850
Withdrawal Enabled
SECTION 7 - Withdrawal
Limits
(Global Settings)
Daily Withdrawal Limit 	Maximum
Requests Per Day 3 Minimum Withdrawal
?300 Processing Type Instant Manual
Approval
SECTION 8 - Unlock System
Enable Unlock Rule YES NO
If YES
Required Plan Dropdown Unlock Message
Example
Complete Premium Plan ?399 First Then this plan will unlock.
Business Logic
User Clicks Buy 1 Required Plan
Purchased? NO 1 Show Popup J Purchase
Required Plan First 1 YES 1 Current Plan
Unlock 1 Buy Button Enabled
Ye rule Fixed aur Flexible dono me same rahega.
SECTION 9 - Wallet Balance
Check
Before Purchase
Wallet Balance 1 Enough Balance? YES 1 Continue NO 1 Add Money Popup popup
Insufficient Wallet Balance Please add money first. Add Money
SECTION 10 - Plan Availability
Draft Active Hidden Expired Out Of Stock
Purchase Limit finish hone par
Automatically
Out Of Stock
Website Behaviour
Plan Visible 1 Buy Button Disabled 1 Out Of
Stock Badge
SECTION 1 1 - Purchase Limit
Unlimited OR Limited Purchases Example
Maximum Purchases 100
After
100 99 1 98 1 0
Automatically
Out Of Stock
SECTION 12 - Marketing
Most Popular Trending Hot Recommended
Limited Time
SECTION 13 - Live Preview
Real-time Preview.
Same Card.
Same UI.
Same Calculator.
OPTION 2 - FLEXIBLE
INVESTMENT PLAN
Sab sections Fixed jaise hi rahenge.
Sirf Investment Section alag hoga.
Investment Range
Minimum Investment 00 Maximum
Investment 000 Slider Step ? 1 00
User Flow
Choose Duration 1 Choose Investment Amount 1 Auto Profit Calculator 1 Buy Plan Example
Duration 3 Months 1 Slider 00 — 000 1
Selected ?500 1 Daily Profit ?6.50 1 Total
Profit ?585 1 Total Return ?1085
Everything Auto.
COMMON RULES (Apply to BOTH)
s/ Manual Plan Icon Upload s/ Manual Thumbnail Upload s/ Manual Explore Icon
Upload s/ Wallet Balance Check s/ Unlock
System s/ Multiple Duration Options s/
Auto Profit Calculation s/ Daily Portfolio
Updates s/ Withdrawal Locked Until
Maturity s/ Profit Auto Credit 8/
Investment Never Returned s/ Purchase Limit Out Of Stock Logic 8/ Live Preview s/ Marketing Badge
FINAL BUSINESS FLOW
Create Plan 1 Select Plan Type 1 Fixed /
Flexible Form 1 Basic Information 1
Investment Setup 1 Duration Setup 1
Interest Setup 1 Auto Profit Calculation 1
Withdrawal Rules 1 Unlock Rules 1 Purchase Limits 1 Marketing 1 Live
Preview 1 Publish 1 User Purchases 1
Portfolio Updates Daily 1 Plan Matures 1
ONLY Profit Credited to Wallet 1 User
Withdraws Profit
GullakPe Admin Panel Create Plan System
(Developer Documentation
Portfolio Engine + Profit Engine + Wallet Engine
SECTION 14 - Portfolio Engine
Plan purchase hote hi automatically Portfolio create hona chahiye.
User Purchases Plan 1 Payment Success 1
Portfolio Created 1 Status Running
Portfolio me ye details store hongi.
Plan Name Plan ID Investment Amount
Selected Duration Interest Rate Start Date
Maturity Date Today's Profit Total Profit Status
Portfolio Status
Portfolio Status
Running Completed Matured Cancelled Expired
Portfolio Progress
Running Plan
Automatically show
Progress Day 25 / 90 Progress 27% Automatically update every day.
Today's Profit
Automatically update every day.
Example
Investment ?500 Interest 12% Duration 3
Months Today's Profit ?6.50 Tomorrow
?13.00 Next Day ?19.50
User manually change nahi kar sakta.
Total Profit
Automatically update.
Day 1 	Day 	Day 30  1 Day
90 
Portfolio History
Every Portfolio should store
Purchase Date Investment Duration Daily
Profit History Maturity Date Profit Credit
Date Withdrawal Status
SECTION 15 - Daily Profit Engine
Daily Profit automatically generate hona chahiye. Schedule
Every Day 1 2:00 AM
System
Running Plans 1 Calculate Today's Profit 1
Update Portfolio 1 Update Total Profit
Daily Profit Formula
Investment x Interest % x Duration 1 Daily profit
Automatically calculate.
Admin manually enter nahi karega.
Daily Profit Conditions
Trust Builder
1 Day 1 Only one calculation
Normal Plans
Every Day Until Maturity
SECTION 16 - Auto Maturity
Engine
System should check daily.
Current Date 1 Maturity Date 1 Completed?
If YES
Automatically
Plan Completed 1 Wallet Credit 1 Portfolio
Status Completed
SECTION 17 - Wallet Credit
Logic
VERY IMPORTANT
Wallet me ONLY Profit jayega.
Never Investment.
Business Logic
Investment ?500 1 Total Profit ?820 1 Wallet Credit ?820 Investment ?500 Never
Returned
Apply on
Fixed Flexible Trust Builder All Plans
Wallet Transaction Entry
Automatically create
Credit Profit Credit ?820 Date Reference ID SECTION 18 - Wallet Engine
Wallet should support
Add Money Profit Credit Referral Bonus Cashback Manual Credit Withdrawal Debit
Wallet Balance Formula
Opening Balance + Profit Credit + Referral
+ Cashback - Withdrawal = Available
Balance
SECTION 19 - Withdrawal
Engine
User can withdraw ONLY Wallet Balance.
Never Running Profit.
Example
Portfolio Running Today's Profit 20
Wallet Withdrawal NOT Allowed
After Maturity
Wallet ?820 Withdrawal Allowed
Withdrawal Validation
Check
Wallet Balance 1 Minimum Withdrawal 1
Daily Limit 1 Request Limit If all valid
Create Withdrawal Request.
Withdrawal Status
Pending Approved Rejected Processing
Completed
SECTION 20 - Trust Builder
Logic
Trust Builder Special Plan.
Rules
Duration 1 Day 1 Auto Mature 1 Profit
Credit 1 Wallet 1 Withdrawal Enabled
Investment
Never Returned.
Unlock Rule
If Locked Buy Required Plan 1 Unlock 1 Trust Builder Available
Automatically.
SECTION 21 - Purchase
Validation
Before Purchase
System checks
Wallet Balance 1 Plan Status 1 Purchase
Limit 1 Unlock Rule 1 Duration Selected 1
Investment Valid
If all valid
Purchase.
Otherwise
Show Error.
SECTION 22 - Purchase
Flow
Open Plan 1 Choose Duration 1 Choose
Amount 1 Profit Calculator 1 Buy 1 Wallet Check 1 Purchase Success 1 Portfolio
Created 1 Running
SECTION 23 - Portfolio
Screen Behaviour
Portfolio Card
Dream Bike Running Day 24 / 90 Today's
Profit Total Profit ?320 Withdrawal
LOCKED
After Maturity
Completed Profit Credited ?820 Wallet
Updated Withdrawal Enabled
SECTION 24 - System
Automation
Every Night
Automatically run
Profit Engine 1 Portfolio Update 1 Progress
Update 1 Maturity Check 1 Wallet Credit 1
Notification
No manual work.
SECTION 25 - Notifications
Automatically send
Plan Purchased 1 Today's Profit Updated 1
Plan Matured 1 Profit Credited 1
Withdrawal Successful
FINAL ENGINE FLOW
Plan Purchase 1 Portfolio Created 1 Daily
Profit Engine 1 Portfolio Updates Daily 1
Maturity Check 1 ONLY Profit 1 Wallet
Credit 1 Withdrawal Enabled 1 Transaction
Created 1 Notification Sent
Perfect. Yeh Final Part-3 hai. Iske baad
GullakPe ka Create Plan + Admin Panel + Business Logic ka complete blueprint ready ho jayega.
GullakPe Admin Panel -
Developer Documentation Admin Dashboard + Referral
+ Analytics + User
Management + Security
SECTION 26 - Admin
Dashboard
Dashboard open hote hi Admin ko complete overview dikhe.
Total Users Active Users Today's New Users Total Plans Running Plans
Completed Plans Expired Plans Total
Investments Today's Investments Total
Profit Paid Today's Withdrawals Pending Withdrawals Wallet Balance Referral
Bonus Paid Cashback Paid
Dashboard Charts
Show
Daily Investment Chart Monthly
Investment Chart User Growth Chart Profit
Distribution Chart Withdrawal Chart
SECTION 27 - Plan
Analytics
Each Plan should have analytics.
Plan Name Total Views Total Clicks Total
Purchases Conversion Rate Total
Investment Running Users Completed
Users Revenue Generated
Plan Performance
Example
Dream Bike Views 5,000 Purchases 320
Conversion 6.4% Revenue 
SECTION 28 - User
Management
Admin should manage users.
User List
User ID Name Mobile Wallet Balance Active Plans Completed Plans Referral
Code Status
User Actions
View Profile Edit User Block User Unblock
User Reset MPIN View Portfolio View
Transactions Wallet History
User Search
Search by
Name Mobile Number User ID
Filters
Active Blocked New Users VIP Referral
Users
SECTION 29 - Wallet
Management
Admin Wallet Controls
Credit Wallet Debit Wallet Adjust Balance
Wallet History Transaction Search Every manual change should require a reason. Example
Manual Credit ?500 Reason Promotion
Bonus
SECTION 30 - Transaction
Management
Transaction List
Transaction ID User Amount Type Status
Date
Transaction Types
Add Money Plan Purchase Profit Credit
Referral Bonus Cashback Withdrawal
Manual Credit Manual Debit Transaction Status
Pending Success Failed Cancelled
SECTION 31 - Referral
Engine
Global Settings
Referral Enabled YES / NO
Reward Settings
Referrer Bonus 00 Friend Bonus 
Rules
Minimum Purchase Required Eligible Plans
Maximum Referral Reward
Referral Dashboard
Show
Total Referrals Successful Referrals
Pending Rewards Paid Rewards SECTION 32 - Cashback Engine
Admin can create Cashback Rules.
Cashback Enabled YES / NO
Cashback Type
Fixed Cashback Percentage Cashback
Example
Purchase ?500 Cashback 
Cashback Conditions
Eligible Plans Minimum Purchase
Maximum Cashback Validity
SECTION 33 - Notification Engine
Admin can send
Push Notification In-App Notification
Banner Notification
Templates
Plan Purchased Profit Credited Withdrawal
Approved Referral Reward Offer
Maintenance
SECTION 34 - Banner
Management
Admin can upload
Home Banner Offer Banner Explore Banner
Popup Banner
Each banner
Image Redirect Link Status Priority Start
Date End Date
SECTION 35 - APP Settings
General Settings
App Name Logo Primary Color Support
Email Support Mobile Website URL Privacy
Policy Terms & Conditions
SECTION 36 - Withdrawal
Management
Withdrawal Requests
User Amount Bank UPI Status Date
Actions
Approve Reject View Details
SECTION 37 - Reports
Generate Reports
Daily Report Weekly Report Monthly
Report Yearly Report
Export
PDF Excel CSV
SECTION 38 - Security
Admin Security
Two Factor Authentication IP Restriction Login History Device History Failed Login
Attempts
Audit Logs
Every Admin Action
Create Plan Edit Plan Delete Plan Wallet
Credit Withdrawal Approval Settings
Change
Should store
Admin Name Time IP Address Action
SECTION 39 - Role
Management
Admin Roles
Super Admin Manager Support Finance
Marketing
Permissions
Example
Create Plan YES Delete Plan NO Approve
Withdrawal YES Wallet Credit NO
SECTION 40 - Backup System
Admin should have
Database Backup Download Backup
Restore Backup
Schedule
Daily Weekly Monthly
SECTION 41 - System
Settings
Maintenance
Maintenance Mode YES / NO
Registration
Allow New Registration YES / NO
Investment
Allow New Investment YES / NO
Withdrawal
Allow Withdrawals YES / NO
SECTION 42 - Admin
Workflow
Complete Flow
Create Plan 1 Preview 1 Save Draft 1
Publish 1 Users Purchase 1 Portfolio
Created 1 Daily Profit Updates 1 Maturity
Check 1 ONLY Profit Credited 1 Wallet Updated 1 Withdrawal Enabled 1 Plan
Analytics Updated 1 Reports Generated
FINAL BUSINESS RULES (VERY IMPORTANT)
s/ Investment Amount is NEVER returned. s/ ONLY Profit Amount is credited to Wallet after maturity. s/ Trust Builder Plan is always 1 Day. s/ Trust Builder can be locked using Unlock Rule. s/ All other plans support multiple duration options. s/ Duration options are selectable by user before purchase. s/ Profit calculation is always automatic. s/ Daily Profit updates automatically. s/ Portfolio updates automatically. s/ Wallet updates automatically. s/ Withdrawal is only allowed after Profit Credit. s/ Fixed and Flexible plans share the same business rules except investment method. 8/ Manual Plan Icon Upload. s/ Manual
Thumbnail Upload. s/ Manual Explore Icon
Upload. s/ Wallet Balance Check before
Purchase. s/ Purchase Limit Support. s/ Out of Stock Support. 8/ Live Preview before Publish.
GullakPe Master Flow
Admin Login 1 Create Plan 1 Select Plan
Type 1 Configure Plan 1 Publish 1 User Opens Plan 1 Select Duration 1 Select
Investment 1 Profit Calculator 1 Wallet
Check 1 Purchase 1 Portfolio Created 1
Daily Profit Engine 1 Portfolio Updates 1 Maturity Engine 1 ONLY Profit Credited to
Wallet 1 Withdrawal Request 1 Admin
Approval (if required) 1 Money Sent 1
Reports & Analytics Updated
