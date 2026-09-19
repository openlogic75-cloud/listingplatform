import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';

/// Root widget: Material 3, light + dark, go_router navigation.
class MarketplaceApp extends ConsumerWidget {
  const MarketplaceApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final GoRouterConfig router = ref.watch(goRouterProvider);

    return MaterialApp.router(
      title: 'Shekuthi',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      darkTheme: AppTheme.dark(),
      themeMode: ThemeMode.system,
      routerConfig: router.config,
    );
  }
}
