<?php

namespace App\Observers;

use App\Models\Property;
use App\Services\PropertyStatRecorder;

class PropertyObserver
{
    public function __construct(public PropertyStatRecorder $propertyStatRecorder) {}

    public function updated(Property $property): void
    {
        if (! $property->wasChanged('taken')) {
            return;
        }

        if (! $property->taken) {
            return;
        }

        $this->propertyStatRecorder->recordTaken($property);
    }

    public function deleting(Property $property): void
    {
        $this->propertyStatRecorder->recordDeleted($property);
    }
}
