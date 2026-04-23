/* ============================================================
   sanity.js — Connects your HTML site to Sanity CMS
   You only need to fill in your PROJECT ID below (once).
   Everything else is automatic.
   ============================================================ */

// ── YOUR SANITY CONFIG ─────────────────────────────────────────
// Fill these in after you create your Sanity project
// You'll find PROJECT_ID in your Sanity dashboard at sanity.io/manage
const SANITY_CONFIG = {
  projectId: 'YOUR_PROJECT_ID',   // e.g. 'abc123de'
  dataset:   'production',
  apiVersion: '2024-01-01',
}
// ──────────────────────────────────────────────────────────────

// Build the Sanity CDN query URL
function sanityUrl(query) {
  const encoded = encodeURIComponent(query)
  return `https://${SANITY_CONFIG.projectId}.api.sanity.io/v${SANITY_CONFIG.apiVersion}/data/query/${SANITY_CONFIG.dataset}?query=${encoded}`
}

// Build a Sanity image URL from an image reference
function imageUrl(ref, width = 800) {
  if (!ref) return ''
  // ref format: image-{id}-{width}x{height}-{format}
  const parts = ref.split('-')
  const id     = parts[1]
  const dims   = parts[2]
  const fmt    = parts[3]
  return `https://cdn.sanity.io/images/${SANITY_CONFIG.projectId}/${SANITY_CONFIG.dataset}/${id}-${dims}.${fmt}?w=${width}&auto=format`
}

// Build a Sanity file URL from a file reference
function fileUrl(ref) {
  if (!ref) return ''
  const parts = ref.split('-')
  const id    = parts[1]
  const ext   = parts[parts.length - 1]
  return `https://cdn.sanity.io/files/${SANITY_CONFIG.projectId}/${SANITY_CONFIG.dataset}/${id}.${ext}`
}

// Core fetch function
async function sanityFetch(query) {
  try {
    const res  = await fetch(sanityUrl(query))
    const data = await res.json()
    return data.result || []
  } catch (err) {
    console.error('Sanity fetch error:', err)
    return []
  }
}

// ── SITE SETTINGS ─────────────────────────────────────────────
async function getSiteSettings() {
  return await sanityFetch(`*[_type == "siteSettings"][0]`)
}

// ── BANNER ────────────────────────────────────────────────────
async function getBanner() {
  return await sanityFetch(`*[_type == "banner" && active == true][0]`)
}

// ── POPUPS ────────────────────────────────────────────────────
async function getPopup(pageSlug) {
  // Get a popup for this specific page OR a sitewide popup
  return await sanityFetch(
    `*[_type == "popup" && active == true && (page == "${pageSlug}" || page == "all")][0]`
  )
}

// ── PAGES ─────────────────────────────────────────────────────
async function getPage(slug) {
  return await sanityFetch(`*[_type == "page" && slug.current == "${slug}"][0]`)
}

async function getAllPages() {
  return await sanityFetch(`*[_type == "page" && status == "published"] | order(title asc) { title, slug, excerpt }`)
}

// ── EVENTS ────────────────────────────────────────────────────
async function getUpcomingEvents(limit = 10) {
  const now = new Date().toISOString()
  return await sanityFetch(
    `*[_type == "event" && status == "published" && startDate >= "${now}"] | order(startDate asc) [0...${limit}]`
  )
}

async function getAllEvents() {
  return await sanityFetch(
    `*[_type == "event" && status == "published"] | order(startDate asc)`
  )
}

async function getFeaturedEvents(limit = 3) {
  const now = new Date().toISOString()
  return await sanityFetch(
    `*[_type == "event" && status == "published" && featured == true && startDate >= "${now}"] | order(startDate asc) [0...${limit}]`
  )
}

// ── RESOURCES ─────────────────────────────────────────────────
async function getResources(filters = {}) {
  let conditions = [`_type == "resource"`, `status == "published"`]
  if (filters.type)     conditions.push(`resourceType == "${filters.type}"`)
  if (filters.category) conditions.push(`"${filters.category}" in categories[]->slug.current`)
  if (filters.language) conditions.push(`language == "${filters.language}"`)

  const query = `*[${conditions.join(' && ')}] | order(featured desc, _createdAt desc) {
    _id, title, description, resourceType, language, featured,
    url, "fileUrl": file.asset->url,
    "categories": categories[]->{ name, slug },
    "imageUrl": image.asset->url
  }`
  return await sanityFetch(query)
}

async function getResourceCategories() {
  return await sanityFetch(`*[_type == "resourceCategory"] | order(sortOrder asc) { _id, name, slug }`)
}

