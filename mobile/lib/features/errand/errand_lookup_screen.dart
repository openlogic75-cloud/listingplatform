import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'errand_repository.dart';

/// Guest errand tracking by code + phone (M4.5 lookup).
class ErrandLookupScreen extends ConsumerStatefulWidget {
  const ErrandLookupScreen({super.key});

  @override
  ConsumerState<ErrandLookupScreen> createState() =>
      _ErrandLookupScreenState();
}

class _ErrandLookupScreenState extends ConsumerState<ErrandLookupScreen> {
  final TextEditingController _code = TextEditingController();
  final TextEditingController _phone = TextEditingController();
  Errand? _result;
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _code.dispose();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _lookup() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final Errand errand = await ref.read(errandRepositoryProvider).lookup(
            code: _code.text.trim(),
            phone: _phone.text.trim(),
          );
      setState(() {
        _result = errand;
        _loading = false;
      });
    } on DioException catch (_) {
      setState(() {
        _result = null;
        _loading = false;
        _error = 'No errand matches that code and phone number.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Track an errand')),
      body: ListView(
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
          TextField(
            controller: _code,
            textCapitalization: TextCapitalization.characters,
            decoration: const InputDecoration(
              labelText: 'Errand code',
              hintText: 'ER-XXXXXX',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(
              labelText: 'Phone used when placing it',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 48,
            child: FilledButton(
              onPressed: _loading ? null : _lookup,
              child: Text(_loading ? 'Checking...' : 'Track errand'),
            ),
          ),
          if (_result != null) ...<Widget>[
            const SizedBox(height: 24),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      _result!.code,
                      style: theme.textTheme.titleLarge
                          ?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _result!.statusLabel,
                      style: theme.textTheme.bodyLarge,
                    ),
                    if (_result!.pickupAddress != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text('Pickup: ${_result!.pickupAddress}'),
                      ),
                    if (_result!.dropAddress != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text('Drop: ${_result!.dropAddress}'),
                      ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}