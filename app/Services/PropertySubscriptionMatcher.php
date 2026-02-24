<?php

namespace App\Services;

use App\Enums\PropertyLeaseType;
use App\Models\Property;
use App\Models\PropertySubscription;
use Illuminate\Support\Collection;

class PropertySubscriptionMatcher
{
    /**
     * @return Collection<int, PropertySubscription>
     */
    public function findMatchingSubscriptions(Property $property): Collection
    {
        return PropertySubscription::query()
            ->active()
            ->get()
            ->filter(fn (PropertySubscription $subscription) => $this->propertyMatchesFilters($property, $subscription->filters));
    }

    public function propertyMatchesFilters(Property $property, array $filters): bool
    {
        if (isset($filters['neighbourhood']) && $filters['neighbourhood'] !== '') {
            $neighbourhoods = is_array($property->neighbourhoods) ? $property->neighbourhoods : [];
            if (! in_array($filters['neighbourhood'], $neighbourhoods, true)) {
                return false;
            }
        }

        if (isset($filters['availability']) && $filters['availability'] === 'available') {
            if ($property->taken) {
                return false;
            }
        }

        if (isset($filters['bedrooms_min']) || isset($filters['bedrooms_max'])) {
            $min = $filters['bedrooms_min'] ?? null;
            $max = $filters['bedrooms_max'] ?? null;
            if ($min !== null || $max !== null) {
                $bedrooms = (float) $property->bedrooms;
                if ($min !== null && $max !== null) {
                    $rangeMin = min((float) $min, (float) $max);
                    $rangeMax = max((float) $min, (float) $max);
                    if ($bedrooms < $rangeMin || $bedrooms > $rangeMax) {
                        return false;
                    }
                } elseif ($min !== null && $bedrooms < (float) $min) {
                    return false;
                } elseif ($max !== null && $bedrooms > (float) $max) {
                    return false;
                }
            }
        }

        if (isset($filters['furnished']) && $filters['furnished'] !== '') {
            if ((string) $property->furnished?->value !== (string) $filters['furnished']) {
                return false;
            }
        }

        if (isset($filters['type']) && $filters['type'] !== '') {
            if ((string) $property->type?->value !== (string) $filters['type']) {
                return false;
            }
        }

        if (isset($filters['type']) && $filters['type'] === PropertyLeaseType::MediumTerm->value) {
            if (isset($filters['available_from']) && $filters['available_from'] !== '') {
                if (! $property->available_from || $property->available_from->format('Y-m-d') < $filters['available_from']) {
                    return false;
                }
            }
            if (isset($filters['available_to']) && $filters['available_to'] !== '') {
                if (! $property->available_to || $property->available_to->format('Y-m-d') > $filters['available_to']) {
                    return false;
                }
            }
        }

        return true;
    }
}