// ── NEWS / BLOG ───────────────────────────────────────────────
async function getNewsArticles(limit = 10) {
  return await sanityFetch(
    `*[_type == "newsArticle" && status == "published"] | order(publishedAt desc) [0...${limit}] {
      _id, title, slug, excerpt, publishedAt,
      "imageUrl": featuredImage.asset->url,
      "author": author->name
    }`
  )
}

async function getNewsArticle(slug) {
  return await sanityFetch(
    `*[_type == "newsArticle" && slug.current == "${slug}"][0]`
  )
}

// ── TESTIMONIALS ─────────────────────────────────────────────
async function getTestimonials() {
  return await sanityFetch(
    `*[_type == "testimonial" && active == true] | order(_createdAt desc)`
  )
}

// ── PORTABLE TEXT RENDERER ───────────────────────────────────
// Converts Sanity's rich text format to HTML
function renderPortableText(blocks) {
  if (!blocks || !Array.isArray(blocks)) return ''

  return blocks.map(block => {
    if (block._type === 'image') {
      const url = imageUrl(block.asset?._ref)
      const alt = block.alt || ''
      return `<figure class="my-4">
        <img src="${url}" alt="${alt}" class="img-fluid rounded-3">
        ${block.caption ? `<figcaption class="text-muted text-center mt-2 small">${block.caption}</figcaption>` : ''}
      </figure>`
    }

    if (block._type === 'youtubeEmbed') {
      const videoId = extractYouTubeId(block.url)
      return `<div class="ratio ratio-16x9 my-4 rounded-3 overflow-hidden">
        <iframe src="https://www.youtube.com/embed/${videoId}" 
                title="${block.title || 'Video'}"
                allowfullscreen loading="lazy"></iframe>
      </div>`
    }

    if (block._type !== 'block') return ''

    // Handle text marks (bold, italic, links)
    const renderChildren = (children) => {
      return (children || []).map(child => {
        let text = escapeHtml(child.text || '')
        if (!child.marks?.length) return text

        child.marks.forEach(mark => {
          if (mark === 'strong') text = `<strong>${text}</strong>`
          else if (mark === 'em') text = `<em>${text}</em>`
          else if (mark === 'underline') text = `<u>${text}</u>`
          else if (mark === 'code') text = `<code>${text}</code>`
          else {
            // It's a link mark — find the mark definition
            const def = (block.markDefs || []).find(d => d._key === mark)
            if (def?._type === 'link') {
              const target = def.blank ? ' target="_blank" rel="noopener"' : ''
              text = `<a href="${def.href}"${target}>${text}</a>`
            }
          }
        })
        return text
      }).join('')
    }

    const content = renderChildren(block.children)

    switch (block.style) {
      case 'h1': return `<h1 class="font-display fw-bold mt-4 mb-3">${content}</h1>`
      case 'h2': return `<h2 class="font-display fw-bold mt-4 mb-3">${content}</h2>`
      case 'h3': return `<h3 class="font-display fw-bold mt-3 mb-2">${content}</h3>`
      case 'h4': return `<h4 class="fw-bold mt-3 mb-2">${content}</h4>`
      case 'blockquote':
        return `<blockquote class="blockquote border-start border-4 ps-4 my-4" 
                            style="border-color:var(--ripin-blue)!important">
                  <p class="mb-0 fst-italic">${content}</p>
                </blockquote>`
      case 'normal':
      default:
        if (!content.trim()) return '<br>'
        return `<p class="mb-3">${content}</p>`
    }
  }).join('\n')
}

// Helper: extract YouTube video ID from URL
function extractYouTubeId(url) {
  if (!url) return ''
  const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/)
  return match ? match[1] : ''
}

