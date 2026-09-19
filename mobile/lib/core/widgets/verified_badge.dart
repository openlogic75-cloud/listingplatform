import 'package:flutter/material.dart';

import '../theme/tokens.dart';

/// "Verified by <volunteer>" badge (M5.3).
///
/// The volunteer's name is a snapshot taken when the badge was issued on the
/// backend, so it survives volunteer account deletion (DPDP). The site-visit
/// fee (M5.4) is paid directly to the volunteer at the visit - the platform
/// never handles payment.
class VerifiedBadge extends StatelessWidget {
  const VerifiedBadge({
    super.key,
    required this.volunteerName,
    this.verifiedAt,
    this.compact = false,
  });

  /// Snapshot name shown to buyers. Null renders "Verified on site".
  final String? volunteerName;

  final DateTime? verifiedAt;

  /// Compact form is a single chip for listing cards.
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);
    final String label = _label();

    if (compact) {
      return Container(
        padding: const EdgeInsets.symmetric(
          horizontal: Spacing.sm,
          vertical: Spacing.xs,
        ),
        decoration: BoxDecoration(
          color: theme.colorScheme.primaryContainer,
          borderRadius: BorderRadius.circular(AppRadius.pill),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            Icon(
              Icons.verified_outlined,
              size: 16,
              color: theme.colorScheme.onPrimaryContainer,
            ),
            const SizedBox(width: Spacing.xs),
            Text(
              label,
              style: theme.textTheme.labelMedium?.copyWith(
                color: theme.colorScheme.onPrimaryContainer,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(Spacing.md),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Icon(Icons.verified_outlined, color: theme.colorScheme.primary),
            const SizedBox(width: Spacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    'Verified on site',
                    style: theme.textTheme.titleSmall
                        ?.copyWith(fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: Spacing.xs),
                  Text(
                    label,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: Spacing.xs),
                  Text(
                    'A trained volunteer visited and reported on this listing. '
                    'The site-visit fee is paid directly to the volunteer.',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _label() {
    final String who = volunteerName?.trim().isNotEmpty == true
        ? volunteerName!.trim()
        : 'a field volunteer';

    if (verifiedAt == null) {
      return 'Verified by $who';
    }

    final DateTime at = verifiedAt!;
    final String date = '${at.day.toString().padLeft(2, '0')}/'
        '${at.month.toString().padLeft(2, '0')}/${at.year}';

    return 'Verified by $who on $date';
  }
}

/// Small inline marker used next to a vendor name in headers.
class VerifiedPill extends StatelessWidget {
  const VerifiedPill({super.key, this.tooltip});

  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Tooltip(
      message: tooltip ?? 'Verified on site',
      child: Icon(
        Icons.verified_outlined,
        size: 18,
        color: theme.colorScheme.primary,
        semanticLabel: 'Verified',
      ),
    );
  }
}