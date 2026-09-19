# Q8 - Maps & geocoding → none required

- Status: decided (2026-09-18)
- Task: M4.6, M1.2

## Decision

**No map SDK, no geocoding, no ETA, no live GPS location.**

Users only need to know **which drivers are online right now in which locality**, derived from the data already stored:

- `driver_availability` (`is_online`, `last_online_at`) — M4.3
- `rider_base_operations` (district + ≤5 localities) — M4.2

Rendered as a per-locality online-driver list/count, refreshed on demand. Status-timeline-only tracking is the tracking story.

## Why

Shows availability honestly without the cost, permissions, or API budget of a mapping stack on shared hosting. A live GPS position adds no value for a marketplace where drivers are matched by locality, not by a moving pin.

## Consequences

- M4.6 rescoped from "Tracking screens + map SDK" to a **driver-availability view (which drivers are online where)**.
- No `google_maps_flutter`, no `flutter_map`, no geocoding/turn-by-turn costs.
- If a map is ever wanted later, this can be revisited without schema changes.