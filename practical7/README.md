# Practical 7 - PHP Form Processing with Server-Side Validation and CSV/JSON Storage

**Course Outcome:** CO1, CO5
**Tools:** PHP 8.2, XAMPP/WAMP/LAMP, VS Code, browser developer tools

## Problem

Process a submitted registration form with PHP, validate and sanitise every
input on the server, store the record in CSV and JSON files, and show clear
success/error messages.

## File structure

```
practical7/
├── index.php                  Registration form (GET) + CSRF token
├── process.php                POST handler: validate -> sanitise -> store -> message
├── records.php                Intermediate extension: renders stored CSV/JSON rows
├── config.php                 Paths, field rules, allowed values
├── includes/
│   ├── bootstrap.php          Loads config + helpers, e() escape helper
│   ├── Validator.php          Sanitisation + server-side validation
│   ├── Storage.php            Safe CSV / JSON file writing
│   └── Csrf.php               Advanced extension: CSRF token generation + check
├── css/style.css              Styling for form, alerts and table
├── data/
│   ├── registrations.csv      Generated output (one record per row)
│   ├── registrations.json     Generated output (JSON array)
│   └── .htaccess              Denies direct web access to the data files
└── tests/
    ├── run_tests.php          33 automated assertions
    └── generate_sample_data.php
```

## Key questions answered

1. **Is the form submitted using POST?**
   Yes. `<form method="post" action="process.php">`. `process.php` rejects any
   non-POST request with `405 Method Not Allowed`.
2. **Are inputs validated and sanitised server side?**
   Yes, in `Validator`. Sanitisation (`sanitizeText`, `sanitizeMultiline`,
   `sanitizeEmail`) strips tags, control characters and null bytes, trims and
   collapses whitespace. Validation then enforces required fields, length
   limits, email format, phone pattern, a Unicode-safe name pattern, and
   whitelist checks for course/year. Duplicate emails are rejected too.
3. **Is file writing handled safely?**
   Yes. `data/` is created with an `.htaccess` deny rule, CSV is opened in
   append mode, the JSON file is written to a `.tmp` file with `LOCK_EX` and
   then atomically renamed. The header row is written only when the file is
   new, and IDs auto-increment. `fputcsv()` handles commas and quotes safely.
4. **Are success and error responses displayed clearly?**
   Yes. A green success alert on success; on failure a red alert, a bulleted
   summary of problems, per-field messages, and the invalid input is kept in
   the form so nothing has to be retyped.

## Extensions implemented

- **Intermediate** - `records.php` reads the CSV/JSON back and renders a
  searchable table with a toggle between the two formats.
- **Advanced** - `includes/Csrf.php` issues a 64-character random token per
  session, embeds it as a hidden field, and compares it with `hash_equals()`.
  The token is rotated after use so it cannot be replayed.

## Setup and run (XAMPP)

1. Copy the `practical7` folder into `C:\xampp\htdocs\`.
2. Start Apache in the XAMPP Control Panel.
3. Visit <http://localhost/practical7/index.php>.

## Setup and run (PHP built-in server)

```bash
cd practical7
php -S localhost:8000
```

## Running the tests

```bash
php tests/run_tests.php          # 33 assertions
php tests/generate_sample_data.php
```

## Test data used

| Test | Input | Expected |
| --- | --- | --- |
| Valid submission | Aisha Khan / aisha.khan@example.com / 9876543210 / BCA / 2 | success, record written |
| Empty required field | `full_name = ""` | "Full name is required." |
| Short name | `full_name = "Al"` | min length error |
| Illegal characters | `full_name = "Bad <b>Name</b>!"` | pattern error |
| Tag injection | `full_name = "Bad<script>Name"` | tags stripped to `BadName` |
| Bad email | `email = "not-an-email"` | "Enter a valid email address." |
| Bad phone | `phone = "abc"` | phone format error |
| Tampered select | `course = "HACKED"` | "Select a valid course." |
| Tampered select | `year = "9"` | "Select a valid year of study." |
| Short optional | `message = "short"` | min length error |
| Long optional | 501-character message | max length error |
| Duplicate email | re-submit an existing email | duplicate error, nothing written |
| CSV special chars | name with a comma and quotes | round-trips correctly |
| CSRF | missing / wrong / reused token | rejected |
| Non-POST | GET request to `process.php` | HTTP 405 |

## Viva questions

- Why sanitise *and* validate? Sanitisation makes data safe to store and
  display; validation checks that it is correct and allowed.
- Why write JSON to a temp file and rename? `rename()` on the same filesystem
  is atomic, so a reader never sees a half-written file.
- Why use `LOCK_EX`? Prevents two simultaneous submissions from interleaving.
- Why rotate the CSRF token after use? A token can then only be used once,
  blocking replay attacks.
- Why `e()` on every echo? Prevents stored XSS when records are redisplayed.
- Why whitelist course and year? An attacker can post any value; a whitelist
  only accepts what the form is supposed to offer.
