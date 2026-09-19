import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/theme/tokens.dart';
import '../../core/network/api_client.dart';
import '../auth/auth_controller.dart';
import 'privacy_repository.dart';

/// Account and data screen (M7.1-M7.3). Registered users must be able to see
/// what they agreed to, take their data with them, and delete their account
/// without asking anyone. All three are here, and all three are backed by
/// audited endpoints.
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  bool _busy = false;
  String? _notice;

  Future<void> _export() async {
    setState(() {
      _busy = true;
      _notice = null;
    });
    try {
      final ExportResult result =
          await ref.read(privacyRepositoryProvider).requestExport();
      if (!mounted) {
        return;
      }
      setState(() {
        _notice = result.downloadUrl.isEmpty
            ? 'Export request recorded. The file is being prepared.'
            : 'Export ready. Open it from the link below.';
      });
      await _openExport(result.downloadUrl);
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _notice = e.response?.statusCode == 401
              ? 'Sign in first - exports are tied to your account.'
              : 'Could not start the export. Try again in a moment.';
        });
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  Future<void> _openExport(String url) async {
    if (url.isEmpty) {
      return;
    }
    try {
      await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
    } on Object {
      // The link stays visible in the notice; opening it is best-effort.
    }
  }

  Future<void> _confirmDeletion() async {
    final bool? confirmed = await showDialog<bool>(
      context: context,
      builder: (BuildContext dialogContext) => AlertDialog(
        title: const Text('Delete your account?'),
        content: const Text(
          'Your name, contact details and password are removed straight away. '
          'Verification badges issued earlier keep the volunteer name only, so '
          'they stay valid. This cannot be undone.',
        ),
        actions: <Widget>[
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('Keep my account'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) {
      return;
    }

    setState(() {
      _busy = true;
      _notice = null;
    });
    try {
      final int requestId =
          await ref.read(privacyRepositoryProvider).requestDeletion();
      if (!mounted) {
        return;
      }
      setState(() {
        _notice = 'Deletion complete. Request $requestId is on record.';
      });
      await ref.read(authControllerProvider.notifier).logout();
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _notice = e.response?.statusCode == 401
              ? 'Sign in first - deletion applies to your own account.'
              : 'Could not complete the deletion. Nothing was changed.';
        });
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  Future<void> _revoke(ConsentRecord consent) async {
    setState(() {
      _busy = true;
      _notice = null;
    });
    try {
      await ref.read(privacyRepositoryProvider).revokeConsent(consent.id);
      ref.invalidate(consentsProvider);
      if (mounted) {
        setState(() => _notice = '${consent.label}: consent withdrawn.');
      }
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _notice = e.response?.statusCode == 422
              ? 'That consent record belongs to another account.'
              : 'Could not withdraw consent. Try again in a moment.';
        });
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);
    final AsyncValue<List<ConsentRecord>> consents = ref.watch(consentsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Account and data')),
      body: ListView(
        padding: const EdgeInsets.all(Spacing.lg),
        children: <Widget>[
          if (_notice != null) ...<Widget>[
            Card(
              color: theme.colorScheme.surfaceContainerHighest,
              child: Padding(
                padding: const EdgeInsets.all(Spacing.md),
                child: Row(
                  children: <Widget>[
                    Icon(
                      Icons.info_outline,
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                    const SizedBox(width: Spacing.sm),
                    Expanded(child: Text(_notice!)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: Spacing.md),
          ],
          Text(
            'Your data, your call',
            style: theme.textTheme.titleMedium
                ?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: Spacing.xs),
          Text(
            'Download everything we hold about you, withdraw a consent, or '
            'delete your account outright. Nobody has to approve it.',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: Spacing.lg),
          Card(
            child: ListTile(
              leading: const Icon(Icons.download_outlined),
              title: const Text('Download my data'),
              subtitle: const Text(
                'A JSON file with your profile and consent history.',
              ),
              trailing: const Icon(Icons.chevron_right),
              enabled: !_busy,
              onTap: _busy ? null : _export,
            ),
          ),
          const SizedBox(height: Spacing.md),
          Text(
            'Consents you have given',
            style: theme.textTheme.titleMedium
                ?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: Spacing.sm),
          consents.when(
            loading: () => const Padding(
              padding: EdgeInsets.all(Spacing.lg),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (Object error, StackTrace stack) => Text(
              'Could not load your consents.',
              style: theme.textTheme.bodyMedium
                  ?.copyWith(color: theme.colorScheme.error),
            ),
            data: (List<ConsentRecord> rows) => rows.isEmpty
                ? Text(
                    'No consents on record yet.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  )
                : Column(
                    children: <Widget>[
                      for (final ConsentRecord row in rows)
                        _ConsentTile(
                          consent: row,
                          busy: _busy,
                          onRevoke: () => _revoke(row),
                        ),
                    ],
                  ),
          ),
          const SizedBox(height: Spacing.xl),
          Card(
            color: theme.colorScheme.errorContainer,
            child: ListTile(
              leading: Icon(
                Icons.delete_outline,
                color: theme.colorScheme.onErrorContainer,
              ),
              title: Text(
                'Delete my account',
                style: TextStyle(color: theme.colorScheme.onErrorContainer),
              ),
              subtitle: Text(
                'Personal details are removed immediately. You will be signed '
                'out.',
                style: TextStyle(color: theme.colorScheme.onErrorContainer),
              ),
              enabled: !_busy,
              onTap: _busy ? null : _confirmDeletion,
            ),
          ),
          const SizedBox(height: Spacing.md),
          Text('About & legal', style: theme.textTheme.titleMedium),
          const SizedBox(height: Spacing.sm),
          Wrap(
            spacing: Spacing.sm,
            children: <Widget>[
              TextButton(
                onPressed: () => _openUrl('$kSiteBaseUrl/terms'),
                child: const Text('Terms & Conditions'),
              ),
              TextButton(
                onPressed: () => _openUrl('$kSiteBaseUrl/privacy'),
                child: const Text('Privacy Policy'),
              ),
              TextButton(
                onPressed: () => _openUrl('$kSiteBaseUrl/disclaimer'),
                child: const Text('Disclaimer'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Future<void> _openUrl(String url) async {
    try {
      await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
    } on Object {
      // Best-effort; the pages are also reachable from a browser.
    }
  }
}

/// One consent row with its plain-language label and a withdraw action.
class _ConsentTile extends StatelessWidget {
  const _ConsentTile({
    required this.consent,
    required this.busy,
    required this.onRevoke,
  });

  final ConsentRecord consent;
  final bool busy;
  final VoidCallback onRevoke;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        leading: const Icon(Icons.check_circle_outline),
        title: Text(consent.label),
        subtitle: Text(
          'Text version ${consent.textVersion}'
          '${consent.grantedAt == null ? '' : ' - given ${_date(consent.grantedAt!)}'}',
        ),
        trailing: TextButton(
          onPressed: busy ? null : onRevoke,
          child: const Text('Withdraw'),
        ),
      ),
    );
  }

  String _date(DateTime at) {
    final DateTime local = at.toLocal();
    return '${local.day.toString().padLeft(2, '0')}/'
        '${local.month.toString().padLeft(2, '0')}/${local.year}';
  }
}