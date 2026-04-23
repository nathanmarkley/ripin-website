# RIPIN Website — iPad Setup Guide
## Get Your Dev Site Live in About 1 Hour

Everything done in Safari on your iPad. No terminal needed.

---

## STEP 1 — Create Your GitHub Account (5 min)

GitHub stores all your website files safely in the cloud.

1. Open Safari → go to **github.com**
2. Click **Sign up**
3. Use your work email (e.g. you@ripin.org)
4. Choose the **Free** plan
5. Verify your email

**Done! You now have a place to store your code.**

---

## STEP 2 — Create Your GitHub Repository (5 min)

A "repository" (repo) is just a folder for your project.

1. Log in to github.com
2. Click the **+** button (top right) → **New repository**
3. Fill in:
   - **Repository name:** `ripin-website`
   - **Description:** RIPIN website
   - **Visibility:** ● Private
   - ✅ Check **Add a README file**
4. Click **Create repository**

---

## STEP 3 — Connect Working Copy to GitHub (10 min)

Working Copy is your iPad app for managing code files.

1. Open **Working Copy** on your iPad
2. Tap the **⚙️ gear icon** (Settings)
3. Tap **GitHub** → **Connect**
4. Log in with your GitHub account
5. Back on the main screen, tap **+** → **Clone repository**
6. Find `ripin-website` in the list → tap **Clone**

**Now your GitHub repo is on your iPad.**

---

## STEP 4 — Add Your Site Files (10 min)

1. In Working Copy, tap your `ripin-website` repo
2. Tap the **folder icon** → **Import**
3. Select all the RIPIN files from the ZIP you downloaded
   (Files app → Downloads → ripin-bootstrap folder)
4. Once imported, tap **Commit**
5. Type a message: `Initial site files`
6. Tap **Commit & Push**

**Your files are now on GitHub. ✅**

---

## STEP 5 — Create Netlify Account & Connect GitHub (10 min)

Netlify hosts your website and auto-deploys when you push changes.

1. Open Safari → go to **netlify.com**
2. Click **Sign up** → choose **Sign up with GitHub**
3. Authorize Netlify to access GitHub
4. Click **Add new site** → **Import an existing project**
5. Choose **GitHub** → find `ripin-website`
6. Leave all settings as-is (Netlify detects HTML sites automatically)
7. Click **Deploy site**

**Wait about 60 seconds…**

Netlify gives you a URL like: `https://amazing-name-123.netlify.app`

You can customize this under **Site settings → Change site name** → set it to something like `ripin-dev.netlify.app`

**Your dev site is now live! 🎉**

---

## STEP 6 — Create Sanity Account (10 min)

Sanity is where staff log in to edit content.

1. Open Safari → go to **sanity.io** a$dS4Q!QW8giPR
2. Click **Start for free**
3. Sign up with your email
4. Click **Create new project**
5. Fill in:
   - **Project name:** RIPIN Website
   - **Dataset:** production (default)
   - **Plan:** Free
6. Click **Create project**
7. **Copy your Project ID** — it looks like `abc12def`
   (shown right on the project dashboard)

---

## STEP 7 — Connect Sanity to Your Site (5 min)

1. Open **Textastic** on your iPad
2. Open the file `js/sanity.js`
3. Find this line near the top:
   ```
   projectId: 'YOUR_PROJECT_ID',
   ```
4. Replace `YOUR_PROJECT_ID` with the ID you copied
   e.g.: `projectId: 'abc12def',`
5. Save the file in Textastic

6. Open **Working Copy**
7. You'll see `js/sanity.js` listed as changed
8. Tap **Commit** → type `Connect Sanity project` → **Commit & Push**

Netlify auto-deploys in ~30 seconds. Your site is now connected to Sanity.

---

## STEP 8 — Set Up the Sanity Schema (15 min)

This tells Sanity what content fields staff can edit.

**Option A — Use Sanity's website (easiest, no code):**
1. Go to **sanity.io/manage** → your project
2. Click **API** → **CORS Origins**
3. Add your Netlify URL: `https://ripin-dev.netlify.app`
4. Also add `http://localhost:3333` for local editing

**Option B — Deploy Sanity Studio:**
Sanity Studio is the editing interface for staff.
We'll use Sanity's free hosted Studio so nothing needs to be installed.

1. Go to **sanity.io/manage** → your project
2. Click **Deploy Studio**
3. Choose a URL: `ripin.sanity.studio`
4. Click **Deploy**

Staff will log in at: `https://ripin.sanity.studio`

---

## STEP 9 — Add Staff Users (5 min)

1. Go to **sanity.io/manage** → your project
2. Click **Members** → **Invite members**
3. Enter each staff person's email
4. Set their role:
   - **Editor** — can create/edit content, can't delete
   - **Administrator** — full access (just for you)
5. They get an email invitation → set their own password

**That's it. Staff can now log in and edit everything.**

---

## STEP 10 — Add Your First Content in Sanity

1. Go to `https://ripin.sanity.studio`
2. Log in
3. Start with **⚙️ Site Settings** — add your phone, address, etc.
4. Then add a few **📅 Calendar Events** to test
5. Add some **📚 Resources**
6. Check your dev site — content appears automatically!

---

## Your Daily Workflow Going Forward

**To edit HTML/CSS:**
```
Textastic → edit file → Working Copy → Commit & Push → 
Netlify auto-deploys in ~30 seconds
```

**To add/edit content (staff):**
```
Browser → ripin.sanity.studio → log in → edit → Publish
Site updates within 60 seconds, no code needed
```

---

## Quick Reference

| What | Where |
|---|---|
| Your code files | Working Copy (iPad) or github.com |
| Dev site | ripin-dev.netlify.app |
| Sanity Studio (staff login) | ripin.sanity.studio |
| Netlify dashboard | app.netlify.com |
| GitHub | github.com |

---

## When You're Ready to Go Live

When the dev site looks great and all content is imported:

1. Go to **app.netlify.com** → your site → **Domain settings**
2. Click **Add custom domain** → type `ripin.org`
3. Netlify gives you DNS settings to add to your domain registrar
4. Update DNS → site goes live (takes 1–24 hours to propagate)
5. Netlify automatically installs a free SSL certificate (https://)
6. Cancel Bluehost/WordPress when you're comfortable

---

## Need Help?

Paste any error messages or questions into Claude.ai —
I can help troubleshoot anything step by step.
