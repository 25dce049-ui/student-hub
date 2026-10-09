# Practical 9: Secure User Registration with Database Insert, Duplicate Email Check, and Password Hashing

## 📋 Problem Definition

Implement secure user registration by inserting user details into MySQL using **MySQLi**. Validate input on frontend and backend, check duplicate email/username, hash passwords using `password_hash()`, and display appropriate messages.

---

## 🎯 Learning Outcomes

- Implement secure user registration following basic web security practices
- Use **password_hash()** for secure password storage
- Perform duplicate email/username validation
- Implement consistent frontend and backend validation
- Use MySQLi prepared statements to prevent SQL injection
- Implement CSRF token protection (Advanced)
- Add audit logging for registration events (Advanced)

---

## 🔑 Key Questions Addressed

✅ **Is `password_hash()` used before storing passwords?**  
Yes. Passwords are hashed using `password_hash($password, PASSWORD_BCRYPT)` with automatic salting before being stored in the database. Plain-text passwords are never stored.

✅ **Is duplicate email/username checked?**  
Yes. Both duplicate checks are performed:
- **PHP Layer**: Pre-check via prepared `SELECT` statement before `INSERT`
- **Database Layer**: `UNIQUE KEY` constraints on both `email` and `username` columns as a safety net

✅ **Are frontend and backend validations consistent?**  
Yes. Both frontend (JavaScript) and backend (PHP) validate:
- Required fields
- Minimum/maximum lengths
- Email format (regex + `filter_var()`)
- Username pattern (alphanumeric + underscore only)
- Password strength (minimum 8 characters)
- Password confirmation match

---

## 🗂️ File Structure

```
practical9/
├── includes/
│   ├── db.php           # MySQLi connection singleton
│   └── csrf.php         # CSRF token generation & validation
├── js/
│   └── validate.js      # Frontend validation, password strength meter
├── sql/
│   └── schema.sql       # Database schema (users + audit_log tables)
├── index.php            # Registration form with CSRF token
├── register.php         # POST handler (validation, duplicate check, insert)
├── success.php          # Registration success page
├── users.php            # View all registered users + audit log
├── style.css            # Styling (consistent with Practical 8)
└── README.md            # This file
```

---

## 🚀 Setup Instructions

### Prerequisites

- **XAMPP** (Apache + MySQL) installed and running
- PHP 8.0 or higher
- Modern web browser

### Step 1: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** and **MySQL** modules

### Step 2: Create Database

1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Click **New** to create a database
3. Database name: `practical9_db`
4. Charset: `utf8mb4_unicode_ci`
5. Click **Create**

### Step 3: Import Schema

1. Select the `practical9_db` database
2. Click **SQL** tab
3. Open `sql/schema.sql` from the practical9 folder
4. Copy the entire content and paste into the SQL editor
5. Click **Go** to execute

**This creates:**
- `users` table with columns: `id`, `username`, `email`, `full_name`, `password_hash`, `created_at`, `updated_at`
- `audit_log` table for tracking registration attempts
- Unique constraints on `username` and `email`

### Step 4: Access the Application

Open in browser: `http://localhost/student%20analysis/practical9/index.php`

---

## 🧪 Testing Instructions

### Test Case 1: Valid Registration ✅

**Input:**
- Full Name: `Ishal Patel`
- Username: `ishal99`
- Email: `ishal@example.com`
- Password: `SecurePass123!`
- Confirm Password: `SecurePass123!`

**Expected Result:**
- ✅ Registration successful
- User redirected to `success.php`
- User details displayed (without password)
- Password stored as bcrypt hash (60 chars) in database
- Audit log entry: `REGISTER_SUCCESS`

---

### Test Case 2: Duplicate Email ❌

**Steps:**
1. Register a user with email `test@example.com`
2. Try to register another user with the same email

**Expected Result:**
- ❌ Error: "This email address is already registered. Please use a different email."
- Form repopulated with entered values (except password)
- Audit log entry: `REGISTER_FAIL_DUPLICATE`

---

### Test Case 3: Duplicate Username ❌

**Steps:**
1. Register a user with username `johndoe`
2. Try to register another user with the same username

**Expected Result:**
- ❌ Error: "This username is already taken. Please choose another username."
- Form repopulated with entered values (except password)
- Audit log entry: `REGISTER_FAIL_DUPLICATE`

---

### Test Case 4: Password Mismatch ❌

