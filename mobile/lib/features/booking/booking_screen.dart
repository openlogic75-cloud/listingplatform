import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'booking_controller.dart';
import 'booking_repository.dart';

/// Guest booking screen (M3.1): quantity (>= MOQ), name, phone. No account.
/// Success shows the booking code and how tracking works.
class BookingScreen extends ConsumerStatefulWidget {
  const BookingScreen({super.key, required this.draft});

  final BookingDraft draft;

  @override
  ConsumerState<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends ConsumerState<BookingScreen> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  final TextEditingController _name = TextEditingController();
  final TextEditingController _phone = TextEditingController();
  final TextEditingController _notes = TextEditingController();
  bool _isReseller = false;

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    await ref.read(bookingControllerProvider(widget.draft).notifier).submit(
          name: _name.text.trim(),
          phone: _phone.text.trim(),
          isReseller: _isReseller,
          notes: _notes.text.trim(),
        );
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);
    final BookingFormState? state =
        ref.watch(bookingControllerProvider(widget.draft)).valueOrNull;

    final GuestBooking? placed = state?.placed;

    return Scaffold(
      appBar: AppBar(title: const Text('Book this item')),
      body: placed != null
          ? _PlacedView(booking: placed)
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: <Widget>[
                  Text(
                    'No account needed. The seller contacts you on your '
                    'phone; settlement is direct.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 16),
                  _QuantityField(draft: widget.draft, state: state),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _name,
                    textInputAction: TextInputAction.next,
                    autofillHints: const <String>[AutofillHints.name],
                    decoration:
                        const InputDecoration(labelText: 'Your name'),
                    validator: (String? value) =>
                        (value == null || value.trim().isEmpty)
                            ? 'Enter your name.'
                            : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    textInputAction: TextInputAction.next,
                    autofillHints: const <String>[AutofillHints.telephoneNumber],
                    decoration: InputDecoration(
                      labelText: 'Phone',
                      helperText: widget.draft.unit == null
                          ? 'Encrypted at rest; used only for this booking.'
                          : 'Encrypted at rest; the seller uses it to confirm '
                              'your ${widget.draft.unit} order.',
                    ),
                    validator: (String? value) =>
                        (value == null || value.trim().isEmpty)
                            ? 'Enter a phone number the seller can reach you on.'
                            : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _notes,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Notes for the seller (optional)',
                    ),
                  ),
                  const SizedBox(height: 8),
                  SwitchListTile(
                    value: _isReseller,
                    onChanged: (bool value) => setState(() => _isReseller = value),
                    title: const Text('I am booking as a reseller'),
                  ),
                  const SizedBox(height: 16),
                  if (state?.error != null) ...<Widget>[
                    Text(
                      state!.error!,
                      style: theme.textTheme.bodyMedium
                          ?.copyWith(color: theme.colorScheme.error),
                    ),
                    const SizedBox(height: 12),
                  ],
                  FilledButton(
                    onPressed:
                        state?.submitting ?? false ? null : _submit,
                    child: (state?.submitting ?? false)
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Place booking'),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Settlement happens directly with the seller. The platform '
                    'never takes a cut.',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}

class _QuantityField extends ConsumerWidget {
  const _QuantityField({required this.draft, required this.state});

  final BookingDraft draft;
  final BookingFormState? state;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ThemeData theme = Theme.of(context);
    final BookingController controller =
        ref.read(bookingControllerProvider(draft).notifier);
    final int value = state?.effectiveQuantity ?? draft.minimumQuantity;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: <Widget>[
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text('Quantity', style: theme.textTheme.titleSmall),
                  Text(
                    draft.minimumQuantity > 1
                        ? 'Minimum order: ${draft.minimumQuantity}'
                        : draft.unit == null
                            ? 'How many you want'
                            : 'How many ${draft.unit} you want',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
            IconButton(
              onPressed: value <= draft.minimumQuantity
                  ? null
                  : () => controller.setQuantity(value - 1),
              icon: const Icon(Icons.remove_circle_outline),
              tooltip: 'Decrease quantity',
            ),
            Text('$value', style: theme.textTheme.titleLarge),
            IconButton(
              onPressed: () => controller.setQuantity(value + 1),
              icon: const Icon(Icons.add_circle_outline),
              tooltip: 'Increase quantity',
            ),
          ],
        ),
      ),
    );
  }
}

class _PlacedView extends StatelessWidget {
  const _PlacedView({required this.booking});

  final GuestBooking booking;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: <Widget>[
            const Icon(Icons.check_circle_outline, size: 48),
            const SizedBox(height: 12),
            Text(
              'Booking placed',
              style: theme.textTheme.headlineSmall
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Text(
              'Keep this reference code. Track or cancel with it and your '
              'phone number.',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: <Widget>[
                    Text(
                      booking.code,
                      style: theme.textTheme.headlineMedium
                          ?.copyWith(fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      booking.statusLabel,
                      style: theme.textTheme.bodyMedium,
                      textAlign: TextAlign.center,
                    ),
                    if (booking.vendorName != null) ...<Widget>[
                      const SizedBox(height: 4),
                      Text(
                        'Seller: ${booking.vendorName}',
                        style: theme.textTheme.bodySmall,
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            OutlinedButton(
              onPressed: () => context.go('/bookings/lookup'),
              child: const Text('Track my bookings'),
            ),
            const SizedBox(height: 4),
            TextButton(
              onPressed: () => context.go('/catalog'),
              child: const Text('Keep browsing'),
            ),
          ],
        ),
      ),
    );
  }
}

