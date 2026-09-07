---
name: Sumi Editorial
colors:
  surface: '#131318'
  surface-dim: '#131318'
  surface-bright: '#39383e'
  surface-container-lowest: '#0e0e13'
  surface-container-low: '#1b1b20'
  surface-container: '#1f1f24'
  surface-container-high: '#2a292f'
  surface-container-highest: '#35343a'
  on-surface: '#e4e1e9'
  on-surface-variant: '#e4beb9'
  inverse-surface: '#e4e1e9'
  inverse-on-surface: '#303035'
  outline: '#ab8985'
  outline-variant: '#5b403d'
  surface-tint: '#ffb4ac'
  primary: '#ffb4ac'
  on-primary: '#690006'
  primary-container: '#ff544c'
  on-primary-container: '#5c0005'
  inverse-primary: '#bb171c'
  secondary: '#c9c6c0'
  on-secondary: '#31312c'
  secondary-container: '#474742'
  on-secondary-container: '#b7b5af'
  tertiary: '#ffb3ae'
  on-tertiary: '#68000c'
  tertiary-container: '#ff5353'
  on-tertiary-container: '#5c0009'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#ffdad6'
  primary-fixed-dim: '#ffb4ac'
  on-primary-fixed: '#410002'
  on-primary-fixed-variant: '#93000d'
  secondary-fixed: '#e5e2db'
  secondary-fixed-dim: '#c9c6c0'
  on-secondary-fixed: '#1c1c18'
  on-secondary-fixed-variant: '#474742'
  tertiary-fixed: '#ffdad7'
  tertiary-fixed-dim: '#ffb3ae'
  on-tertiary-fixed: '#410004'
  on-tertiary-fixed-variant: '#930015'
  background: '#131318'
  on-background: '#e4e1e9'
  surface-variant: '#35343a'
typography:
  display-hero:
    fontFamily: Plus Jakarta Sans
    fontSize: 44px
    fontWeight: '800'
    lineHeight: 52px
    letterSpacing: -0.03em
  display-hero-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '800'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '700'
    lineHeight: 28px
    letterSpacing: -0.015em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: -0.01em
  title-editorial:
    fontFamily: Plus Jakarta Sans
    fontSize: 15px
    fontWeight: '700'
    lineHeight: 20px
    letterSpacing: 0.01em
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
    letterSpacing: 0.01em
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
    letterSpacing: 0em
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
    letterSpacing: 0.01em
  counter-metric:
    fontFamily: Space Grotesk
    fontSize: 16px
    fontWeight: '700'
    lineHeight: 20px
    letterSpacing: 0.02em
  label-badge:
    fontFamily: Space Grotesk
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.08em
  label-caption:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.02em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  space-2xs: 0.25rem
  space-xs: 0.5rem
  space-sm: 0.75rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
  space-2xl: 3rem
  space-3xl: 4rem
  gutter-mobile: 1rem
  gutter-desktop: 1.5rem
  margin-mobile: 1rem
  margin-tablet: 2rem
  margin-desktop: 3rem
  poster-aspect-ratio: 2 / 3
---

## Brand & Style

This design system embodies an editorial, dark-canvas experience tailored for discerning manga enthusiasts, collectors, and casual readers alike. It marries the disciplined, deliberate craft of traditional Japanese inkwork (*sumi-e*) with modern, bespoke media tracking utilities (in the vein of Letterboxd and TV Time).

### Personality & Tone
- **Atmospheric & Immersive:** Dark, velvety backdrops that allow high-contrast cover illustrations and monochrome manga panels to take center stage without visual competition.
- **Editorial Rigor:** Clean type pairings, measured structural layout, and spacious hierarchy inspired by contemporary Tokyo publication house design.
- **Surgical Energy:** A restrained, high-octane crimson accent derived from the vermilion seals (*hanko*) and rising sun, deployed strictly as focal beacons (unread chapter badges, primary updates, reading progression).

### Design Style: Modern Neo-Editorial
A fusion of dark minimalism and tactile editorial depth. The aesthetic relies on deep charcoal slate tiers rather than harsh grays, creamy warm-paper highlights that reduce eye strain during late-night tracking sessions, and razor-sharp structural outlines reminiscent of manga panel gutters (*koma*).

