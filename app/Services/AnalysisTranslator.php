<?php

namespace App\Services;

class AnalysisTranslator
{
    /**
     * Translate all analysis data for the current locale.
     * Only processes if locale is not 'ko'.
     */
    public static function translate(array $data): array
    {
        if (app()->getLocale() === 'ko') {
            return $data;
        }

        if (isset($data['timeline'])) {
            $data['timeline'] = self::translateTimeline($data['timeline']);
        }

        if (isset($data['milestones'])) {
            $data['milestones'] = self::translateMilestones($data['milestones']);
        }

        if (isset($data['metrics'])) {
            $data['metrics'] = self::translateMetrics($data['metrics']);
        }

        if (isset($data['usage_guide'])) {
            $data['usage_guide'] = self::translateUsageGuide($data['usage_guide']);
        }

        if (isset($data['skin_profile'])) {
            $data['skin_profile'] = self::translateSkinProfile($data['skin_profile']);
        }

        if (isset($data['lifestyle_factors'])) {
            $data['lifestyle_factors'] = self::translateLifestyleFactors($data['lifestyle_factors']);
        }

        return $data;
    }

    /**
     * Translate timeline milestone messages.
     */
    public static function translateTimeline(array $timeline): array
    {
        if (isset($timeline['milestones'])) {
            foreach ($timeline['milestones'] as $day => &$message) {
                $message = self::translatePatternString($message);
            }
        }

        return $timeline;
    }

    /**
     * Translate milestones data.
     */
    public static function translateMilestones(array $milestones): array
    {
        foreach ($milestones as &$milestone) {
            if (isset($milestone['message'])) {
                $milestone['message'] = self::translatePatternString($milestone['message']);
            }
            if (isset($milestone['title'])) {
                $milestone['title'] = self::translatePatternString($milestone['title']);
            }
        }

        return $milestones;
    }

    /**
     * Translate metrics data.
     */
    public static function translateMetrics(array $metrics): array
    {
        // Translate top-level metric name/description
        if (isset($metrics['name'])) {
            $metrics['name'] = __($metrics['name']);
        }
        if (isset($metrics['description'])) {
            $metrics['description'] = __($metrics['description']);
        }

        // Translate per-category metrics
        $metricKeys = ['moisture', 'elasticity', 'tone', 'pore', 'wrinkle', 'soothing'];
        foreach ($metricKeys as $key) {
            if (isset($metrics[$key])) {
                if (isset($metrics[$key]['name'])) {
                    $metrics[$key]['name'] = __($metrics[$key]['name']);
                }
                if (isset($metrics[$key]['description'])) {
                    $metrics[$key]['description'] = __($metrics[$key]['description']);
                }
            }
        }

        return $metrics;
    }

    /**
     * Translate usage guide data.
     */
    public static function translateUsageGuide(array $guide): array
    {
        // Optimal usage timing
        if (isset($guide['optimal_usage'])) {
            $usage = &$guide['optimal_usage'];

            if (isset($usage['timing']['reason'])) {
                $usage['timing']['reason'] = __($usage['timing']['reason']);
            }
            if (isset($usage['best_time'])) {
                $usage['best_time'] = __($usage['best_time']);
            }
            if (isset($usage['frequency'])) {
                $usage['frequency'] = __($usage['frequency']);
            }
            if (isset($usage['optimal_amount'])) {
                $usage['optimal_amount'] = __($usage['optimal_amount']);
            }
            if (isset($usage['absorption_tip'])) {
                $usage['absorption_tip'] = __($usage['absorption_tip']);
            }
        }

        // Recommendations
        if (isset($guide['recommendations'])) {
            foreach ($guide['recommendations'] as &$rec) {
                if (isset($rec['action'])) {
                    $rec['action'] = __($rec['action']);
                }
                if (isset($rec['action_short'])) {
                    $rec['action_short'] = __($rec['action_short']);
                }
                if (isset($rec['description'])) {
                    $rec['description'] = self::translatePatternString($rec['description']);
                }
            }
        }

        return $guide;
    }

    /**
     * Translate skin profile data.
     */
    public static function translateSkinProfile(array $profile): array
    {
        if (isset($profile['characteristics'])) {
            foreach ($profile['characteristics'] as $key => &$char) {
                if (isset($char['label'])) {
                    $char['label'] = __($char['label']);
                }
                if (isset($char['description'])) {
                    $char['description'] = __($char['description']);
                }
            }
        }

        if (isset($profile['summary'])) {
            $profile['summary'] = self::translatePatternString($profile['summary']);
        }

        return $profile;
    }

    /**
     * Translate lifestyle factors.
     */
    public static function translateLifestyleFactors(array $factors): array
    {
        foreach ($factors as &$factor) {
            if (isset($factor['name'])) {
                $factor['name'] = __($factor['name']);
            }
        }

        return $factors;
    }

    /**
     * Translate a Korean string that may contain embedded numbers/values.
     * Tries exact match first, then pattern-based translation.
     */
    private static function translatePatternString(string $text): string
    {
        // Try exact match first
        $translated = __($text);
        if ($translated !== $text) {
            return $translated;
        }

        // Pattern-based translation: extract numbers and try template matching
        $patterns = [
            // "피부 수분 흡수율 12.5%p 상승 감지" pattern
            '/^(.+?)([\d.]+)(%p?\s*)(.+)$/' => function ($matches) {
                $prefix = trim($matches[1]);
                $value = $matches[2];
                $unit = $matches[3];
                $suffix = trim($matches[4]);
                $template = $prefix . ' :value' . $unit . $suffix;
                $result = __($template, ['value' => $value]);
                return $result !== $template ? $result : null;
            },
            // "효과 85% 진행 중" pattern
            '/^(.+?)\s*([\d.]+)(%?\s*)(.+)$/' => function ($matches) {
                $prefix = trim($matches[1]);
                $value = $matches[2];
                $unit = $matches[3];
                $suffix = trim($matches[4]);
                $template = $prefix . ' :value' . $unit . $suffix;
                $result = __($template, ['value' => $value]);
                return $result !== $template ? $result : null;
            },
        ];

        foreach ($patterns as $pattern => $handler) {
            if (preg_match($pattern, $text, $matches)) {
                $result = $handler($matches);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        // Fallback: return original text
        return $text;
    }
}