**Input:**
- Password: `MyPassword123`
- Confirm Password: `MyPassword456`

**Expected Result:**
- ❌ Frontend validation shows error before submission
- If submitted anyway, backend shows: "Passwords do not match."

---

### Test Case 5: Invalid Email Format ❌

**Input:**
- Email: `notanemail`

**Expected Result:**
- ❌ Frontend validation error: "Please enter a valid email address."
- Backend validation catches it if JS is disabled

---

### Test Case 6: Short Password ❌

**Input:**
- Password: `pass`

**Expected Result:**
- ❌ Frontend: "Password must be at least 8 characters."
- Password strength meter shows "Weak" in red

---

### Test Case 7: Invalid Username Characters ❌

**Input:**
- Username: `user@name!`

**Expected Result:**
- ❌ Frontend: "Only letters, numbers, and underscores allowed."
- Backend validates with regex: `/^[a-zA-Z0-9_]+$/`

---

### Test Case 8: CSRF Token Validation ❌

**Steps:**
1. Open form and inspect CSRF token
2. Manually submit form with modified/missing token

**Expected Result:**
- ❌ Error: "Invalid or expired form token. Please try again."
- Registration blocked before any database operation

---

## 🔐 Security Features Implemented

### ✅ Basic Requirements

| Feature | Implementation | Location |
|---------|---------------|----------|
| **Password Hashing** | `password_hash($password, PASSWORD_BCRYPT)` | `register.php:107` |
| **Duplicate Email Check** | Prepared `SELECT` + `UNIQUE KEY` | `register.php:88-97` |
| **Duplicate Username Check** | Prepared `SELECT` + `UNIQUE KEY` | `register.php:88-97` |
| **Frontend Validation** | Real-time JS validation with error messages | `js/validate.js` |
| **Backend Validation** | PHP validation matching frontend rules | `register.php:47-74` |
| **MySQLi Prepared Statements** | All queries use `bind_param()` | `register.php`, `users.php` |

### ✅ Intermediate Extension

| Feature | Implementation | Location |
|---------|---------------|----------|
| **Prepared Statements** | All queries use bound parameters | All `.php` files with DB queries |

### ✅ Advanced Extensions

| Feature | Implementation | Location |
|---------|---------------|----------|
| **CSRF Token Protection** | 32-byte random token, `hash_equals()` validation | `includes/csrf.php`, `register.php:29-33` |
| **Audit Log** | Records all registration attempts with IP & user agent | `register.php:152-164`, `sql/schema.sql` |
| **Password Strength Meter** | Visual strength indicator (Weak/Fair/Good/Strong) | `js/validate.js:64-84` |
| **Toggle Password Visibility** | Eye icon to show/hide password | `js/validate.js:103-113` |
| **Session Flash Data** | Form repopulation after validation failure | `index.php:15-17`, `register.php:76` |

---

## 🛡️ Security Best Practices Applied

1. **Password Security**
   - ✅ `password_hash()` with `PASSWORD_BCRYPT` (cost factor: 10)
   - ✅ Automatic random salt per password
   - ✅ 60-character hash stored in VARCHAR(255)
   - ✅ Passwords never stored in plain text
   - ✅ Passwords never logged or displayed

2. **SQL Injection Prevention**
   - ✅ MySQLi prepared statements with `bind_param()`
   - ✅ No raw string interpolation in queries
   - ✅ `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)`

3. **XSS Prevention**
   - ✅ `htmlspecialchars()` on all output
   - ✅ User input never rendered directly in HTML

4. **CSRF Prevention**
   - ✅ Token generated with `random_bytes(32)`
   - ✅ Token validated with `hash_equals()` (timing-attack safe)
   - ✅ One-time token (regenerated after use)

5. **Input Validation**
   - ✅ Whitelist validation (allowed characters for username)
   - ✅ Length limits enforced (frontend + backend + database)
   - ✅ Email format validation (`filter_var(FILTER_VALIDATE_EMAIL)`)
   - ✅ `trim()` to remove whitespace
   - ✅ `mb_strlen()` for multibyte-safe length checks

6. **Database Security**
   - ✅ `UNIQUE` constraints prevent race conditions
   - ✅ UTF-8 charset (`utf8mb4`) for emoji/international support
   - ✅ Error messages don't leak sensitive details
   - ✅ Password hash column never exposed in SELECT