## Colors

The palette is engineered around dark-mode comfort, tonal layering, and high-impact semantic signifiers.

### Canvas & Surface Architecture (Sumi Inks)
- **Base Canvas (`#0D0D12`):** Pure deep obsidian slate; the bottom-most viewport surface.
- **Surface 01 (`#14141C`):** Primary card bodies, navigation rail, and bottom sheets.
- **Surface 02 (`#1B1B26`):** Elevated overlays, modal drawers, hover tiers, and nested lists.
- **Surface 03 / Border (`#262636`):** Subdued ghost borders, progress track backgrounds, and divider rules.

### Brand Accent (Vermilion Hanko)
- **Primary Red (`#E53935`):** The primary brand signifier. Used strictly for "Mark Read", active progress indicators, and key action points.
- **Primary Hover / Neon Red (`#FF5252`):** Interactive hover states, active glow spots, and live release dots.
- **Deep Red Shade (`#991B1B`):** Backgrounds for high-urgency notifications or subtle radial glows beneath active reading streaks.

### Typography & Paper Neutrals (Washi Paper)
- **Washi High-Emphasis (`#F4F1EA`):** Off-white, warm cream reminiscent of high-grade manga stock. Used for titles, chapter counts, and active numbers.
- **Washi Medium-Emphasis (`#B8B4AE`):** Secondary metadata, author names, serialized magazines, and synopsis body text.
- **Ink Ghost (`#6E6D7A`):** Inactive states, metadata captions, empty states, and placeholder text.

## Typography

The typographic system utilizes **Plus Jakarta Sans** for structural prose and titles, providing a contemporary, geometric humanism that remains legible on dark surfaces. For tabular tracking, chapter progress, and metadata stamps (e.g., `CAP. 87 / 104`), **Space Grotesk** is deployed to introduce a technical, collector-grade precision.

### Typographic Rules
- **Numerical Alignment:** All chapter counts, volume progress, and rating scores must leverage tabular figures or the `counter-metric` token to ensure clean scanability across dense grid columns.
- **Editorial Contrast:** Section headers (e.g., *LENDO ATUALMENTE*, *EM ALTA NA COMUNIDADE*) utilize uppercase casing paired with extended letter spacing (`0.08em`) to mirror Japanese design magazine mastheads.
- **Readability on Dark Canvases:** Never use pure white (`#FFFFFF`) for long-form synopsis text; use `Washi Medium-Emphasis` (`#B8B4AE`) to prevent eye fatigue.

## Layout & Spacing

The layout is built on an editorial fluid-grid foundation, paced by 4px and 8px step multiples to echo the rhythmic gutters between comic strip panels.

### Structural Grid Architecture
- **Mobile (<768px):** 4-column layout, 16px margins, 12px gutters. Manga covers render as high-density 2-column or 3-column rows, maximizing vertical scroll efficiency.
- **Tablet (768px - 1024px):** 8-column layout, 24px margins, 16px gutters. Allows split views between the reading queue and community feeds.
- **Desktop (>1024px):** 12-column layout with a max content envelope of 1360px, 32px margins, 24px gutters. Standard shelf views feature 5 to 6 manga cards per row.

### Shelf & Poster Proportions
All manga cover art elements strictly maintain a **2:3 aspect ratio** (`poster-aspect-ratio`). Cards must never crop or distort cover art width; typography and progress widgets anchor directly below or layer over a delicate bottom gradient scrim (`rgba(13, 13, 18, 0.9)`).

## Elevation & Depth

Visual hierarchy does not rely on heavy drop shadows, which can muddy dark interfaces. Instead, the design system employs **tonal layering**, **fine hairline borders**, and **subtle sumi-e wash gradients**.

