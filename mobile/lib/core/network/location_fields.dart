import 'package:flutter/material.dart';

import 'locations_provider.dart';

/// District dropdown bound to the public /locations data.
class DistrictField extends StatelessWidget {
  const DistrictField({
    super.key,
    required this.label,
    required this.value,
    required this.districts,
    required this.onChanged,
  });

  final String label;
  final int? value;
  final List<DistrictWithLocalities> districts;
  final ValueChanged<int?> onChanged;

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<int>(
      initialValue: value,
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
      ),
      items: districts
          .map((DistrictWithLocalities d) => DropdownMenuItem<int>(
                value: d.id,
                child: Text(d.name),
              ))
          .toList(),
      onChanged: onChanged,
      validator: (int? v) => v == null ? 'Choose a district' : null,
    );
  }
}

/// Locality dropdown constrained to the chosen district.
class LocalityField extends StatelessWidget {
  const LocalityField({
    super.key,
    required this.label,
    required this.value,
    required this.districtId,
    required this.districts,
    required this.onChanged,
  });

  final String label;
  final int? value;
  final int? districtId;
  final List<DistrictWithLocalities> districts;
  final ValueChanged<int?> onChanged;

  @override
  Widget build(BuildContext context) {
    final DistrictWithLocalities? district = districts
        .where((DistrictWithLocalities d) => d.id == districtId)
        .firstOrNull;

    return DropdownButtonFormField<int>(
      initialValue: value,
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
      ),
      items: (district?.localities ?? const <LocalityOption>[])
          .map((LocalityOption l) => DropdownMenuItem<int>(
                value: l.id,
                child: Text(l.name),
              ))
          .toList(),
      onChanged: onChanged,
      validator: (int? v) => v == null ? 'Choose a locality' : null,
    );
  }
}