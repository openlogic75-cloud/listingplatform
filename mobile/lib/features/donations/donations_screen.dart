import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/theme/tokens.dart';
import 'donations_repository.dart';

/// Donation page (M6.1): shows the admin-set UPI ID and QR. The platform is
/// donation-funded and commission-free, so it stores no payment data at all -
/// this screen only displays what to pay to, and the user's own UPI app does
/// the rest.
class DonationsScreen extends ConsumerWidget {
  const DonationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ThemeData theme = Theme.of(context);
    final AsyncValue<DonationSettings?> settings =
        ref.watch(donationSettingsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Support the platform')),
      body: settings.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (Object error, StackTrace stack) => _message(
          theme,
          'Could not load donation details. Pull down to try again.',
          isError: true,
        ),
        data: (DonationSettings? data) {
          if (data == null || (data.upiId?.isEmpty ?? true)) {
            return _message(
              theme,
              'Donation details are not published yet. Check back soon - the '
              'platform runs on donations, never commission.',
            );
          }
          return _body(context, theme, data);
        },
      ),
    );
  }

  Widget _message(ThemeData theme, String text, {bool isError = false}) {
    return ListView(
      padding: const EdgeInsets.all(Spacing.xl),
      children: <Widget>[
        const SizedBox(height: Spacing.xxl),
        Icon(
          isError ? Icons.error_outline : Icons.volunteer_activism_outlined,
          size: 48,
          color: isError
              ? theme.colorScheme.error
              : theme.colorScheme.onSurfaceVariant,
        ),
        const SizedBox(height: Spacing.md),
        Text(
          text,
          textAlign: TextAlign.center,
          style: theme.textTheme.bodyLarge?.copyWith(
            color: isError ? theme.colorScheme.error : null,
          ),
        ),
      ],
    );
  }

  Widget _body(
    BuildContext context,
    ThemeData theme,
    DonationSettings settings,
  ) {
    return ListView(
      padding: const EdgeInsets.all(Spacing.lg),
      children: <Widget>[
        Text(
          'Keep it free for everyone',
          style: theme.textTheme.headlineSmall
              ?.copyWith(fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: Spacing.sm),
        Text(
          'No commission is charged to vendors, drivers or volunteers. '
          'Running costs are covered by voluntary donations.',
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(height: Spacing.lg),
        if (settings.qrUrl != null && settings.qrUrl!.isNotEmpty)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(Spacing.lg),
              child: Column(
                children: <Widget>[
                  Text('Scan to pay', style: theme.textTheme.titleMedium),
                  const SizedBox(height: Spacing.md),
                  SizedBox(
                    height: 220,
                    child: Image.network(
                      settings.qrUrl!,
                      fit: BoxFit.contain,
                      errorBuilder: (
                        BuildContext context,
                        Object error,
                        StackTrace? stack,
                      ) =>
                          Center(
                        child: Text(
                          'QR image unavailable. Use the UPI ID below.',
                          textAlign: TextAlign.center,
                          style: theme.textTheme.bodySmall,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        const SizedBox(height: Spacing.md),
        Card(
          child: ListTile(
            leading: const Icon(Icons.qr_code_outlined),
            title: const Text('UPI ID'),
            subtitle: Text(
              settings.upiId!,
              style: theme.textTheme.titleSmall
                  ?.copyWith(fontWeight: FontWeight.w600),
            ),
            trailing: IconButton(
              tooltip: 'Copy UPI ID',
              icon: const Icon(Icons.copy_all_outlined),
              onPressed: () async {
                await Clipboard.setData(ClipboardData(text: settings.upiId!));
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('UPI ID copied.')),
                  );
                }
              },
            ),
          ),
        ),
        const SizedBox(height: Spacing.md),
        if (settings.upiDeepLink != null)
          FilledButton.icon(
            onPressed: () => _openUpi(context, settings.upiDeepLink!),
            icon: const Icon(Icons.open_in_new_outlined),
            label: const Text('Open in your UPI app'),
          ),
        const SizedBox(height: Spacing.lg),
        Text(
          'Payments go directly from your UPI app to the account above. This '
          'app never sees, stores or shares your payment details.',
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ],
    );
  }

  Future<void> _openUpi(BuildContext context, String deepLink) async {
    const SnackBar fallback = SnackBar(
      content: Text('No UPI app answered. Scan the QR code instead.'),
    );

    try {
      final bool launched = await launchUrl(
        Uri.parse(deepLink),
        mode: LaunchMode.externalApplication,
      );
      if (!launched && context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(fallback);
      }
    } on Object {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(fallback);
      }
    }
  }
}