7. **Session Security**
   - ✅ Flash data pattern (consume-once for messages)
   - ✅ Sensitive data cleared after display
   - ✅ Session started securely

8. **Error Handling**
   - ✅ Generic error messages to users
   - ✅ Detailed errors logged server-side with `error_log()`
   - ✅ Try-catch blocks for database exceptions

---

## 📊 Database Schema

### `users` Table

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT |
| `username` | VARCHAR(50) | NOT NULL, UNIQUE |
| `email` | VARCHAR(100) | NOT NULL, UNIQUE |
| `full_name` | VARCHAR(100) | NOT NULL |
| `password_hash` | VARCHAR(255) | NOT NULL |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE |

### `audit_log` Table

| Column | Type | Constraints |
|--------|------|-------------|
| `log_id` | INT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT |
| `action` | VARCHAR(50) | NOT NULL (e.g. 'REGISTER_SUCCESS') |
| `username` | VARCHAR(50) | NULL |
| `email` | VARCHAR(100) | NULL |
| `ip_address` | VARCHAR(45) | NULL (supports IPv6) |
| `user_agent` | VARCHAR(255) | NULL |
| `logged_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

---

## 🎨 Frontend Features

1. **Real-time Validation**
   - Field-by-field validation on `blur` and `input` events
   - Clear error messages below each field
   - Visual feedback (red/green border colors)

2. **Password Strength Meter**
   - Visual progress bar
   - Strength labels: Weak / Fair / Good / Strong
   - Color-coded: Red → Orange → Blue → Green
   - Scoring based on length, uppercase, numbers, symbols

3. **Toggle Password Visibility**
   - Eye icon button next to password fields
   - Switches between `type="password"` and `type="text"`
   - Accessible with `aria-label`

4. **Accessibility (WCAG)**
   - ✅ Skip link for keyboard navigation
   - ✅ ARIA labels on all form controls
   - ✅ `aria-describedby` linking errors to fields
   - ✅ `aria-live="polite"` for dynamic error messages
   - ✅ `role="alert"` for validation errors
   - ✅ Semantic HTML (`<label>`, `<fieldset>`, `<main>`)

---

## 🧩 CO Mapping

- **CO1**: Understand web application security fundamentals
- **CO3**: Implement secure database operations with prepared statements
- **CO5**: Apply password hashing and duplicate validation in real-world scenarios

---

## 🕐 Time Allocation

- **Implementation**: 4 hours
- **Testing & Documentation**: 2 hours
- **Total Engagement**: 6 hours

---

## 📝 Post Laboratory Work

Submit a **Registration Test Report** including:

1. **Valid Test Cases** (at least 3)
   - Screenshot of successful registration
   - Database screenshot showing hashed password

2. **Invalid Test Cases** (at least 5)
   - Duplicate email
   - Duplicate username
   - Password mismatch
   - Invalid email format
   - Short password

3. **Security Verification**
   - Confirm password is hashed (60-char bcrypt string)
   - Confirm CSRF token is validated
   - Confirm prepared statements are used

4. **Audit Log**
   - Screenshot of audit_log table showing success/failure entries

---

## 🛠️ Tools & Technologies

- **Backend**: PHP 8.0+
- **Database**: MySQL (via MySQLi)
- **Server**: XAMPP (Apache + MySQL)
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Security**: bcrypt, CSRF tokens, prepared statements

---

## 📚 References

- [PHP password_hash() Documentation](https://www.php.net/manual/en/function.password-hash.php)
- [MySQLi Prepared Statements](https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php)
- [OWASP Input Validation](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html)
- [CSRF Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)

---

## 🐛 Troubleshooting

### Database Connection Failed
- Ensure XAMPP MySQL is running
- Verify database name is `practical9_db`
- Check credentials in `includes/db.php` (default: root with no password)

### CSRF Token Error
- Clear browser cookies/session
- Refresh the registration page to generate a new token

### Duplicate Key Error After Validation
- This is a rare race condition
- The app handles it gracefully and shows a user-friendly message

### JavaScript Not Working
- Ensure `js/validate.js` is loaded correctly
- Check browser console for errors
- Frontend validation is optional; backend validation always runs

---

## 👨‍💻 Author

**Ishal Patel**  
Student Analysis Project – Practical 9  
StudentHub – Secure User Registration System

---

## 📄 License

Educational project for academic purposes.

---

**Last Updated**: October 9, 2026  
**Version**: 1.0
