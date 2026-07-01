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
        $filterNeighbourhoods = $filters['neighbourhoods'] ?? null;
        if (! is_array($filterNeighbourhoods) || $filterNeighbourhoods === []) {
            $legacy = $filters['neighbourhood'] ?? null;
            $filterNeighbourhoods = is_string($legacy) && $legacy !== '' ? [$legacy] : [];
        }
        if ($filterNeighbourhoods !== []) {
            // 1. Ensure property neighborhoods are cast cleanly to integers
            $propertyNeighbourhoods = is_array($property->neighbourhoods)
                ? array_map('intval', $property->neighbourhoods)
                : [];

            // 2. Ensure filter IDs are cast cleanly to integers
            $filterIds = array_map('intval', $filterNeighbourhoods);

            // 3. Use PHP's native array_intersect to see if there's any match
            if (empty(array_intersect($filterIds, $propertyNeighbourhoods))) {
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
