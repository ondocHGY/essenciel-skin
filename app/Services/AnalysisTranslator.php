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
     * Safely apply __() only to string values.
     */
    private static function t(mixed $value): mixed
    {
        return is_string($value) ? __($value) : $value;
    }

    /**
     * Translate timeline milestone messages.
     */
    public static function translateTimeline(array $timeline): array
    {
        if (isset($timeline['milestones']) && is_array($timeline['milestones'])) {
            foreach ($timeline['milestones'] as $day => &$message) {
                if (is_string($message)) {
                    $message = self::translatePatternString($message);
                }
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
            if (!is_array($milestone)) continue;
            if (isset($milestone['message']) && is_string($milestone['message'])) {
                $milestone['message'] = self::translatePatternString($milestone['message']);
            }
            if (isset($milestone['title']) && is_string($milestone['title'])) {
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
        if (isset($metrics['name']) && is_string($metrics['name'])) {
            $metrics['name'] = __($metrics['name']);
        }
        if (isset($metrics['description']) && is_string($metrics['description'])) {
            $metrics['description'] = __($metrics['description']);
        }

        $metricKeys = ['moisture', 'elasticity', 'tone', 'pore', 'wrinkle', 'soothing'];
        foreach ($metricKeys as $key) {
            if (isset($metrics[$key]) && is_array($metrics[$key])) {
                if (isset($metrics[$key]['name']) && is_string($metrics[$key]['name'])) {
                    $metrics[$key]['name'] = __($metrics[$key]['name']);
                }
                if (isset($metrics[$key]['description']) && is_string($metrics[$key]['description'])) {
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
        if (isset($guide['optimal_usage']) && is_array($guide['optimal_usage'])) {
            $usage = &$guide['optimal_usage'];

            // timing: { best, reason, morning_effect, evening_effect }
            if (isset($usage['timing']) && is_array($usage['timing'])) {
                if (isset($usage['timing']['reason']) && is_string($usage['timing']['reason'])) {
                    $usage['timing']['reason'] = __($usage['timing']['reason']);
                }
                if (isset($usage['timing']['best']) && is_string($usage['timing']['best'])) {
                    $usage['timing']['best'] = __($usage['timing']['best']);
                }
            }
            // legacy flat keys (이전 데이터 호환)
            if (isset($usage['best_time']) && is_string($usage['best_time'])) {
                $usage['best_time'] = __($usage['best_time']);
            }
            // frequency: { recommended, once_effect, twice_effect, with_mask_effect }
            if (isset($usage['frequency']) && is_array($usage['frequency'])) {
                if (isset($usage['frequency']['recommended']) && is_string($usage['frequency']['recommended'])) {
                    $usage['frequency']['recommended'] = __($usage['frequency']['recommended']);
                }
            } elseif (isset($usage['frequency']) && is_string($usage['frequency'])) {
                $usage['frequency'] = __($usage['frequency']);
            }
            // absorption: { time, tip }
            if (isset($usage['absorption']) && is_array($usage['absorption'])) {
                if (isset($usage['absorption']['tip']) && is_string($usage['absorption']['tip'])) {
                    $usage['absorption']['tip'] = __($usage['absorption']['tip']);
                }
            }
            if (isset($usage['absorption_tip']) && is_string($usage['absorption_tip'])) {
                $usage['absorption_tip'] = __($usage['absorption_tip']);
            }
            if (isset($usage['optimal_amount']) && is_string($usage['optimal_amount'])) {
                $usage['optimal_amount'] = __($usage['optimal_amount']);
            }
        }

        if (isset($guide['recommendations']) && is_array($guide['recommendations'])) {
            foreach ($guide['recommendations'] as &$rec) {
                if (!is_array($rec)) continue;
                if (isset($rec['action']) && is_string($rec['action'])) {
                    $rec['action'] = __($rec['action']);
                }
                if (isset($rec['action_short']) && is_string($rec['action_short'])) {
                    $rec['action_short'] = __($rec['action_short']);
                }
                if (isset($rec['description']) && is_string($rec['description'])) {
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
        if (isset($profile['characteristics']) && is_array($profile['characteristics'])) {
            foreach ($profile['characteristics'] as $key => &$char) {
                if (!is_array($char)) continue;
                if (isset($char['label']) && is_string($char['label'])) {
                    $char['label'] = __($char['label']);
                }
                if (isset($char['description']) && is_string($char['description'])) {
                    $char['description'] = __($char['description']);
                }
            }
        }

        if (isset($profile['summary']) && is_string($profile['summary'])) {
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
            if (!is_array($factor)) continue;
            if (isset($factor['name']) && is_string($factor['name'])) {
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
        $translated = __($text);
        if ($translated !== $text) {
            return $translated;
        }

        $patterns = [
            '/^(.+?)([\d.]+)(%p?\s*)(.+)$/' => function ($matches) {
                $prefix = trim($matches[1]);
                $value = $matches[2];
                $unit = $matches[3];
                $suffix = trim($matches[4]);
                $template = $prefix . ' :value' . $unit . $suffix;
                $result = __($template, ['value' => $value]);
                return $result !== $template ? $result : null;
            },
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

        return $text;
    }
}
