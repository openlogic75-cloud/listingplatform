import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

/// Persistent app navigation for the main mobile experience (M41.1).
/// Authentication screens remain outside this shell so sign-in stays focused.
class AppShell extends StatelessWidget {
  const AppShell({required this.child, super.key});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: child,
      bottomNavigationBar: const _AppBottomNavigationBar(),
    );
  }
}

class _AppBottomNavigationBar extends StatelessWidget {
  const _AppBottomNavigationBar();

  @override
  Widget build(BuildContext context) {
    final int selectedIndex = _selectedIndex(context);

    return NavigationBar(
      selectedIndex: selectedIndex,
      destinations: const <NavigationDestination>[
        NavigationDestination(
          icon: Icon(Icons.home_outlined),
          selectedIcon: Icon(Icons.home),
          label: 'Home',
        ),
        NavigationDestination(
          icon: Icon(Icons.search_outlined),
          selectedIcon: Icon(Icons.search),
          label: 'Browse',
        ),
        NavigationDestination(
          icon: Icon(Icons.agriculture_outlined),
          selectedIcon: Icon(Icons.agriculture),
          label: 'Farm',
        ),
        NavigationDestination(
          icon: Icon(Icons.person_outline),
          selectedIcon: Icon(Icons.person),
          label: 'Account',
        ),
        NavigationDestination(
          icon: Icon(Icons.arrow_back),
          label: 'Back',
        ),
      ],
      onDestinationSelected: (int index) {
        switch (index) {
          case 0:
            context.go('/');
          case 1:
            context.go('/catalog');
          case 2:
            context.go('/farm-produce');
          case 3:
            context.go('/profile');
          case 4:
            if (context.canPop()) {
              context.pop();
            } else {
              context.go('/');
            }
        }
      },
    );
  }

  int _selectedIndex(BuildContext context) {
    final String path = GoRouterState.of(context).uri.path;

    if (path == '/') {
      return 0;
    }
    if (path.startsWith('/catalog') ||
        path == '/stays' ||
        path == '/workers' ||
        path == '/transport') {
      return 1;
    }
    if (path == '/farm-produce' || path.startsWith('/collections')) {
      return 2;
    }
    if (path == '/profile' ||
        path.startsWith('/listings') ||
        path.startsWith('/vendor') ||
        path.startsWith('/driver') ||
        path.startsWith('/worker') ||
        path.startsWith('/collector')) {
      return 3;
    }

    return 0;
  }
}
