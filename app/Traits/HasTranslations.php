<?php

namespace App\Traits;

trait HasTranslations
{
    /**
     * Get the translatable fields (defined in each model as $translatable).
     */
    public function getTranslatableFields(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Get translation for a specific field and locale.
     * Falls back to the original (Korean) value if no translation exists.
     */
    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        // Korean is stored in the original columns
        if ($locale === 'ko') {
            return parent::getAttribute($field);
        }

        $translations = parent::getAttribute('translations');

        if (is_string($translations)) {
            $translations = json_decode($translations, true);
        }

        if (!is_array($translations)) {
            return parent::getAttribute($field);
        }

        $value = $translations[$locale][$field] ?? null;

        // Fallback to original Korean value
        return $value ?: parent::getAttribute($field);
    }

    /**
     * Set translation for a specific field and locale.
     */
    public function setTranslation(string $field, string $locale, string $value): self
    {
        $translations = $this->translations ?? [];

        if (!isset($translations[$locale])) {
            $translations[$locale] = [];
        }

        $translations[$locale][$field] = $value;
        $this->translations = $translations;

        return $this;
    }

    /**
     * Override getAttribute to automatically return translated values
     * when the current locale is not Korean.
     */
    public function getAttribute($key)
    {
        if (
            in_array($key, $this->getTranslatableFields())
            && app()->getLocale() !== 'ko'
        ) {
            return $this->getTranslation($key);
        }

        return parent::getAttribute($key);
    }
}