// Helper: escape HTML special chars
function escapeHtml(str) {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

// ── BANNER RENDERER ───────────────────────────────────────────
async function renderBanner() {
  const banner = await getBanner()
  const el = document.getElementById('ripin-banner')
  if (!el) return
  if (!banner || !banner.active) { el.style.display = 'none'; return }

  const colors = {
    blue:   { bg: 'var(--ripin-blue)',   text: '#fff' },
    orange: { bg: 'var(--ripin-orange)', text: '#fff' },
    red:    { bg: '#DC2626',             text: '#fff' },
    teal:   { bg: 'var(--ripin-teal)',   text: '#fff' },
    yellow: { bg: '#D97706',             text: '#fff' },
  }
  const color = colors[banner.style] || colors.blue

  el.style.background = color.bg
  el.style.color      = color.text
  el.innerHTML = `
    <div class="container d-flex align-items-center justify-content-between gap-3 flex-wrap py-2">
      <p class="mb-0 fw-semibold" style="font-size:0.9375rem">
        ${banner.icon ? banner.icon + ' ' : '📢 '}${banner.message}
      </p>
      <div class="d-flex align-items-center gap-3 flex-shrink-0">
        ${banner.buttonText && banner.buttonUrl
          ? `<a href="${banner.buttonUrl}" class="btn btn-sm btn-ripin-white py-1 px-3" 
                style="color:${color.bg}">${banner.buttonText}</a>`
          : ''}
        <button onclick="this.closest('#ripin-banner').style.display='none'"
                style="background:none;border:none;color:inherit;font-size:1.25rem;line-height:1;cursor:pointer;opacity:0.8"
                aria-label="Close">×</button>
      </div>
    </div>
  `
}

// ── POPUP RENDERER ────────────────────────────────────────────
async function renderPopup(pageSlug) {
  const popup = await getPopup(pageSlug)
  if (!popup || !popup.active) return

  // "Show once" logic — skip if already seen
  const storageKey = `ripin_popup_${popup._id}`
  if (popup.showOnce && localStorage.getItem(storageKey)) return

  const delay = (popup.delaySeconds || 3) * 1000

  setTimeout(() => {
    // Build popup HTML
    const modalId = 'ripinPopupModal'

    // Image section
    const imgHtml = popup.image
      ? `<img src="${imageUrl(popup.image?.asset?._ref, 600)}" 
              alt="${popup.imageAlt || ''}" 
              class="img-fluid w-100" 
              style="max-height:220px;object-fit:cover">`
      : ''

    // Embed / form section
    let embedHtml = ''
    if (popup.embedType === 'code' && popup.embedCode) {
      embedHtml = `<div class="popup-embed mt-3">${popup.embedCode}</div>`
    } else if (popup.embedType === 'contactForm') {
      embedHtml = `
        <form id="popupContactForm" class="mt-3">
          <div class="mb-2">
            <input type="text"  name="name"    placeholder="Your name"          class="form-control" required>
          </div>
          <div class="mb-2">
            <input type="email" name="email"   placeholder="Your email address" class="form-control" required>
          </div>
          <div class="mb-3">
            <textarea name="message" placeholder="Your message" rows="3" class="form-control" required></textarea>
          </div>
          <button type="submit" class="btn btn-ripin-primary w-100">Send Message</button>
          <div id="popupFormMsg" class="mt-2 text-center small" style="display:none"></div>
        </form>
      `
    }

    // Button
    const btnHtml = popup.buttonText && popup.buttonUrl && popup.embedType !== 'code'
      ? `<div class="mt-3 text-center">
           <a href="${popup.buttonUrl}" class="btn btn-ripin-primary"
              ${popup.buttonNewTab ? 'target="_blank" rel="noopener"' : ''}>
             ${popup.buttonText}
           </a>
         </div>`
      : ''

    // Size
    const sizeClass = popup.size === 'large' ? 'modal-lg' : popup.size === 'small' ? 'modal-sm' : ''

    // Header color
    const headerColor = popup.headerColor || 'var(--ripin-blue)'

    // Inject modal into page
    const modalHtml = `
      <div class="modal fade" id="${modalId}" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered ${sizeClass}">
          <div class="modal-content border-0 overflow-hidden" style="border-radius:1rem">

            ${imgHtml ? `<div class="popup-image">${imgHtml}</div>` : ''}

            <div class="modal-header border-0 text-white"
                 style="background:${headerColor};border-radius:${imgHtml ? '0' : '1rem 1rem 0 0'}">
              <h5 class="modal-title font-display fw-bold">${popup.heading || ''}</h5>
              <button type="button" class="btn-close btn-close-white" 
                      data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 pb-4">
              ${popup.message
                ? `<p class="text-muted mb-0">${popup.message}</p>`
                : ''}
              ${embedHtml}
              ${btnHtml}
            </div>

          </div>
        </div>
      </div>
    `

    document.body.insertAdjacentHTML('beforeend', modalHtml)
    const modal = new bootstrap.Modal(document.getElementById(modalId))
    modal.show()

    // Mark as seen if showOnce
    if (popup.showOnce) {
      document.getElementById(modalId).addEventListener('hidden.bs.modal', () => {
        localStorage.setItem(storageKey, Date.now())
      })
    }

    // Handle built-in contact form submission
    const contactForm = document.getElementById('popupContactForm')
    if (contactForm) {
      contactForm.addEventListener('submit', async (e) => {
        e.preventDefault()
        const msg = document.getElementById('popupFormMsg')
        const fd  = new FormData(contactForm)
        // Uses Netlify Forms — free, no backend needed
        try {
          await fetch('/', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              'form-name': 'popup-contact',
              name:    fd.get('name'),
              email:   fd.get('email'),
              message: fd.get('message'),
            }).toString()
          })
          msg.style.display = 'block'
          msg.style.color   = 'green'
          msg.textContent   = '✅ Message sent! We\'ll be in touch soon.'
          contactForm.reset()
        } catch {
          msg.style.display = 'block'
          msg.style.color   = 'red'
          msg.textContent   = 'Something went wrong. Please try again.'
        }
      })
    }

  }, delay)
}

