# Practical 7 — Test Cases (PHP Form Processing with CSV/JSON Storage)

Form under test: `PAGES/register.php` (method POST). Storage: `DATA/registrations.csv`, `DATA/registrations.json`.

| # | Test input | Expected server result |
|---|------------|------------------------|
| 1 | All valid: `Khush Viradiya / khush@example.com / 9876543210 / 25DCE123 / Information Technology / 2025 / Pass@1234 + match + terms ticked` | Success message, +1 row in CSV and JSON |
| 2 | Submit empty form | 8 errors (name, email, phone, id, course, year, password, confirm, terms) |
| 3 | Email `abc` / `a@b` | Error: Enter a valid email address |
| 4 | Phone `12345` / `abcdefghij` | Error: must be exactly 10 digits |
| 5 | Student ID `ab` / `id@#$` | Error: 4-20 chars, letters/digits/hyphen |
| 6 | Course = tampered value `Hacking` (via dev tools) | Error: Invalid course selected (whitelist check) |
| 7 | Year `1999` / `3000` / `abc` | Error: between 2000 and 2099 |
| 8 | Password `pass` / `password1` (no upper/special) | Error: min 8 + upper/lower/digit/special |
| 9 | Password `Pass@1234`, confirm `Pass@9999` | Error: passwords do not match |
| 10 | Valid data but terms unticked | Error: must agree to Terms |
| 11 | Valid data but `csrf_token` removed (dev tools) | Error: invalid session token, nothing stored |
| 12 | Valid submit twice, then open `view-records.php` | Both rows listed in CSV table and JSON table |

## How to run
1. Copy `PRACTICAL 7` to `C:\xampp\htdocs\`, start Apache in XAMPP.
2. Open `http://localhost/PRACTICAL%207/PAGES/register.php`.
3. Try cases 1-11, then verify case 12 in `view-records.php` and by opening the CSV/JSON files.
