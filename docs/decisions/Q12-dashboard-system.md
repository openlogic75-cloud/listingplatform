# Q12 - Dashboard UI system

- Status: decided (2026-09-05)
- Task: M0.2, M0.4

## Context

Three candidate dashboard design systems exist in `ui deisgns/dashboard ui/`. One must be adopted for the admin dashboard and role dashboards (vendor, driver).

## Options

1. `genesis-DESIGN.md` - editorial precision platform dashboard, indigo strictly for interactive elements, flat cards, hover lift (recommended)
2. `verdana-health-design-system-DESIGN.md` - calm clinical, navy + sage
3. `design-md-sistema-de-monitoreo-el-ctrico-wattvision-DESIGN.md` - dark, data-dense KPI skin

## Decision

Adopt **genesis-DESIGN.md** for all dashboards. Its rule "indigo only for interactive elements" keeps an operations tool quiet, and the flat card system renders well on Hostinger without heavy CSS.

## Consequences

- Dashboard tokens live in `backend/resources/css/admin-tokens.css` (see `docs/design/tokens.md`).
- Typography: General Sans (Fontshare) for display, DM Sans (Google Fonts) for body.
- Wattvision is deferred as a possible dark ops/KPI skin for a later phase.
- No emojis in UI: icons come from the SVG icon library only.
