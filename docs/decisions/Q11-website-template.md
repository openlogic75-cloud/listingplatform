# Q11 - Website UI template

- Status: decided (2026-09-05)
- Task: M0.1, M0.4

## Context

Four candidate design-system templates exist in `ui deisgns/website ui/`. One must be adopted as the token source for the public website (Blade + CSS).

## Options

1. `minimalist-swiss-design.md` - clean, professional, semantic color, Inter, radius 4px, zig-zag sections (recommended)
2. `material design.md` - vibrant, Roboto, Material-flavored
3. `design.md` - corporate flat, Lato
4. `fresh white ui.md` - micro-interaction heavy, System UI stack

## Decision

Adopt **minimalist-swiss-design.md** as the website token source. It suits a marketplace that must stay legible on low-end devices and slow connections: semantic color only, airy layout, subtle motion.

## Consequences

- Website tokens live in `backend/resources/css/tokens.css` (see `docs/design/tokens.md`).
- Typography: Inter (Google Fonts) with system fallbacks.
- Base radius 4px; spacing base 8px; container 1280px; z-index contract: base 0 / sticky-nav 100 / overlay 200 / modal 300 / toast 500.
- No emojis in UI: icons come from `assets/icons/` (Tabler-style SVG, outline default, filled for active states).