// ── CALENDAR RENDERER ─────────────────────────────────────────
function buildCalendar(containerId, events, year, month) {
  const container = document.getElementById(containerId)
  if (!container) return

  const firstDay  = new Date(year, month, 1).getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const today     = new Date()

  // Map events to date keys
  const eventMap  = {}
  events.forEach(ev => {
    const d   = new Date(ev.startDate)
    if (d.getFullYear() === year && d.getMonth() === month) {
      const key = d.getDate()
      if (!eventMap[key]) eventMap[key] = []
      eventMap[key].push(ev)
    }
  })

  const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']
  let html = `<table class="calendar-grid w-100">
    <thead><tr>${days.map(d => `<th>${d}</th>`).join('')}</tr></thead>
    <tbody><tr>`

  // Empty cells before month starts
  for (let i = 0; i < firstDay; i++) {
    html += `<td class="other-month"></td>`
  }

  for (let day = 1; day <= daysInMonth; day++) {
    const isToday = today.getDate() === day &&
                    today.getMonth() === month &&
                    today.getFullYear() === year
    const dayEvents = eventMap[day] || []

    html += `<td class="${isToday ? 'today' : ''}">
      <span class="day-number">${day}</span>`

    dayEvents.slice(0, 3).forEach(ev => {
      html += `<span class="cal-event-pill" 
                     style="background:${ev.color || 'var(--ripin-blue)'}"
                     onclick="showEventDetail('${ev._id}')"
                     title="${ev.title}">
                 ${ev.title}
               </span>`
    })

    if (dayEvents.length > 3) {
      html += `<span class="cal-event-pill" style="background:#6B7280">+${dayEvents.length - 3} more</span>`
    }

    html += `</td>`

    // New row on Saturday (except last day)
    if ((firstDay + day) % 7 === 0 && day !== daysInMonth) {
      html += `</tr><tr>`
    }
  }

  // Remaining empty cells
  const lastCell = (firstDay + daysInMonth) % 7
  if (lastCell !== 0) {
    for (let i = lastCell; i < 7; i++) {
      html += `<td class="other-month"></td>`
    }
  }

  html += `</tr></tbody></table>`
  container.innerHTML = html
}

// Store events globally for modal access
window._ripinEvents = []

function showEventDetail(eventId) {
  const ev = window._ripinEvents.find(e => e._id === eventId)
  if (!ev) return

  const d      = new Date(ev.startDate)
  const end    = ev.endDate ? new Date(ev.endDate) : null
  const dateStr = d.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' })
  const timeStr = ev.allDay
    ? 'All day'
    : d.toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit' }) +
      (end ? ' – ' + end.toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit' }) : '')

  const modalHtml = `
    <div class="modal fade" id="eventDetailModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden" style="border-radius:1rem">
          <div class="modal-header border-0 text-white"
               style="background:${ev.color || 'var(--ripin-blue)'}">
            ${ev.category
              ? `<span class="ripin-badge me-2" style="background:rgba(255,255,255,0.2);color:#fff">${ev.category}</span>`
              : ''}
            <h5 class="modal-title font-display fw-bold">${ev.title}</h5>
            <button type="button" class="btn-close btn-close-white ms-auto" 
                    data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <div class="d-flex gap-3 mb-3">
              <span style="font-size:1.25rem">🗓️</span>
              <div>
                <div class="fw-semibold">${dateStr}</div>
                <div class="text-muted small">${timeStr}</div>
              </div>
            </div>
            ${ev.location ? `
            <div class="d-flex gap-3 mb-3">
              <span style="font-size:1.25rem">📍</span>
              <div>
                <div class="fw-semibold">${ev.location}</div>
                ${ev.locationUrl
                  ? `<a href="${ev.locationUrl}" target="_blank" class="small">View on map →</a>`
                  : ''}
              </div>
            </div>` : ''}
            ${ev.description
              ? `<p class="text-muted mb-3">${ev.description}</p>`
              : ''}
            ${ev.registrationUrl
              ? `<a href="${ev.registrationUrl}" target="_blank" 
                    class="btn btn-ripin-primary w-100">Register for this Event</a>`
              : ''}
          </div>
        </div>
      </div>
    </div>
  `

  // Remove previous modal if exists
  document.getElementById('eventDetailModal')?.remove()
  document.body.insertAdjacentHTML('beforeend', modalHtml)
  new bootstrap.Modal(document.getElementById('eventDetailModal')).show()
}
