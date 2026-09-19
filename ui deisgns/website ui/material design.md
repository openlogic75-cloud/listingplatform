---
version: "alpha"
name: "Estilo Material AI"
description: "Clean and vibrant landing page for a generative AI platform. Ideal for landing pages, modern websites. AI-ready template."
colors:
  primary: "#4285F4"
  secondary: "#EA4335"
  tertiary: "#FBBC05"
  neutral: "#34A853"
  surface: "#FFFFFF"
  accent: "#F8F9FA"
typography:
  h1:
    fontFamily: Roboto
    fontSize: 2.5rem
    fontWeight: 700
  body-md:
    fontFamily: Roboto
    fontSize: 1rem
    fontWeight: 400
rounded:
  sm: 8px
  md: 16px
  lg: 24px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.neutral}"
    rounded: "{rounded.sm}"
    padding: 12px
---

## Overview

Clean and vibrant landing page for a generative AI platform. Ideal for landing pages, modern websites. AI-ready template. Material Design was always opinionated about physics. Surfaces had weight, shadows told you where things lived in z-space, motion followed real-world curves. Then Google did something interesting — they stopped pretending the screen was paper.

Material You introduced dynamic color extraction, pulling palettes from wallpapers and user preferences. Surfaces became adaptive. But the real shift came when Gemini needed a face. AI features couldn't just inherit the same button styles and card layouts. They needed something that communicated intelligence without pretending to be human. The shimmer effects, the generative text animations, the way responses build themselves on screen — that's a distinct visual language growing inside Material's skeleton.

The adaptive color system now serves double duty: personal expression for the user, semantic signaling for AI states. Processing looks different from responding. Confidence has a color. Uncertainty has one too. It's Material Design learning to speak a new dialect.

- Density: 3/10 — Airy
- Variance: 3/10 — Restrained
- Motion: 4/10 — Subtle

- **Style:** Clean, Vibrant, User-Friendly
- **Keywords:** AI, generative AI, cloud, developer, intuitive, vibrant, clean, modern
- **Era:** 2026+ AI-First
- **Light/Dark:** ✓ Full / ✗ No (com opções de tema)

## Colors

- **Azul Vibrante** (#4285F4) — Accent highlight, links and focus states
- **Vermelho Ousado** (#EA4335) — Error states, destructive actions
- **Amarelo Energético** (#FBBC05) — Warning states, attention indicators
- **Verde Vistoso** (#34A853) — Supporting palette color
- **Branco** (#FFFFFF) — Secondary surface
- **Cinza Claro** (#F8F9FA) — Secondary text, borders, muted elements
- **Cinza Escuro** (#3C4043) — Deep contrast surface
- **Ciano** (#00BCD4) — Extended palette, decorative use


## Typography

- **Display / Hero:** Roboto — Weight 700, tight tracking, used for headline impact
- **Body:** Roboto — Weight 400, 16px/1.6 line-height, max 72ch per line
- **UI Labels / Captions:** Roboto — 0.875rem, weight 500, slight letter-spacing
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

Sombras sutis (Material Design), gradientes dinâmicos, micro-interações responsivas, tipografia legível (sans-serif), elementos flutuantes, animações de carregamento de IA, ilustrações abstratas de dados.

- **Physics:** Ease-out curves, 200-300ms duration. Smooth and predictable.
- **Entry animations:** Fade + translate-Y (16px → 0) over 420ms ease-out. Staggered cascades for lists: 80ms between items.
- **Hover states:** Subtle color shift + shadow adjustment over 200ms.
- **Page transitions:** Fade only (200ms).
- **Performance:** Only transform and opacity animated. No layout-triggering properties.


## Shapes

Base corner radius: 8px. See rounded tokens in front matter for the full scale.


## Components

- **Primary Button:** Rounded (8px) shape. Accent color fill. Hover: 8% darken + subtle lift shadow. Active: -1px translate tactile press. Font weight 600. No outer glows.
- **Secondary / Ghost Button:** Outline variant. 1.5px border in muted color. Text in primary color. Hover: subtle background fill.
- **Cards:** Rounded (8px) corners. Surface background. Subtle shadow (0 2px 12px rgba(0,0,0,0.06)). 1px border stroke.
- **Inputs:** Label above input. 1px border stroke. Focus ring: 2px accent color offset 2px. Error text below in semantic red. No floating labels.
- **Navigation:** Primary surface background. Active item: accent color indicator. Font weight 500 when active.
- **Skeletons:** Shimmer animation matching component dimensions. No circular spinners.
- **Empty States:** Icon-based composition with descriptive text and action button.


## Do's and Don'ts

- No emojis in UI — use icon system only (Lucide, Heroicons)
- No pure black (#000000) — use off-black or charcoal variants
- No oversaturated accent colors (saturation cap: 80%)
- No 3-column equal-width feature layouts — use zig-zag or asymmetric grid
- No `h-screen` — use `min-h-[100dvh]`
- No AI copywriting clichés: "Elevate", "Seamless", "Unleash", "Next-Gen"
- No broken external image links — use picsum.photos or inline SVG
- No generic lorem ipsum in demos

- Do Cores vibrantes da marca
- Do Sombras Material Design
- Do Gradientes dinâmicos
- Do Tipografia legível
- Do Animações de IA
- Do Foco no desenvolvedor.


## Use Case

Landing pages, Modern websites

<!-- Source: https://designmd.app/library/estilo-material-ai · designmd.app -->
