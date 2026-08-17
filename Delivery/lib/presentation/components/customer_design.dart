import 'package:flutter/material.dart';

/// Visual primitives for customer-facing flows. They intentionally receive
/// labels and values from their callers; business data remains API-driven.
class CustomerDesign {
  static const Color ink = Color(0xFF152238);
  static const Color surface = Color(0xFFF7F8FC);
  static const Color primary = Color(0xFF159B12);
  static const Color muted = Color(0xFF6B778C);
  static const Color border = Color(0xFFE5E9F0);
}

class CustomerNavItem {
  const CustomerNavItem({required this.icon, required this.label});

  final IconData icon;
  final String label;
}

class CustomerBottomNavigation extends StatelessWidget {
  const CustomerBottomNavigation({
    super.key,
    required this.items,
    required this.currentIndex,
    required this.onChanged,
  });

  final List<CustomerNavItem> items;
  final int currentIndex;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      minimum: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: CustomerDesign.border),
          boxShadow: const [
            BoxShadow(color: Color(0x120B1220), blurRadius: 24, offset: Offset(0, 8)),
          ],
        ),
        child: Row(
          children: List.generate(items.length, (index) {
            final item = items[index];
            final selected = index == currentIndex;
            return Expanded(
              child: Semantics(
                selected: selected,
                button: true,
                label: item.label,
                child: InkWell(
                  borderRadius: BorderRadius.circular(18),
                  onTap: () => onChanged(index),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 180),
                    curve: Curves.easeOutCubic,
                    margin: const EdgeInsets.all(6),
                    padding: const EdgeInsets.symmetric(vertical: 9),
                    decoration: BoxDecoration(
                      color: selected ? CustomerDesign.primary.withValues(alpha: 0.12) : Colors.transparent,
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(item.icon, size: 21, color: selected ? CustomerDesign.primary : CustomerDesign.muted),
                        const SizedBox(height: 3),
                        Text(
                          item.label,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: Theme.of(context).textTheme.labelSmall?.copyWith(
                                color: selected ? CustomerDesign.primary : CustomerDesign.muted,
                                fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                              ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          }),
        ),
      ),
    );
  }
}

class AsyncActionButton extends StatefulWidget {
  const AsyncActionButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.icon,
  });

  final String label;
  final Future<bool> Function() onPressed;
  final IconData? icon;

  @override
  State<AsyncActionButton> createState() => _AsyncActionButtonState();
}

class _AsyncActionButtonState extends State<AsyncActionButton> {
  bool _submitting = false;

  Future<void> _submit() async {
    if (_submitting) return;
    setState(() => _submitting = true);
    try {
      await widget.onPressed();
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return FilledButton.icon(
      onPressed: _submitting ? null : _submit,
      icon: _submitting ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : Icon(widget.icon ?? Icons.arrow_forward_rounded),
      label: Text(_submitting ? 'Procesando…' : widget.label),
    );
  }
}
