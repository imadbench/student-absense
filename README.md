# Student Absence (Attendance) — AWP

This is a lightweight front-end attendance app used for recording student session attendance and participation.

## What this repository contains

- `index.html` — Main application UI (attendance table, search, controls).
- `styles.css` — Styling and responsive layout tweaks.
- `app.js` — Front-end logic to render students, manage attendance, sorting and reports.
- `images/` — Folder that stores student photos and other assets. Two placeholder images are included (`image5.svg`, `image1.svg`).

## Quick start (Windows PowerShell)

1. Open PowerShell in the project folder (where `index.html` is located):

```powershell
cd "C:\Users\name\student apsence"
```

2. Start a simple static server. If you have Python installed (recommended):

```powershell
python -m http.server 8000
```

Or with Node (no install required if you use `npx`):

```powershell
npx http-server -p 8000
```

3. Open the app in your browser:

```
http://localhost:8000
```

## Adding real student photos

- Add image files (JPG/PNG/SVG) into the `images/` folder.
- To display a photo inside a row, update `index.html` or `app.js` where rows are rendered. Example markup for a photo cell:

```html
<td class="photo-cell"><img src="images/your-photo.jpg" alt="Student photo" width="48" height="48"></td>
```

- If you want me to wire photos into the table rows (show an avatar column), tell me and I will update `index.html`/`app.js` to add a photo column and populate it with available images.

## Notes & next steps I can help with

- Adjust column width percentages in the `<colgroup>` if you want different sizing.
- Re-enable the progress bar UI and wiring (`progress.js`) if you want an on-screen progress indicator.
- Wire click-to-advance attendance behavior (clicking a student row advances checks) if desired.

If you want me to embed the new sample photos into the table UI now, say so and I will patch `index.html`/`app.js` accordingly.
