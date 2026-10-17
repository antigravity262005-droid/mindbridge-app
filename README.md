# MindBridge: A Student Mental Wellness, Awareness, and Support Referral System
> **Theme:** Suicide Prevention and Mental Health Awareness  
> **Course:** Database Systems Concept Proposal & Midterm Project  
> **Environment:** PHP 8, MySQL/MariaDB (XAMPP), HTML5, Vanilla CSS, Tailwind CSS CDN, Chart.js CDN, ES6 Vanilla JS

---

## 1. Project Overview & Scope

**MindBridge** is a secure, empathetic, web-based support navigation platform designed for schools and universities. It centralizes verified mental wellness literature, presents an approved campus and community support services directory, and provides students a confidential avenue to request non-emergency appointments or referrals.

### Crucial Safety & Ethical Scope
- **Non-Emergency Boundary:** MindBridge is strictly an information and referral coordination tool. It is **NOT** a diagnostic system, a crisis hotline, or an emergency dispatch monitor.
- **Persistent Emergency Notice:** Every single page prominently displays safety instructions: for immediate crises, students are directed to call **911**, go to the nearest emergency room, or reach the **Philippine National Center for Mental Health (NCMH) Crisis Hotline at 1553** (`0917-899-8727` / `0966-351-4518`).
- **Strict Privacy Shield:** Student daily wellness check-ins (`wellness_checkins`) are strictly private to the student who wrote them. Administrators and counselors cannot view, query, or audit individual mood ratings or personal reflections.

---

## 2. Relational Database Design (`mindbridge_db`)

The database is normalized to 3NF and implemented in InnoDB with strict foreign key constraints, ON DELETE cascades/sets, and CHECK constraints:

```
+----------------------------------------------------------------------------------------------------+
|                                      MINDBRIDGE DATABASE SCHEMA                                    |
+----------------------------------------------------------------------------------------------------+
| 1. users                   (user_id, full_name, email, password_hash, role, must_change_password) |
| 2. resource_categories     (category_id, category_name, description)                              |
| 3. mental_health_resources (resource_id, category_id*, title, content, source_url, created_by*)      |
| 4. support_services        (service_id, service_name, service_type, description, contact_info...)  |
| 5. support_requests        (request_id, user_id*, service_id*, request_type, subject, status...)   |
| 6. support_request_updates (update_id, request_id*, updated_by*, status, update_note, created_at)  |
| 7. wellness_checkins       (checkin_id, user_id*, mood_rating [1-5], reflection, checkin_date)    |
| 8. awareness_campaigns     (campaign_id, title, description, start_date, end_date, created_by*)    |
| 9. campaign_participants   (campaign_id*, user_id*, joined_at)                                    |
+----------------------------------------------------------------------------------------------------+
* Foreign Key constraint
```

### Table Relationships & Key Constraints
1. **`users`**: Contains role-based accounts (`'user'` or `'administrator'`). Enforces unique email addresses and bcrypt password storage.
2. **`support_requests` & `support_request_updates`**: 1-to-Many audit timeline. When an admin updates request status, a note is logged in `support_request_updates`, preserving a complete audit trail without losing previous notes.
3. **`wellness_checkins`**: Constraint `chk_mood_range CHECK (mood_rating >= 1 AND mood_rating <= 5)`. Queried **only** where `user_id = :authenticated_user_id`.
4. **`awareness_campaigns`**: Constraint `chk_campaign_dates CHECK (end_date >= start_date)`.
5. **`campaign_participants`**: Composite Primary Key `(campaign_id, user_id)` resolving the M:N relationship between students and awareness campaigns.

---

## 3. Demo Accounts & Seed Credentials

The database comes pre-seeded with initial demonstration accounts:

| Role | Name | Email | Password | Initial State |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | MindBridge Administrator | `admin@mindbridge.edu` | `Admin@123` | Mandatory Password Change on First Sign-in |
| **Student** | Alex Rivera | `student@mindbridge.edu` | `Student@123` | Active Student Dashboard |

*Note: You can also register unlimited student accounts using the self-service **"Create Account"** registration page.*

---

## 4. Key Features Implemented

