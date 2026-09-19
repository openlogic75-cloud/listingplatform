/// Design tokens shared across the app.
///
/// Values mirror docs/design/tokens.md. Widgets reference these constants -
/// never inline literals. Icons come from assets/icons (SVG, outline default);
/// emoji characters are not used anywhere in the UI.
library;

import 'package:flutter/material.dart';

/// Spacing scale, 4pt base grid.
abstract final class Spacing {
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 24;
  static const double xxl = 32;
  static const double xxxl = 48;
}

/// Corner radii: crisp controls 6, cards 8, chips pill.
abstract final class AppRadius {
  static const double control = 6;
  static const double card = 8;
  static const double pill = 999;

  static final BorderRadius controlRadius = BorderRadius.circular(control);
  static final BorderRadius cardRadius = BorderRadius.circular(card);
}

/// Brand colors. The seed drives the Material 3 schemes; semantic colors stay
/// stable across light and dark so status meaning never shifts.
abstract final class BrandColors {
  /// Interactive seed (brand blue). Used only for interaction and status.
  static const Color seed = Color(0xFF007BFF);

  static const Color success = Color(0xFF28A745);
  static const Color warning = Color(0xFFF59E0B);
  static const Color danger = Color(0xFFDC3545);
}

/// Minimum touch target for interactive elements (accessibility floor).
abstract final class TouchTarget {
  static const double min = 48;
}
