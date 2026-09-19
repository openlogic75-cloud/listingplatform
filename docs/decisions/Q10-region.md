# Q10 - Region, currency, language → Nagaland only

- Status: decided (2026-09-18)
- Task: M8.4, M8.5, M4.1

## Decision

The platform operates in **India, Nagaland only** (launch region and the only supported platform region):

- Currency: **₹ (INR)**.
- Language: English with local dialects (Nagamese and others) as copy needs grow — copy source stays single-sourced so a locale layer can be added later without rework.
- Seed data (M4.1): Nagaland's districts (+ their localities).

## Why

Narrow, intentionally thin launch surface: locality-based logistics and ground-truth verification work best with a small, known geography. Keeping IDs (not names) as references means later regions are additive, not a rewrite.

## Consequences

- `DistrictLocalitySeeder` seeds Nagaland districts for the first region.
- No multi-region, multi-currency, or multi-language logic anywhere in the schema today — regions/districts already reference IDs.
- Launch checklist (M8.5) seeds Nagaland; ₹ formatting throughout.