### The Tonal Stack
1. **Backdrop Canvas (`#0D0D12`):** Base workspace for feeds, background gutters, and empty space.
2. **Card Surfaces (`#14141C`):** Clipped with a 1px border (`#262636` at 60% opacity). Rises 1 level above canvas.
3. **Floating Controls & Modals (`#1B1B26`):** Used for quick log actions, filter panels, and dropdown menus. Accompanied by a diffused, dark ambient shadow: `0 12px 32px -4px rgba(0, 0, 0, 0.65)`.
4. **Primary Glow (Focus State):** When an item is selected or tracking is completed, an ambient crimson radial aura may be cast behind the element: `0 0 24px -4px rgba(229, 57, 53, 0.35)`.

### Hairline Outlines (Koma Lines)
All modular content boxes leverage a crisp 1px outline with low opacity (`rgba(255, 255, 255, 0.07)`), evoking the fine ink borders of manga panel layouts.

## Shapes

The design system employs a **Soft (Level 1)** roundedness system. Sharp, pure rectangular forms can feel clinical, while overly rounded bubbly shapes clash with the disciplined precision of Japanese print editorial layouts.

### Geometry Standards
- **Manga Cards:** 8px (`rounded-md` / `0.5rem`) corner radius. Subtle curvature that softens the book format while preserving rectangular frame dignity.
- **Buttons & Chips:** 6px to 8px. Compact, confident, structural.
- **Badges & Status Tags:** 4px (`rounded-sm` / `0.25rem`) for compact tags (e.g., `LENDO`, `HIATUS`).
- **Interactive Quick-Increment Counters:** 6px radius for seamless thumb targeting.

## Components

### Buttons
- **Primary Action (e.g., "Marcar Lido +1", "Começar Leitura"):** Solid `#E53935` background with `#F4F1EA` bold text. On hover, shifts to `#FF5252` with a subtle 4px crimson ambient glow. Compact padding: 8px 16px; font: `Space Grotesk` medium.
- **Secondary Action (e.g., "Ver Detalhes", "Adicionar à Lista"):** `#1B1B26` fill, 1px hairline border `#262636`, `#F4F1EA` text. On hover, background shifts to `#222230` with border brightening to `rgba(255, 255, 255, 0.15)`.
- **Ghost / Icon Action:** Transparent background with `#B8B4AE` iconography; transitions to `#F4F1EA` on hover with a faint `#1B1B26` pill background.

### Manga Tracking Card
- **Frame:** 2:3 vertical aspect ratio cover wrapper with 8px radius.
- **Cover Overlay:** Top-right anchored floating status tag; bottom gradient scrim protecting the title and chapter progress display.
- **Progress Track:** Integrated into the base of the card as a 3px ultra-thin rail. Track background: `rgba(255, 255, 255, 0.12)`; active fill: `#E53935`.
- **Quick-Advance Control:** A dedicated `+1` increment button situated adjacent to the chapter progress metric (`Cap. 87 / 104`), allowing zero-latency updates directly from feed or shelf views.

### Status Badges & Chips
- **Status Tiers:**
  - *Lendo (Reading):* Dark crimson tint background (`rgba(229, 57, 53, 0.15)`), border `rgba(229, 57, 53, 0.4)`, text `#FF5252`.
  - *Lido (Completed):* Deep emerald slate (`rgba(16, 185, 129, 0.12)`), text `#34D399`.
  - *Pretendo Ler (Plan to Read):* Neutral slate (`rgba(255, 255, 255, 0.06)`), text `#B8B4AE`.
  - *Pausado / Dropado (On Hold / Dropped):* Dark amber or muted gray (`rgba(245, 158, 11, 0.12)`), text `#FBBF24`.
- **Badge Anatomy:** Rendered in `Space Grotesk` at 11px with uppercase tracking (`letter-spacing: 0.08em`).

### Progress Trackers
- A minimal horizontal line (height: 4px; radius: 2px) positioned below entry titles.
- Accompanied by editorial text: left-aligned current volume/chapter, right-aligned percentage or remaining chapters in `counter-metric` font token.

### Input Fields & Search
- Surface: `#14141C` with 1px border `#262636`.
- Typography: `#F4F1EA` body, placeholder in `#6E6D7A`.
- Active Focus State: Hairline border shifts to `#E53935` with no heavy outer glow ring, maintaining crisp editorial refinement.