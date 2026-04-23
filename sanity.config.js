// ============================================================
// sanity.config.js  — Paste this into your Sanity project
// This defines ALL content types staff can edit:
//   pages, events, resources, banners, popups, settings
// ============================================================

import {defineConfig, defineType, defineField} from 'sanity'
import {structureTool} from 'sanity/structure'
import {visionTool} from '@sanity/vision'

export default defineConfig({
  name: 'ripin',
  title: 'RIPIN Website',
  projectId: 'efoldtar',   // fill in after creating project
  dataset: 'production',

  plugins: [
    structureTool({
      structure: (S) => S.list().title('RIPIN Content').items([
        S.listItem().title('🏠 Homepage Banner').child(
          S.document().schemaType('banner').documentId('homepage-banner')
        ),
        S.listItem().title('💬 Popups').child(S.documentTypeList('popup')),
        S.divider(),
        S.listItem().title('📄 Pages').child(S.documentTypeList('page')),
        S.listItem().title('📰 News & Blog').child(S.documentTypeList('newsArticle')),
        S.listItem().title('📅 Calendar Events').child(S.documentTypeList('event')),
        S.listItem().title('📚 Resources').child(S.documentTypeList('resource')),
        S.listItem().title('🗂️ Resource Categories').child(S.documentTypeList('resourceCategory')),
        S.listItem().title('💬 Testimonials').child(S.documentTypeList('testimonial')),
        S.divider(),
        S.listItem().title('⚙️ Site Settings').child(
          S.document().schemaType('siteSettings').documentId('site-settings')
        ),
      ])
    }),
    visionTool(),
  ],

  schema: {
    types: [

      // ── SITE SETTINGS ───────────────────────────────────────
      defineType({
        name: 'siteSettings',
        title: 'Site Settings',
        type: 'document',
        fields: [
          defineField({ name: 'siteName',    title: 'Site Name',    type: 'string' }),
          defineField({ name: 'tagline',     title: 'Tagline',      type: 'string' }),
          defineField({ name: 'phone',       title: 'Phone Number', type: 'string' }),
          defineField({ name: 'fax',         title: 'Fax Number',   type: 'string' }),
          defineField({ name: 'email',       title: 'Email',        type: 'string' }),
          defineField({ name: 'address',     title: 'Address',      type: 'text', rows: 3 }),
          defineField({ name: 'facebook',    title: 'Facebook URL', type: 'url' }),
          defineField({ name: 'instagram',   title: 'Instagram URL',type: 'url' }),
          defineField({ name: 'twitter',     title: 'Twitter/X URL',type: 'url' }),
          defineField({ name: 'youtube',     title: 'YouTube URL',  type: 'url' }),
          defineField({ name: 'linkedin',    title: 'LinkedIn URL', type: 'url' }),
        ],
      }),

      // ── BANNER ──────────────────────────────────────────────
      defineType({
        name: 'banner',
        title: 'Homepage Banner',
        type: 'document',
        fields: [
          defineField({
            name: 'active', title: 'Show Banner?', type: 'boolean',
            description: 'Turn the banner on or off without deleting it.',
            initialValue: false,
          }),
          defineField({ name: 'icon',    title: 'Icon/Emoji (optional)', type: 'string', description: 'e.g. 📢 or 🎉' }),
          defineField({ name: 'message', title: 'Banner Message', type: 'text', rows: 2,
            validation: R => R.required() }),
          defineField({ name: 'buttonText', title: 'Button Text (optional)', type: 'string' }),
          defineField({ name: 'buttonUrl',  title: 'Button Link (optional)', type: 'string' }),
          defineField({
            name: 'style', title: 'Banner Color', type: 'string',
            options: { list: ['blue','orange','red','teal','yellow'], layout: 'radio' },
            initialValue: 'blue',
          }),
        ],
        preview: {
          select: { title: 'message', subtitle: 'active' },
          prepare({ title, subtitle }) {
            return { title, subtitle: subtitle ? '✅ Active' : '⭕ Hidden' }
          }
        }
      }),

      // ── POPUP ────────────────────────────────────────────────
      defineType({
        name: 'popup',
        title: 'Popup',
        type: 'document',
        fields: [
          defineField({ name: 'name', title: 'Popup Name (internal)', type: 'string',
            description: 'e.g. "May Newsletter Signup" — only you see this',
            validation: R => R.required() }),
          defineField({
            name: 'active', title: 'Show Popup?', type: 'boolean', initialValue: false,
            description: 'Turn popup on or off without deleting it.',
          }),
          defineField({
            name: 'page', title: 'Show on Page', type: 'string',
            description: 'Which page to show this on.',
            options: {
              list: [
                { title: 'All Pages',        value: 'all' },
                { title: 'Homepage',         value: 'home' },
                { title: 'About',            value: 'about' },
                { title: 'Special Education',value: 'special-education' },
                { title: 'Healthcare',       value: 'healthcare' },
                { title: 'Resources',        value: 'resources' },
                { title: 'Calendar',         value: 'calendar' },
                { title: 'Contact',          value: 'contact' },
              ],
              layout: 'dropdown',
            },
            initialValue: 'all',
          }),
          defineField({ name: 'delaySeconds', title: 'Show After (seconds)', type: 'number', initialValue: 3 }),
          defineField({
            name: 'showOnce', title: 'Show Only Once Per Visitor?', type: 'boolean', initialValue: true,
            description: 'If on, visitors only see this popup once every 30 days.',
          }),

          // Content
          defineField({ name: 'heading', title: 'Popup Heading', type: 'string' }),
          defineField({ name: 'message', title: 'Popup Message', type: 'text', rows: 3 }),
          defineField({
            name: 'image', title: 'Image (optional)', type: 'image',
            options: { hotspot: true },
            fields: [defineField({ name: 'alt', title: 'Alt Text', type: 'string' })],
          }),

          // Embed type
          defineField({
            name: 'embedType', title: 'Content Type', type: 'string',
            options: {
              list: [
                { title: 'Button Only',                        value: 'button' },
                { title: 'Embed Code (Constant Contact etc.)', value: 'code' },
                { title: 'Built-in Contact Form',              value: 'contactForm' },
                { title: 'No button or form',                  value: 'none' },
              ],
              layout: 'radio',
            },
            initialValue: 'button',
          }),
          defineField({
            name: 'embedCode', title: 'Embed Code',
            type: 'text', rows: 6,
            description: 'Paste your Constant Contact, Mailchimp, or other embed code here.',
            hidden: ({ document }) => document?.embedType !== 'code',
          }),

          // Button
          defineField({ name: 'buttonText',   title: 'Button Text',   type: 'string',
            hidden: ({ document }) => document?.embedType === 'code' || document?.embedType === 'none' }),
          defineField({ name: 'buttonUrl',    title: 'Button Link',   type: 'string',
            hidden: ({ document }) => document?.embedType === 'code' || document?.embedType === 'none' }),
          defineField({ name: 'buttonNewTab', title: 'Open in New Tab?', type: 'boolean', initialValue: false,
            hidden: ({ document }) => document?.embedType !== 'button' }),

          // Appearance
          defineField({
            name: 'size', title: 'Popup Size', type: 'string',
            options: { list: ['small','medium','large'], layout: 'radio' },
            initialValue: 'medium',
          }),
          defineField({ name: 'headerColor', title: 'Header Color', type: 'string',
            description: 'CSS color value, e.g. #1E5CA8', initialValue: '#1E5CA8' }),
        ],
        preview: {
          select: { title: 'name', subtitle: 'active', media: 'image' },
          prepare({ title, subtitle, media }) {
            return { title, subtitle: subtitle ? '✅ Active' : '⭕ Hidden', media }
          }
        }
      }),

      // ── PAGE ─────────────────────────────────────────────────
      defineType({
        name: 'page',
        title: 'Page',
        type: 'document',
        fields: [
          defineField({ name: 'title',   title: 'Page Title',  type: 'string', validation: R => R.required() }),
          defineField({ name: 'slug',    title: 'URL Slug',    type: 'slug', options: { source: 'title' }, validation: R => R.required() }),
          defineField({ name: 'excerpt', title: 'Short Description (for SEO)', type: 'text', rows: 2 }),
          defineField({
            name: 'status', title: 'Status', type: 'string',
            options: { list: ['draft','published','archived'], layout: 'radio' },
            initialValue: 'draft',
          }),
          defineField({
            name: 'featuredImage', title: 'Featured Image', type: 'image',
            options: { hotspot: true },
            fields: [defineField({ name: 'alt', title: 'Alt Text', type: 'string' })],
          }),
          defineField({
            name: 'content', title: 'Page Content', type: 'array',
            of: [
              { type: 'block' },
              {
                type: 'image', options: { hotspot: true },
                fields: [
                  defineField({ name: 'alt',     title: 'Alt Text',  type: 'string' }),
                  defineField({ name: 'caption', title: 'Caption',   type: 'string' }),
                ],
              },
              {
                name: 'youtubeEmbed', type: 'object', title: 'YouTube Video',
                fields: [
                  defineField({ name: 'url',   title: 'YouTube URL', type: 'url' }),
                  defineField({ name: 'title', title: 'Video Title', type: 'string' }),
                ],
                preview: { select: { title: 'title', subtitle: 'url' } }
              },
            ],
          }),
          defineField({ name: 'metaTitle', title: 'SEO Title (optional)',       type: 'string' }),
          defineField({ name: 'metaDesc',  title: 'SEO Description (optional)', type: 'text', rows: 2 }),
        ],
        preview: {
          select: { title: 'title', subtitle: 'status', media: 'featuredImage' },
        }
      }),

      // ── NEWS ARTICLE ─────────────────────────────────────────
      defineType({
        name: 'newsArticle',
        title: 'News Article',
        type: 'document',
        fields: [
          defineField({ name: 'title',       title: 'Title',        type: 'string', validation: R => R.required() }),
          defineField({ name: 'slug',        title: 'URL Slug',     type: 'slug', options: { source: 'title' } }),
          defineField({ name: 'publishedAt', title: 'Published Date', type: 'datetime' }),
          defineField({ name: 'excerpt',     title: 'Excerpt',      type: 'text', rows: 3 }),
          defineField({
            name: 'status', title: 'Status', type: 'string',
            options: { list: ['draft','published'], layout: 'radio' }, initialValue: 'draft',
          }),
          defineField({
            name: 'featuredImage', title: 'Featured Image', type: 'image',
            options: { hotspot: true },
            fields: [defineField({ name: 'alt', title: 'Alt Text', type: 'string' })],
          }),
          defineField({
            name: 'content', title: 'Article Content', type: 'array',
            of: [{ type: 'block' }, { type: 'image', options: { hotspot: true } }],
          }),
        ],
        orderings: [{ title: 'Published Date, Newest', name: 'publishedAtDesc', by: [{ field: 'publishedAt', direction: 'desc' }] }],
      }),

      // ── EVENT ────────────────────────────────────────────────
      defineType({
        name: 'event',
        title: 'Calendar Event',
        type: 'document',
        fields: [
          defineField({ name: 'title',       title: 'Event Title', type: 'string', validation: R => R.required() }),
          defineField({ name: 'description', title: 'Short Description', type: 'text', rows: 3 }),
          defineField({ name: 'startDate',   title: 'Start Date & Time', type: 'datetime', validation: R => R.required() }),
          defineField({ name: 'endDate',     title: 'End Date & Time (optional)', type: 'datetime' }),
          defineField({ name: 'allDay',      title: 'All-day Event?', type: 'boolean', initialValue: false }),
          defineField({ name: 'location',    title: 'Location', type: 'string', description: 'e.g. 300 Jefferson Blvd or "Virtual (Zoom)"' }),
          defineField({ name: 'locationUrl', title: 'Google Maps Link (optional)', type: 'url' }),
          defineField({ name: 'registrationUrl', title: 'Registration Link (optional)', type: 'url' }),
          defineField({
            name: 'category', title: 'Category', type: 'string',
            options: {
              list: ['Workshop','Webinar','Support Group','Meeting','Conference','Training','Community Event','Other'],
              layout: 'dropdown',
            },
          }),
          defineField({ name: 'color',    title: 'Calendar Color', type: 'string',
            description: 'Hex color, e.g. #1E5CA8', initialValue: '#1E5CA8' }),
          defineField({ name: 'featured', title: 'Featured on Homepage?', type: 'boolean', initialValue: false }),
          defineField({
            name: 'status', title: 'Status', type: 'string',
            options: { list: ['draft','published','cancelled'], layout: 'radio' }, initialValue: 'published',
          }),
          defineField({
            name: 'content', title: 'Full Event Details (optional)', type: 'array',
            of: [{ type: 'block' }, { type: 'image', options: { hotspot: true } }],
          }),
        ],
        orderings: [{ title: 'Start Date', name: 'startDateAsc', by: [{ field: 'startDate', direction: 'asc' }] }],
        preview: {
          select: { title: 'title', subtitle: 'startDate', status: 'status' },
          prepare({ title, subtitle, status }) {
            const date = subtitle ? new Date(subtitle).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : ''
            return { title, subtitle: `${date} · ${status}` }
          }
        }
      }),

      // ── RESOURCE CATEGORY ────────────────────────────────────
      defineType({
        name: 'resourceCategory',
        title: 'Resource Category',
        type: 'document',
        fields: [
          defineField({ name: 'name',      title: 'Category Name', type: 'string', validation: R => R.required() }),
          defineField({ name: 'slug',      title: 'Slug',          type: 'slug', options: { source: 'name' } }),
          defineField({ name: 'sortOrder', title: 'Sort Order',    type: 'number', initialValue: 0 }),
        ],
      }),

      // ── RESOURCE ─────────────────────────────────────────────
      defineType({
        name: 'resource',
        title: 'Resource',
        type: 'document',
        fields: [
          defineField({ name: 'title',       title: 'Resource Title',      type: 'string', validation: R => R.required() }),
          defineField({ name: 'description', title: 'Short Description',   type: 'text', rows: 3 }),
          defineField({
            name: 'resourceType', title: 'Resource Type', type: 'string',
            options: {
              list: [
                { title: '📄 PDF Document', value: 'PDF' },
                { title: '🎥 Video',        value: 'VIDEO' },
                { title: '🔗 External Link',value: 'LINK' },
                { title: '📝 Document',     value: 'DOCUMENT' },
              ],
              layout: 'radio',
            },
            validation: R => R.required(),
          }),
          defineField({
            name: 'file', title: 'Upload File (PDF or Document)', type: 'file',
            hidden: ({ document }) => !['PDF','DOCUMENT'].includes(document?.resourceType),
          }),
          defineField({
            name: 'url', title: 'URL (for Video or Link)', type: 'url',
            description: 'For videos: paste YouTube or Vimeo URL. For links: paste the website URL.',
            hidden: ({ document }) => !['VIDEO','LINK'].includes(document?.resourceType),
          }),
          defineField({
            name: 'image', title: 'Thumbnail Image (optional)', type: 'image',
            options: { hotspot: true },
          }),
          defineField({
            name: 'language', title: 'Language', type: 'string',
            options: { list: ['en','es','pt','other'], layout: 'dropdown' },
            initialValue: 'en',
          }),
          defineField({
            name: 'categories', title: 'Categories', type: 'array',
            of: [{ type: 'reference', to: [{ type: 'resourceCategory' }] }],
          }),
          defineField({ name: 'featured', title: 'Featured Resource?', type: 'boolean', initialValue: false }),
          defineField({
            name: 'status', title: 'Status', type: 'string',
            options: { list: ['draft','published','archived'], layout: 'radio' },
            initialValue: 'published',
          }),
        ],
        preview: {
          select: { title: 'title', subtitle: 'resourceType', media: 'image' },
          prepare({ title, subtitle, media }) {
            const icons = { PDF:'📄', VIDEO:'🎥', LINK:'🔗', DOCUMENT:'📝' }
            return { title, subtitle: `${icons[subtitle] || ''} ${subtitle}`, media }
          }
        }
      }),

      // ── TESTIMONIAL ──────────────────────────────────────────
      defineType({
        name: 'testimonial',
        title: 'Testimonial',
        type: 'document',
        fields: [
          defineField({ name: 'quote',    title: 'Quote',    type: 'text',   rows: 4, validation: R => R.required() }),
          defineField({ name: 'name',     title: 'Name',     type: 'string', description: 'First name or initials only if preferred' }),
          defineField({ name: 'location', title: 'Location', type: 'string', description: 'e.g. "Providence, RI"' }),
          defineField({ name: 'service',  title: 'Service',  type: 'string', description: 'e.g. "Special Education"' }),
          defineField({ name: 'active',   title: 'Show on Site?', type: 'boolean', initialValue: true }),
        ],
        preview: {
          select: { title: 'name', subtitle: 'quote' },
          prepare({ title, subtitle }) {
            return { title: title || 'Anonymous', subtitle: subtitle?.substring(0, 80) + '…' }
          }
        }
      }),

    ]
  }
})
