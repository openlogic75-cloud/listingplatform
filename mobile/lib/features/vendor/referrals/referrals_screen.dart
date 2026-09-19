import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/theme/tokens.dart';
import 'referrals_repository.dart';

/// Builds the public landing URL a vendor shares (M6.3). It resolves to the
/// website's `/ref/{code}` route, which records the attribution.
String referralLink(String code) => '$kSiteBaseUrl/ref/$code';

/// Vendor referral codes (M6.3). Vendors hand these to their own marketing
/// people; the landing page attributes signups. Counts are informational - the
/// platform never moves money.
class ReferralsScreen extends ConsumerStatefulWidget {
  const ReferralsScreen({super.key});

  @override
  ConsumerState<ReferralsScreen> createState() => _ReferralsScreenState();
}

class _ReferralsScreenState extends ConsumerState<ReferralsScreen> {
  ReferralSummary _summary = const ReferralSummary.empty();
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final ReferralSummary summary =
          await ref.read(referralsRepositoryProvider).summary();
      if (mounted) {
        setState(() {
          _summary = summary;
          _loading = false;
        });
      }
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _error = e.response?.statusCode == 401
              ? 'Sign in as a vendor to manage referral codes.'
              : 'Could not load referral codes. Pull down to try again.';
          _loading = false;
        });
      }
    }
  }

  Future<void> _create() async {
    final TextEditingController labelController = TextEditingController();
    final bool? confirmed = await showDialog<bool>(
      context: context,
      builder: (BuildContext context) => AlertDialog(
        title: const Text('New referral code'),
        content: TextField(
          controller: labelController,
          autofocus: true,
          maxLength: 40,
          decoration: const InputDecoration(
            labelText: 'Label (optional)',
            helperText: 'Used to build the code, e.g. "monsoon-flyer".',
          ),
        ),
        actions: <Widget>[
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Create'),
          ),
        ],
      ),
    );

    if (confirmed != true) {
      return;
    }

    try {
      await ref
          .read(referralsRepositoryProvider)
          .create(label: labelController.text.trim());
      await _load();
    } on DioException catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not create the code.')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Referral codes'),
        actions: <Widget>[
          IconButton(
            tooltip: 'New code',
            onPressed: _create,
            icon: const Icon(Icons.add_link_outlined),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : _summary.codes.isEmpty
                ? _emptyView(theme)
                : _list(theme),
      ),
    );
  }

  Widget _emptyView(ThemeData theme) {
    return ListView(
      padding: const EdgeInsets.all(Spacing.xl),
      children: <Widget>[
        const SizedBox(height: Spacing.xxxl),
        Icon(
          Icons.link_outlined,
          size: 48,
          color: theme.colorScheme.onSurfaceVariant,
        ),
        const SizedBox(height: Spacing.md),
        Text(
          _error ?? 'No referral codes yet.',
          textAlign: TextAlign.center,
          style: theme.textTheme.titleMedium?.copyWith(
            color: _error != null ? theme.colorScheme.error : null,
          ),
        ),
        const SizedBox(height: Spacing.xs),
        Text(
          'Create one per campaign, then share the link. Signups through it '
          'are counted here.',
          textAlign: TextAlign.center,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(height: Spacing.lg),
        Center(
          child: FilledButton.icon(
            onPressed: _create,
            icon: const Icon(Icons.add_outlined),
            label: const Text('Create a code'),
          ),
        ),
      ],
    );
  }

  Widget _list(ThemeData theme) {
    return ListView(
      padding: const EdgeInsets.all(Spacing.lg),
      children: <Widget>[
        Row(
          children: <Widget>[
            Expanded(
              child: _stat(
                theme,
                'Signups',
                _summary.totalSignups.toString(),
              ),
            ),
            const SizedBox(width: Spacing.md),
            Expanded(
              child: _stat(
                theme,
                'Conversions',
                _summary.totalConversions.toString(),
              ),
            ),
          ],
        ),
        const SizedBox(height: Spacing.lg),
        for (final ReferralCode code in _summary.codes) ...<Widget>[
          Card(
            child: ListTile(
              leading: const Icon(Icons.qr_code_2_outlined),
              title: Text(
                code.code,
                style: theme.textTheme.titleSmall
                    ?.copyWith(fontWeight: FontWeight.w600),
              ),
              subtitle: Text(
                '${code.signups} signups - ${code.conversions} conversions',
              ),
              trailing: IconButton(
                tooltip: 'Copy link',
                icon: const Icon(Icons.copy_all_outlined),
                onPressed: () async {
                  await Clipboard.setData(
                    ClipboardData(text: referralLink(code.code)),
                  );
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Referral link copied.')),
                    );
                  }
                },
              ),
            ),
          ),
          const SizedBox(height: Spacing.sm),
        ],
        const SizedBox(height: Spacing.md),
        Text(
          'Counts are informational. The platform charges no commission and '
          'moves no money between accounts.',
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ],
    );
  }

  Widget _stat(ThemeData theme, String label, String value) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(Spacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              value,
              style: theme.textTheme.headlineSmall
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: Spacing.xs),
            Text(
              label,
              style: theme.textTheme.labelMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ],
        ),
      ),
    );
  }
}