/* ============================================================
   partials.js — Shared navbar and footer injected on every page
   This means you only edit nav/footer in ONE place.
   ============================================================ */

// ── NAV ───────────────────────────────────────────────────────
function renderNav(activePage = '') {
  const links = [
    {
      label: 'How We Help',
      children: [
        { label: 'About RIPIN',        href: 'about.html' },
        { label: 'News & Events',      href: 'news.html' },
        { label: 'Services & Support', href: 'services.html' },
        { label: 'Testimonials',       href: 'testimonials.html' },
      ],
    },
    {
      label: 'How To Get Help',
      children: [
        { label: 'Special Education',     href: 'special-education.html' },
        { label: 'Health Care',           href: 'healthcare.html' },
        { label: 'Healthy Aging',         href: 'healthy-aging.html' },
        { label: 'Cedar Family Center',   href: 'cedar.html' },
        { label: 'Early Intervention',    href: 'early-intervention.html' },
        { label: 'Self-Directed Support', href: 'self-directed-support.html' },
        { label: 'Support Groups',        href: 'support-groups.html' },
        { label: 'Call Center',           href: 'call-center.html' },
        { label: 'Resources',             href: 'resources.html' },
        { label: 'Recursos en Español',   href: 'resources.html?lang=es' },
        { label: 'Webinars',              href: 'webinars.html' },
        { label: 'Calendar',              href: 'calendar.html' },
      ],
    },
    {
      label: 'How To Help',
      children: [
        { label: 'Donate',    href: 'donate.html' },
        { label: 'Volunteer', href: 'volunteer.html' },
        { label: 'Careers',   href: 'careers.html' },
        { label: 'Contact',   href: 'contact.html' },
      ],
    },
  ]

  const dropdowns = links.map(section => `
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        ${section.label}
      </a>
      <ul class="dropdown-menu">
        ${section.children.map(c => `
          <li><a class="dropdown-item" href="${c.href}">${c.label}</a></li>
        `).join('')}
      </ul>
    </li>
  `).join('')

  document.getElementById('ripin-nav').innerHTML = `
    <!-- Top bar -->
    <div class="ripin-topbar">
      <div class="container d-flex justify-content-between align-items-center">
        <span class="text-white-50 small">Our services are free, multilingual &amp; confidential</span>
        <a href="tel:401-270-0101" class="text-white fw-semibold small text-decoration-none">
          📞 Need Help? 401-270-0101
        </a>
      </div>
    </div>

    <!-- Main navbar -->
    <nav class="ripin-navbar navbar navbar-expand-lg">
      <div class="container">
        <a class="navbar-brand" href="index.html">RIPIN</a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
          <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
            <line x1="3" y1="6"  x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            ${dropdowns}
          </ul>
          <div class="d-flex gap-2 mt-3 mt-lg-0">
            <a href="calendar.html" class="btn btn-ripin-ghost btn-sm">Calendar</a>
            <a href="donate.html"   class="btn btn-ripin-primary btn-sm">Donate</a>
          </div>
        </div>
      </div>
    </nav>
  `
}

