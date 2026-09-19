import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../auth/auth_controller.dart';

/// Home shell. Buyers browse as guests; registered roles land here after
/// sign-in. Catalog, bookings and role dashboards attach in M2+.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ThemeData theme = Theme.of(context);
    final AsyncValue<SessionState> session =
        ref.watch(authControllerProvider);

    return Scaffold(
      body: CustomScrollView(
        slivers: <Widget>[
          SliverAppBar(
            pinned: true,
            title: const Text('Shekuthi'),
            actions: <Widget>[
              session.when(
                data: (SessionState state) {
                  if (state is SessionAuthenticated) {
                    return Padding(
                      padding: const EdgeInsets.only(right: 16),
                      child: Center(
                        child: Text(
                          state.profile.name,
                          style: theme.textTheme.labelLarge,
                        ),
                      ),
                    );
                  }
                  return TextButton(
                    onPressed: () => context.go('/login'),
                    child: const Text('Sign in'),
                  );
                },
                loading: () => const SizedBox.shrink(),
                error: (_, __) => const SizedBox.shrink(),
              ),
            ],
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    'Local goods, honest sourcing, direct connections.',
                    style: theme.textTheme.headlineMedium
                        ?.copyWith(fontWeight: FontWeight.w700, height: 1.2),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'A free, open platform connecting buyers, sellers, '
                    'logistics and ground-truth verification. No commission, '
                    'no on-platform payments - it runs on donations.',
                    style: theme.textTheme.bodyLarge?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 24),
                  _ActionCard(
                    icon: Icons.storefront_outlined,
                    title: 'Browse the catalog',
                    body: 'Traditional products, agro produce, rentals and '
                        'homestays. No account needed.',
                    onTap: () => context.go('/catalog'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.hotel_outlined,
                    title: 'PG, rentals & homestays',
                    body: 'Rooms, paying-guest accommodation and homestays, '
                        'on their own page.',
                    onTap: () => context.go('/stays'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.agriculture_outlined,
                    title: 'Farm produce for resellers',
                    body: 'Bulk farm produce direct from farmers; collectors '
                        'bring it to a hub district.',
                    onTap: () => context.go('/farm-produce'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.article_outlined,
                    title: 'Stories',
                    body: 'New businesses and farms, and the on-site visits '
                        'volunteers made.',
                    onTap: () => context.go('/stories'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.handyman_outlined,
                    title: 'Skilled workers',
                    body: 'Find people who do the work you need, by trade. '
                        'No account needed.',
                    onTap: () => context.go('/workers'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.local_shipping_outlined,
                    title: 'Transport & errands',
                    body: 'Drivers and errand runners you can call directly.',
                    onTap: () => context.go('/transport'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.edit_note_outlined,
                    title: 'My listings',
                    body: 'Vendors: create, publish and archive your items.',
                    onTap: () => context.go('/listings'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.store_outlined,
                    title: 'Shop profile',
                    body: 'Vendors: update your name, contact and shop '
                        'description.',
                    onTap: () => context.go('/vendor/profile'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.inbox_outlined,
                    title: 'Incoming bookings',
                    body: 'Vendors: confirm and move bookings to delivered.',
                    onTap: () => context.go('/vendor/bookings'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.agriculture_outlined,
                    title: 'Request a farm-produce collection',
                    body: 'Vendors: have a bulk listing collected from its '
                        'sub-division to a hub district.',
                    onTap: () => context.go('/collections/new'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.local_shipping_outlined,
                    title: 'Collect farm produce',
                    body: 'Collectors: your sub-division and the collections '
                        'waiting there.',
                    onTap: () => context.go('/collector'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.travel_explore_outlined,
                    title: 'Track a booking',
                    body: 'Guests: follow or cancel a booking with its code '
                        'and your phone number.',
                    onTap: () => context.go('/bookings/lookup'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.local_shipping_outlined,
                    title: 'Drive and run errands',
                    body: 'Drivers: set your district plus up to five '
                        'localities, go online, and take jobs.',
                    onTap: () => context.go('/driver'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.badge_outlined,
                    title: 'Driver work profile',
                    body: 'Drivers: list the transport and errand work you do, '
                        'and the number buyers can call.',
                    onTap: () => context.go('/driver/work'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.build_outlined,
                    title: 'Skilled worker profile',
                    body: 'Workers: tick the work you provide and add your own.',
                    onTap: () => context.go('/worker/profile'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.radar_outlined,
                    title: 'Who\'s online near you',
                    body: 'See which localities have drivers online right '
                        'now. No map, no live GPS - just honest counts.',
                    onTap: () => context.go('/drivers-online'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.assignment_outlined,
                    title: 'Request an errand',
                    body: 'Anyone: send a parcel or have something collected. '
                        'Track it with the errand code.',
                    onTap: () => context.go('/errands/new'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.verified_outlined,
                    title: 'Verified listings',
                    body: 'Volunteers visit sites and stamp listings with '
                        'their name. The site-visit fee is paid directly to '
                        'the volunteer.',
                    onTap: () => context.go('/volunteer'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.notifications_none_outlined,
                    title: 'Notifications',
                    body: 'Booking updates, job offers and verification '
                        'results land in your inbox.',
                    onTap: () => context.go('/notifications'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.volunteer_activism_outlined,
                    title: 'Support the platform',
                    body: 'Donate by UPI. The platform charges no commission '
                        'and never handles your payment details.',
                    onTap: () => context.go('/donations'),
                  ),
                  const SizedBox(height: 12),
                  _ActionCard(
                    icon: Icons.manage_accounts_outlined,
                    title: 'Your account and data',
                    body: 'Download a copy of your data or delete your '
                        'account and personal details.',
                    onTap: () => context.go('/profile'),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionCard extends StatelessWidget {
  const _ActionCard({
    required this.icon,
    required this.title,
    required this.body,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String body;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Icon(icon, size: 28, color: theme.colorScheme.primary),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Row(
                      children: <Widget>[
                        Expanded(
                          child: Text(
                            title,
                            style: theme.textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.w600),
                          ),
                        ),
                        Icon(
                          Icons.chevron_right,
                          size: 20,
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      body,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
