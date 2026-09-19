---
version: "alpha"
name: "Minimalismo Funcional B2B"
description: "Clean and professional minimalist landing page for a B2B project management platform. Ideal for landing pages, modern websites. AI-ready template."
colors:
  primary: "#FFFFFF"
  secondary: "#F8F8F8"
  tertiary: "#007BFF"
  neutral: "#212529"
  surface: "#28A745"
  accent: "#FFC107"
typography:
  h1:
    fontFamily: Inter
    fontSize: 2.5rem
    fontWeight: 700
  body-md:
    fontFamily: Inter
    fontSize: 1rem
    fontWeight: 400
rounded:
  sm: 4px
  md: 8px
  lg: 12px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.neutral}"
    rounded: "{rounded.sm}"
    padding: 12px
---

## Overview

Clean and professional minimalist landing page for a B2B project management platform. Ideal for landing pages, modern websites. AI-ready template. Before Linear shipped in 2019, B2B software looked like it hated you. Dense tables, chrome-heavy toolbars, that particular shade of enterprise blue nobody asked for. Basecamp had planted a flag for simplicity years earlier, but it was Linear that proved you could build project management tooling with the restraint of a Swiss poster — monospace type, generous negative space, keyboard-first interaction. Suddenly the bar moved.

Notion took a different route to the same destination. A blank page. Almost nothing on screen until you needed it. Progressive disclosure as aesthetic philosophy. Asana followed with its 2021 redesign, stripping decades of accumulated UI cruft. The pattern was clear: productive software should feel like a well-organized desk, not a cockpit.

What emerged wasn't minimalism for minimalism's sake. It was functional reduction — every pixel earning its place through utility. Color became semantic rather than decorative. Typography carried hierarchy alone. The tools that won weren't prettier; they were quieter. They got out of the way.

- Density: 3/10 — Airy
- Variance: 2/10 — Structured
- Motion: 4/10 — Subtle

- **Style:** Clean, Professional, Minimalist
- **Keywords:** B2B, software, project management, clean, minimalist, efficient, professional, intuitive, data-driven, modern
- **Era:** 2026+ Produtividade Digital
- **Light/Dark:** ✓ Full / ✗ No

## Colors

- **Branco** (#FFFFFF) — Light surface, card backgrounds
- **Cinza Claro** (#F8F8F8) — Secondary text, borders, muted elements
- **Azul Corporativo** (#007BFF) — Accent highlight, links and focus states
- **Preto** (#212529) — Dark surface, primary background
- **Verde Suave** (#28A745) — Success states, positive indicators
- **Amarelo Mostarda** (#FFC107) — Warning states, attention indicators
- **Vermelho Suave** (#DC3545) — Error states, destructive actions
- **Cinza Médio** (#6C757D) — Secondary text, borders, muted elements


## Typography

- **Display / Hero:** Inter — Weight 700, tight tracking, used for headline impact
- **Body:** Inter — Weight 400, 16px/1.6 line-height, max 72ch per line
- **UI Labels / Captions:** Inter — 0.875rem, weight 500, slight letter-spacing
- **Monospace:** JetBrains Mono — Used for code, metadata, and technical values

Scale:
- Hero: clamp(2.5rem, 5vw, 4rem)
- H1: 2.25rem
- H2: 1.5rem
- Body: 1rem / 1.6
- Small: 0.875rem


## Layout

- **Grid:** CSS Grid primary. Max-width containment: 1280px centered with 1.5rem side padding.
- **Spacing rhythm:** Balanced. Base unit: 0.5rem (8px).
- **Section vertical gaps:** clamp(4rem, 8vw, 8rem).
- **Hero layout:** Split-screen (text left, visual right).
- **Feature sections:** Zig-zag alternating text+image rows. No 3-equal-columns.
- **Mobile collapse:** All multi-column layouts collapse below 768px. No horizontal overflow.
- **z-index contract:** base (0) / sticky-nav (100) / overlay (200) / modal (300) / toast (500).


## Elevation & Depth

Espaço em branco abundante, tipografia sans-serif limpa, ícones minimalistas, micro-interações sutis, sombras suaves, transições fluidas, foco na legibilidade e clareza.

- **Physics:** Ease-out curves, 200-300ms duration. Smooth and predictable.
- **Entry animations:** Fade + translate-Y (16px → 0) over 420ms ease-out. Staggered cascades for lists: 80ms between items.
- **Hover states:** Subtle color shift + shadow adjustment over 200ms.
- **Page transitions:** Fade only (200ms).
- **Performance:** Only transform and opacity animated. No layout-triggering properties.


## Shapes

Base corner radius: 4px. See rounded tokens in front matter for the full scale.


## Components

- **Primary Button:** Rounded (4px) shape. Accent color fill. Hover: 8% darken + subtle lift shadow. Active: -1px translate tactile press. Font weight 600. No outer glows.
- **Secondary / Ghost Button:** Outline variant. 1.5px border in muted color. Text in primary color. Hover: subtle background fill.
- **Cards:** Rounded (4px) corners. Surface background. Subtle shadow (0 2px 12px rgba(0,0,0,0.06)). 1px border stroke.
- **Inputs:** Label above input. 1px border stroke. Focus ring: 2px accent color offset 2px. Error text below in semantic red. No floating labels.
- **Navigation:** Primary surface background. Active item: accent color indicator. Font weight 500 when active.
- **Skeletons:** Shimmer animation matching component dimensions. No circular spinners.
- **Empty States:** Icon-based composition with descriptive text and action button.


## Do's and Don'ts

- No emojis in UI — use icon system only (Lucide, Heroicons)
- No decorative gradients — flat color only
- No shadows heavier than 0 2px 8px rgba(0,0,0,0.08)
- No pure black (#000000) — use off-black or charcoal variants
- No oversaturated accent colors (saturation cap: 80%)
- No 3-column equal-width feature layouts — use zig-zag or asymmetric grid
- No `h-screen` — use `min-h-[100dvh]`
- No AI copywriting clichés: "Elevate", "Seamless", "Unleash", "Next-Gen"
- No broken external image links — use picsum.photos or inline SVG
- No generic lorem ipsum in demos

- Do Espaço em branco abundante
- Do Tipografia sans-serif limpa
- Do Ícones minimalistas
- Do Micro-interações sutis
- Do Sombras suaves
- Do Foco na legibilidade.


## Use Case

Landing pages, Modern websites

<!-- Source: https://designmd.app/library/minimalismo-funcional-b2b · designmd.app -->