// ── FOOTER ────────────────────────────────────────────────────
function renderFooter() {
  document.getElementById('ripin-footer').innerHTML = `
    <!-- CTA Strip -->
    <div class="ripin-cta-strip text-center text-white">
      <div class="container">
        <h2 class="font-display fw-bold mb-3" style="font-size:clamp(1.5rem,3vw,2rem)">
          Need Help Navigating the System?
        </h2>
        <p class="mb-4" style="color:rgba(255,255,255,0.8); max-width:520px; margin:0 auto 1.5rem;">
          Our services are free, multilingual, and confidential. Call us or reach out online.
        </p>
        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
          <a href="tel:401-270-0101" class="btn btn-ripin-ghost btn-lg">📞 Call 401-270-0101</a>
          <a href="contact.html"     class="btn btn-ripin-white btn-lg">Contact Us Online</a>
        </div>
      </div>
    </div>

    <!-- Footer body -->
    <footer class="ripin-footer">
      <div class="container">
        <div class="row g-5">

          <!-- Brand -->
          <div class="col-lg-4">
            <div class="font-display fw-bold text-white mb-3" style="font-size:1.75rem">RIPIN</div>
            <p style="font-size:0.9rem;line-height:1.7">
              Rhode Island Parent Information Network. Helping Rhode Islanders navigate
              health care, special education, and healthy aging since 1991.
            </p>
            <div class="mt-3" style="font-size:0.875rem;line-height:2">
              <div>📍 300 Jefferson Blvd, Suite 300, Warwick, RI 02888</div>
              <div>📞 <a href="tel:401-270-0101">401-270-0101</a></div>
              <div>📠 401-270-7049</div>
              <div>✉️ <a href="mailto:info@ripin.org">info@ripin.org</a></div>
            </div>
            <div class="d-flex gap-3 mt-3">
              <a href="https://www.facebook.com/RIPIN.ORG" target="_blank" rel="noopener" aria-label="Facebook">
                <svg width="20" height="20" fill="rgba(255,255,255,0.6)" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              </a>
              <a href="https://www.instagram.com/ripin_ri/" target="_blank" rel="noopener" aria-label="Instagram">
                <svg width="20" height="20" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="rgba(255,255,255,0.6)" stroke="none"/></svg>
              </a>
              <a href="https://twitter.com/RIPIN_RI" target="_blank" rel="noopener" aria-label="Twitter/X">
                <svg width="20" height="20" fill="rgba(255,255,255,0.6)" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
              </a>
              <a href="https://www.youtube.com/@ripin_ri" target="_blank" rel="noopener" aria-label="YouTube">
                <svg width="20" height="20" fill="rgba(255,255,255,0.6)" viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.54C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="var(--ripin-navy)"/></svg>
              </a>
            </div>
          </div>

          <!-- Get Help -->
          <div class="col-6 col-lg-2">
            <h5>Get Help</h5>
            <ul class="list-unstyled">
              <li class="mb-1"><a href="special-education.html">Special Education</a></li>
              <li class="mb-1"><a href="healthcare.html">Healthcare</a></li>
              <li class="mb-1"><a href="healthy-aging.html">Healthy Aging</a></li>
              <li class="mb-1"><a href="resources.html">Resources</a></li>
              <li class="mb-1"><a href="calendar.html">Calendar</a></li>
              <li class="mb-1"><a href="webinars.html">Webinars</a></li>
            </ul>
          </div>

          <!-- About -->
          <div class="col-6 col-lg-2">
            <h5>About RIPIN</h5>
            <ul class="list-unstyled">
              <li class="mb-1"><a href="about.html">About Us</a></li>
              <li class="mb-1"><a href="news.html">News &amp; Events</a></li>
              <li class="mb-1"><a href="testimonials.html">Testimonials</a></li>
              <li class="mb-1"><a href="careers.html">Careers</a></li>
              <li class="mb-1"><a href="volunteer.html">Volunteer</a></li>
              <li class="mb-1"><a href="contact.html">Contact Us</a></li>
            </ul>
          </div>

          <!-- Recursos -->
          <div class="col-6 col-lg-2">
            <h5>Recursos</h5>
            <ul class="list-unstyled">
              <li class="mb-1"><a href="resources.html?lang=es">Recursos en Español</a></li>
              <li class="mb-1"><a href="webinars.html#espanol">Seminarios Web</a></li>
            </ul>
          </div>

        </div>

        <!-- Bottom bar -->
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
          <span>© 1991–${new Date().getFullYear()} RIPIN, Inc. All rights reserved. 501(c)(3) nonprofit.</span>
          <div class="d-flex gap-3">
            <a href="privacy-policy.html">Privacy Policy</a>
            <a href="accessibility.html">Accessibility</a>
            <a href="admin/login.html">Staff Login</a>
          </div>
        </div>
      </div>
    </footer>
  `
}
