import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';

import 'app.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Local offline cache (browse caching lands in later milestones).
  await Hive.initFlutter();

  runApp(const ProviderScope(child: MarketplaceApp()));
}
