import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/language_provider.dart';
import '../utils/constants.dart';

class LanguageSelector extends StatelessWidget {
  final bool showFlag;
  final bool showText;
  final bool isCompact;

  const LanguageSelector({
    Key? key,
    this.showFlag = true,
    this.showText = true,
    this.isCompact = false,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Consumer<LanguageProvider>(
      builder: (context, languageProvider, child) {
        final currentLanguage = languageProvider.currentLanguageInfo;

        if (isCompact) {
          return _CompactLanguageSelector(
            currentLanguage: currentLanguage,
            availableLanguages: languageProvider.availableLanguages,
            onLanguageChanged: languageProvider.setLanguage,
            showFlag: showFlag,
            showText: showText,
          );
        }

        return _FullLanguageSelector(
          currentLanguage: currentLanguage,
          availableLanguages: languageProvider.availableLanguages,
          onLanguageChanged: languageProvider.setLanguage,
          showFlag: showFlag,
          showText: showText,
        );
      },
    );
  }
}

class _CompactLanguageSelector extends StatelessWidget {
  final Map<String, String> currentLanguage;
  final List<Map<String, String>> availableLanguages;
  final Function(String) onLanguageChanged;
  final bool showFlag;
  final bool showText;

  const _CompactLanguageSelector({
    required this.currentLanguage,
    required this.availableLanguages,
    required this.onLanguageChanged,
    required this.showFlag,
    required this.showText,
  });

  @override
  Widget build(BuildContext context) {
    return PopupMenuButton<String>(
      onSelected: onLanguageChanged,
      child: Container(
        padding: const EdgeInsets.symmetric(
          horizontal: AppSizes.paddingSmall,
          vertical: AppSizes.paddingSmall,
        ),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.2),
          borderRadius: BorderRadius.circular(AppSizes.radiusSmall),
          border: Border.all(
            color: Colors.white.withOpacity(0.3),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (showFlag) ...[
              Text(
                currentLanguage['flag'] ?? '',
                style: const TextStyle(fontSize: 16),
              ),
              if (showText) const SizedBox(width: 4),
            ],
            if (showText)
              Text(
                currentLanguage['code']?.toUpperCase() ?? '',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                ),
              ),
            const SizedBox(width: 4),
            const Icon(
              Icons.keyboard_arrow_down,
              color: Colors.white,
              size: 16,
            ),
          ],
        ),
      ),
      itemBuilder: (context) => availableLanguages.map((language) {
        return PopupMenuItem<String>(
          value: language['code'],
          child: Row(
            children: [
              if (showFlag) ...[
                Text(
                  language['flag'] ?? '',
                  style: const TextStyle(fontSize: 16),
                ),
                const SizedBox(width: 8),
              ],
              if (showText)
                Text(
                  language['name'] ?? '',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                  ),
                ),
            ],
          ),
        );
      }).toList(),
    );
  }
}

class _FullLanguageSelector extends StatelessWidget {
  final Map<String, String> currentLanguage;
  final List<Map<String, String>> availableLanguages;
  final Function(String) onLanguageChanged;
  final bool showFlag;
  final bool showText;

  const _FullLanguageSelector({
    required this.currentLanguage,
    required this.availableLanguages,
    required this.onLanguageChanged,
    required this.showFlag,
    required this.showText,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppSizes.radiusMedium),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppSizes.paddingMedium),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Language / اللغة',
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w600,
                color: AppColors.primaryColor,
              ),
            ),
            const SizedBox(height: AppSizes.paddingSmall),
            Row(
              children: availableLanguages.map((language) {
                final isSelected = language['code'] == currentLanguage['code'];
                return Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 4),
                    child: InkWell(
                      onTap: () => onLanguageChanged(language['code']!),
                      borderRadius: BorderRadius.circular(AppSizes.radiusSmall),
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          vertical: AppSizes.paddingSmall,
                          horizontal: AppSizes.paddingMedium,
                        ),
                        decoration: BoxDecoration(
                          color: isSelected
                              ? AppColors.primaryColor
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(AppSizes.radiusSmall),
                          border: Border.all(
                            color: isSelected
                                ? AppColors.primaryColor
                                : Colors.grey[300]!,
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            if (showFlag) ...[
                              Text(
                                language['flag'] ?? '',
                                style: const TextStyle(fontSize: 16),
                              ),
                              if (showText) const SizedBox(width: 4),
                            ],
                            if (showText)
                              Flexible(
                                child: Text(
                                  language['name'] ?? '',
                                  style: TextStyle(
                                    color: isSelected ? Colors.white : Colors.black87,
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                  ),
                                  textAlign: TextAlign.center,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ],
        ),
      ),
    );
  }
}
