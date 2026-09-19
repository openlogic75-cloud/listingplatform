import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'volunteer_repository.dart';

/// New site-visit report (M5.2): notes, checklist, geo point.
class VerificationReportScreen extends ConsumerStatefulWidget {
  const VerificationReportScreen({super.key});

  @override
  ConsumerState<VerificationReportScreen> createState() =>
      _VerificationReportScreenState();
}

class _VerificationReportScreenState
    extends ConsumerState<VerificationReportScreen> {
  final TextEditingController _subjectId = TextEditingController();
  final TextEditingController _notes = TextEditingController();
  final TextEditingController _lat = TextEditingController();
  final TextEditingController _lng = TextEditingController();

  String _subjectType = 'App\\Models\\Vendor';
  List<VisitQuestion> _questions = <VisitQuestion>[];
  final Map<String, bool> _answers = <String, bool>{};

  bool _submitting = false;
  String? _error;
  VerificationItem? _created;
  VerificationFee? _fee;

  @override
  void initState() {
    super.initState();
    ref.read(volunteerRepositoryProvider).verificationFee().then(
      (VerificationFee? fee) {
        if (mounted) {
          setState(() => _fee = fee);
        }
      },
      onError: (Object _) {},
    );
    // The platform provides the visit questionnaire (M25.1).
    ref.read(volunteerRepositoryProvider).questionnaire().then(
      (List<VisitQuestion> questions) {
        if (mounted) {
          setState(() {
            _questions = questions;
            for (final VisitQuestion question in questions) {
              _answers.putIfAbsent(question.id, () => false);
            }
          });
        }
      },
      onError: (Object _) {},
    );
  }

  @override
  void dispose() {
    _subjectId.dispose();
    _notes.dispose();
    _lat.dispose();
    _lng.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final int? subjectId = int.tryParse(_subjectId.text.trim());
    if (subjectId == null || _notes.text.trim().isEmpty) {
      setState(() {
        _error = 'Enter a subject id and what you found on the visit.';
      });
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });
    try {
      final VerificationItem created =
          await ref.read(volunteerRepositoryProvider).createReport(
                subjectType: _subjectType,
                subjectId: subjectId,
                notes: _notes.text.trim(),
                checklist: _answers,
                geoLat: double.tryParse(_lat.text.trim()),
                geoLng: double.tryParse(_lng.text.trim()),
              );
      setState(() {
        _created = created;
        _submitting = false;
      });
    } on DioException catch (_) {
      setState(() {
        _submitting = false;
        _error = 'Could not save the report. Check the values and try again.';
      });
    }
  }
  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Site-visit report')),
      body: _created != null ? _done(theme) : _form(theme),
    );
  }

  Widget _done(ThemeData theme) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: <Widget>[
            Icon(
              Icons.fact_check,
              size: 56,
              color: theme.colorScheme.primary,
            ),
            const SizedBox(height: 16),
            Text('Report saved as a draft',
                style: theme.textTheme.headlineSmall),
            const SizedBox(height: 8),
            Text(
              'Submit it from your dashboard once you are sure the '
              'details are right. An admin reviews it before the '
              'verified badge is issued.',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: () => context.go('/volunteer'),
              child: const Text('Back to dashboard'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _form(ThemeData theme) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: <Widget>[
        if (_error != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Text(
              _error!,
              style: theme.textTheme.bodyMedium
                  ?.copyWith(color: theme.colorScheme.error),
            ),
          ),
        if (_fee?.amountInr != null)
          Card(
            color: theme.colorScheme.primaryContainer,
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Icon(Icons.payments_outlined,
                      color: theme.colorScheme.onPrimaryContainer),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'The vendor pays you '
                      '${_fee!.currencySymbol}'
                      '${_fee!.amountInr!.toStringAsFixed(2)} for this visit '
                      'directly. The platform never handles the payment.',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onPrimaryContainer,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        const SizedBox(height: 12),
        SegmentedButton<String>(
          segments: const <ButtonSegment<String>>[
            ButtonSegment<String>(
              value: 'App\\Models\\Vendor',
              label: Text('Vendor'),
            ),
            ButtonSegment<String>(
              value: 'App\\Models\\Product',
              label: Text('Listing'),
            ),
          ],
          selected: <String>{_subjectType},
          onSelectionChanged: (Set<String> v) =>
              setState(() => _subjectType = v.first),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _subjectId,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(
            labelText: 'Subject id',
            hintText: 'The id shown on the vendor or listing page',
            border: OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 8),
        Text('Visit questionnaire', style: theme.textTheme.titleMedium),
        Text(
          'The platform asks these; you answer them on the visit. Your answers '
          'and photos become the story that vouches for the listing.',
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(height: 4),
        for (final VisitQuestion question in _questions)
          CheckboxListTile(
            value: _answers[question.id] ?? false,
            onChanged: (bool? v) =>
                setState(() => _answers[question.id] = v ?? false),
            title: Text(question.question),
            controlAffinity: ListTileControlAffinity.leading,
            contentPadding: EdgeInsets.zero,
          ),
        const SizedBox(height: 8),
        TextField(
          controller: _notes,
          maxLines: 4,
          decoration: const InputDecoration(
            labelText: 'What you found',
            hintText: 'Plain description of the visit',
            border: OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 12),
        Row(
          children: <Widget>[
            Expanded(
              child: TextField(
                controller: _lat,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Latitude (optional)',
                  border: OutlineInputBorder(),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: TextField(
                controller: _lng,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Longitude (optional)',
                  border: OutlineInputBorder(),
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
        SizedBox(
          height: 48,
          child: FilledButton(
            onPressed: _submitting ? null : _submit,
            child: Text(_submitting ? 'Saving...' : 'Save report'),
          ),
        ),
      ],
    );
  }
}