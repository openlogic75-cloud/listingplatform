# Design tokens reference

Single source of truth for design tokens across the three surfaces. Code files may reference these values only via their token files - never inline literals in components.

## Website (public, Blade) - source: `ui deisgns/website ui/minimalist-swiss-design.md`

| Token | Value | Usage |
|---|---|---|
| `--color-surface` | `#FFFFFF` | Page background |
| `--color-surface-alt` | `#F8F8F8` | Section background, muted surfaces |
| `--color-accent` | `#007BFF` | Links, focus states, primary buttons (interactive only) |
| `--color-accent-hover` | `#0069D9` | Primary button hover |
| `--color-ink` | `#212529` | Primary text (never `#000000`) |
| `--color-muted` | `#6C757D` | Secondary text, borders |
| `--color-success` | `#28A745` | Positive states |
| `--color-warning` | `#FFC107` | Caution states |
| `--color-danger` | `#DC3545` | Errors, destructive |
| `--font-sans` | Inter + system fallbacks | All text (400/500/600/700) |
| `--radius-sm` | 4px | Buttons, inputs, cards |
| `--space-unit` | 8px | All spacing multiples |
| container | 1280px + 24px side padding | Page shell |
| z-index | nav 100 / overlay 200 / modal 300 / toast 500 | Contract |

## Dashboard (admin + role dashboards, Blade) - source: `ui deisgns/dashboard ui/genesis-DESIGN.md`

| Token | Value | Usage |
|---|---|---|
| `--adm-bg` | `#FAFAFA` | Page background |
| `--adm-surface` | `#FFFFFF` | Cards, panels |
| `--adm-interactive` | `#6366F1` | CTAs, active states, links, focus rings ONLY (never decoration) |
| `--adm-interactive-hover` | `#4F46E5` | Interactive hover |
| `--adm-ink` | `#0A0A0A` | Headings, body |
| `--adm-ink-secondary` | `#6B6B6B` | Metadata, descriptions |
| `--adm-muted` | `#9C9C9C` | Placeholders, timestamps, disabled |
| `--adm-border` | `#E8E8EC` | Card/divider/input borders |
| `--adm-success` | `#10B981` | Published, confirmations |
| `--adm-warning` | `#F59E0B` | Pending states |
| `--adm-danger` | `#EF4444` | Destructive, rejected |
| `--adm-font-display` | General Sans (Fontshare) | Headings, tight tracking -0.03em |
| `--adm-font-body` | DM Sans (Google Fonts) | Body, UI text |
| `--adm-radius-button` | 6px | Buttons, inputs, selects |
| `--adm-radius-card` | 12px | Cards, panels |
| spacing | 4px base grid: 4/8/12/16/20/24/32/40/48/64/80/96 | All padding, margins, gaps |

## Mobile (Flutter) - source: Material 3 + brand seed

| Token | Value | Usage |
|---|---|---|
| seed color | `0xFF007BFF` | `ColorScheme.fromSeed` light + dark |
| success | `0xFF28A745` | Positive states |
| warning | `0xFFF59E0B` | Caution states |
| danger | `0xFFDC3545` | Errors, destructive |
| font | Inter via `google_fonts` | Full type scale |
| spacing | 4/8/12/16/24/32/48 (`Spacing` in `tokens.dart`) | All layout |
| radius | 4/8/12 (`AppRadius` in `tokens.dart`) | Buttons 8, inputs 8, cards 12 |
| touch targets | >= 48dp | Accessibility floor |

## Hard rules (all surfaces)

1. Accent/interactive colors mark interaction and status only - never decoration.
2. Never use pure black `#000000` for text; use the ink tokens above.
3. No emojis anywhere in UI. Icons come from `assets/icons/` (outline default, filled only for active/selected states), recolored via `currentColor` / theme tint.
4. Shadows subtle only; elevation communicated on hover/press, not on static elements.
5. Motion: 150-250ms ease-out on transform/opacity only.
