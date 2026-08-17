# StudentHub – Web Technology Lab Practicals

A complete static web portal built across four practicals covering
semantic HTML5, responsive CSS, JavaScript interactivity, and form validation.

---

## Folder Structure

```
student analysis/
├── practical2/   – Semantic HTML5 pages (11 pages + accessibility checklist)
├── practical3/   – Responsive UI with CSS Grid, Flexbox & Bootstrap 5
├── practical4/   – JavaScript DOM Manipulation & UI Interactivity
└── practical5/   – Registration Form with Validation & Canvas CAPTCHA
```

---

## Practical 2 – Semantic HTML5 Pages with Accessibility

**Pages:** Home, About, Register, Login, Dashboard, Events, Profile, Contact, Admin, FAQ, Feedback + Accessibility Checklist

**Key Features:**
- Semantic tags: `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>`
- ARIA landmarks, `aria-current`, `aria-required`, `aria-label`
- Skip-to-content link on every page
- Breadcrumb navigation (Intermediate Extension)
- WCAG 2.1 AA accessibility checklist

**Open:** `practical2/index.html`

---

## Practical 3 – Responsive UI Design

**Pages:** Home, About, Register, Dashboard, Events, Contact, Feedback, Login

**Key Features:**
- CSS Grid (auto-fit, named template areas)
- Flexbox (navbar, hero, stats row, footer)
- Bootstrap 5 CDN integration
- Mobile-first media queries (480px / 768px / 900px)
- Hamburger menu with JS toggle
- 4-column responsive footer grid

**Open:** `practical3/index.html`

---

## Practical 4 – JavaScript DOM Manipulation

**File:** `practical4/index.html` + `js/main.js`

**Features Implemented:**
| Feature | Detail |
|---|---|
| Notification Banner | Dismiss with session storage, animated collapse |
| Hamburger Nav | ARIA-controlled, closes on outside click |
| Dark/Light Theme | Toggle + `localStorage` restore on page load |
| FAQ Accordion | Single-open, ARIA expand/collapse, arrow-key navigation |
| Modal Popup | Focus trap, Escape key close, returns focus on dismiss |
| Content Slider | Auto-play, prev/next, dot navigation, keyboard arrows, pause on hover |
| Transition Cards | CSS hover lift + JS click pulse animation + toast notifications |
| localStorage | Saves and restores all UI preferences on page load |

**Open:** `practical4/index.html`

---

## Practical 5 – Registration Form with Validation

**File:** `practical5/index.html` + `js/validate.js`

**Validated Fields (with Regex):**
- First/Last Name, Email, Mobile (Indian 10-digit), Date of Birth (min age 15)
- Course, Year of Study, Gender, Roll Number (optional, format validated)
- Password (strength: uppercase + lowercase + number + special char)
- Confirm Password (match check)
- Terms & Conditions checkbox
- Custom Canvas CAPTCHA (Advanced Extension)

**Features:**
- Real-time validation on `input` / `blur` / `change` events (Intermediate)
- 4-level password strength meter with requirement checklist
- Show/Hide password toggle
- Canvas-generated CAPTCHA with refresh (Advanced)
- Step progress indicator (3 steps)
- Live summary sidebar (updates as you type)
- Accessible error messages (`aria-describedby`, `role="alert"`)
- Success panel on valid submission

**Open:** `practical5/index.html`

---

## Tools & Technologies

- HTML5 (semantic elements, input types, ARIA)
- CSS3 (Grid, Flexbox, custom properties, media queries)
- Bootstrap 5.3 (CDN)
- JavaScript ES6+ (DOM API, localStorage, sessionStorage, Canvas API)
- Browser DevTools for testing & accessibility inspection

---

## Author

**Ishal Akhtariya** | Roll: BCA/2024/021  
Web Technology Lab – 2026
