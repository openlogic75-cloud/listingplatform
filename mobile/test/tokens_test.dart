import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:listingplatform/core/theme/tokens.dart';

void main() {
  group('Design tokens', () {
    test('spacing follows the 4pt grid', () {
      expect(Spacing.xs, 4);
      expect(Spacing.sm, 8);
      expect(Spacing.md, 12);
      expect(Spacing.lg, 16);
      expect(Spacing.xl, 24);
      expect(Spacing.xxl, 32);
      expect(Spacing.xxxl, 48);
    });

    test('semantic brand colors are stable across themes', () {
      expect(BrandColors.seed, const Color(0xFF007BFF));
      expect(BrandColors.success, const Color(0xFF28A745));
      expect(BrandColors.warning, const Color(0xFFF59E0B));
      expect(BrandColors.danger, const Color(0xFFDC3545));
    });

    test('accessibility floor is at least 48dp', () {
      expect(TouchTarget.min, greaterThanOrEqualTo(48));
    });
  });
}