### For Students:
1. **Interactive Educational Library:** Category filtering, real-time keyword search, full article reader modals with verified sources.
2. **Support Directory:** Campus counseling, peer-support, telehealth, and crisis hotline cards with a one-click **"Request Appointment"** button that pre-selects the service.
3. **Confidential Support Requests:** Clear separation between "Appointment" and "Referral" requests, accompanied by a safety advisory warning against submitting emergency situations.
4. **Interactive Request Timeline:** Real-time visibility into counselor status changes (`Submitted` → `In Review` → `Scheduled` → `Completed`) with the admin's latest notes.
5. **Private Mood Tracker & Reflection Log:** 1 to 5 mood scale with animated interactive emojis, personal journaling note, history log, and a dynamic **Chart.js Mood Trend Graph**.
6. **Awareness Campaigns:** Explore upcoming advocacy drives and pledge support with one-click **Join/Leave Campaign** actions.

### For Administrators:
1. **Analytics Dashboard:** Visual status donut chart (Chart.js), real-time counter metrics, and recent inquiries feed.
2. **Support Request Manager:** Filter by status or type, search by student name/subject, and modal to change status and append counselor follow-up notes.
3. **Resource & Category CRUD:** Add, edit, or remove psychoeducational guides and create new topical categories.
4. **Support Directory Management:** Register new on-campus or community clinics, edit contact information, and toggle public visibility.
5. **Campaign Scheduler:** Schedule wellness weeks, validate date bounds (`end_date >= start_date`), and inspect the list of enrolled student participants.

### Full-Stack Engineering & Security:
- **Live Updates Polling (`api/poll.php`):** 3-second authenticated polling with automatic pausing when the browser tab is inactive (`document.visibilityState`) to preserve CPU and bandwidth.
- **CSRF Token Protection:** Enforced on all mutating `POST` actions via `csrf_token()` and `verify_csrf()`.
- **Session Expiration:** Automatic idle timeout after 30 minutes of inactivity.
- **Zero Build Tooling:** Pure standard PHP 8 PDO, vanilla HTML/CSS, Tailwind CDN, and vanilla ES6 modules for maximum ease of evaluation on standard XAMPP stacks.
- **Dark Mode Support:** Smooth transitions with `localStorage` persistence and automatic OS preference detection.

---

## 5. Local Setup Instructions (XAMPP on Windows)

### Step 1: Ensure Apache & MySQL are Running
1. Open the **XAMPP Control Panel**.
2. Click **Start** for both **Apache** and **MySQL**.
3. Verify that Apache is on Port `80` (or `8080`) and MySQL is on Port `3306`.

### Step 2: Deploy Project Files
The project must reside inside the XAMPP `htdocs` directory:
```
C:\xampp\htdocs\mindbridge\
```

### Step 3: Import the Database
Open PowerShell or your command prompt:
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "SOURCE C:\xampp\htdocs\mindbridge\database\mindbridge.sql;"
```
*Alternatively, you can open phpMyAdmin at `http://localhost/phpmyadmin/`, click **Import**, and choose `database/mindbridge.sql`.*

### Step 4: Open MindBridge in Your Web Browser
Navigate to:
```
http://localhost/mindbridge/
```

---

## 6. Midterm Evaluation & Presentation Walkthrough

Follow this 5-minute testing path to verify every specification requirement:

1. **Safety & Landing Page (`/`):**
   - Check the persistent amber safety banner at the top of every page with the NCMH 1553 hotline.
   - Observe the animated suspension bridge hero graphic representing bridging students to care.
2. **Admin Password Policy (`/login.php`):**
   - Sign in as `admin@mindbridge.edu` / `Admin@123`.
   - Notice the automatic redirect to `/change-password.php` due to `must_change_password = 1`.
   - Update the password (e.g., to `Admin@2026`) and observe the session flash notification.
3. **Student Support Flow (`/user/requests.php`):**
   - Sign in as student `student@mindbridge.edu` / `Student@123`.
   - Go to **Support Directory** (`/user/directory.php`) and click **"Request Appointment"** on the Guidance Office.
   - Notice the pre-filled service dropdown and submit an appointment request.
4. **Real-Time Admin Response (`/admin/requests.php`):**
   - In another browser window or tab, open the Admin Requests page.
   - The live counter will sync the new request. Click **"Review & Respond"**.
   - Change status to **"Scheduled"** and enter a note: *"Consultation confirmed for Thursday 2:00 PM in Room 204."*
5. **Student Timeline & Private Mood Log (`/user/checkins.php`):**
   - Return to the student dashboard: the live poller displays the counselor's follow-up note instantly.
   - Go to **Private Check-in**, select a mood (1-5), enter a note, and save.
   - View the updated **Chart.js Mood Trend Graph**.
   - Confirm that the Admin side has **NO** access to student mood check-ins (preserving privacy).
6. **Campaigns & Theme:**
   - Toggle **Dark Mode** via the sun/moon button in the header.
   - Join an awareness campaign and inspect the updated participant counter.
