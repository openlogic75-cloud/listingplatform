# Q6 - Volunteer TA/DA funding → verification fee

- Status: decided (2026-09-18)
- Task: M5.1, M5.3, M5.4

## Decision

Volunteer travel/TA/DA is funded by a **verification fee** charged to the vendor:

- The admin sets a single fee amount in the **settings dashboard** (M5.4).
- The fee is shown on the listing/vendor page and in the app before a visit is booked.
- The fee goes **directly to the volunteer** who performed the visit, collected at the site — the platform never touches the money (Q1-style offline settlement).
- The fee amount is snapshotted onto the `Badge` at issue time, so later admin changes never rewrite issued history.

## Why

`local-market.md` (verification section) already anticipated charging for verification to cover volunteer travel from cheque: "we charge as our volunteers will have to travel to site". Donations remain for platform operations, not volunteer expenses.

## Consequences

- Verification is now a **paid service**; M5.3's "no commission charged" wording still holds for the platform (zero commission — the fee goes to the volunteer), but the "free" framing is superseded.
- New task M5.4 (verification fee) — settings row, display in web + app, badge fee snapshot.
- Money boundary (AGENTS.md §7) unchanged: no payment credentials stored or processed.