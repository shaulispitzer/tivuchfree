<?php

namespace App\Console\Commands;

use App\Mail\PropertyListingStatusChange;
use App\Mail\PropertyTakenWarning;
use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProcessPropertyLifecycle extends Command
{
    protected $signature = 'properties:process-lifecycle';

    protected $description = 'Auto-mark properties as taken after 30 days, send warnings, and delete stale taken listings';

    public function handle(): int
    {
        $this->deleteTakenProperties();
        $this->markExpiredPropertiesAsTaken();
        $this->sendTakenWarnings();

        return self::SUCCESS;
    }

    /**
     * Delete properties that have been marked as taken for 14+ days.
     */
    protected function deleteTakenProperties(): void
    {
        $query = Property::query()
            ->with('user')
            ->where('taken', true)
            ->whereNotNull('taken_at')
            ->where('taken_at', '<=', now()->subDays(14));

        $count = 0;

        $query->chunkById(100, function ($properties) use (&$count) {
            foreach ($properties as $property) {
                if ($property->user?->email) {
                    $address = trim($property->street.($property->building_number ? ' '.$property->building_number : ''));
                    Mail::to($property->user->email)->locale('en')->queue(new PropertyListingStatusChange(
                        $property->user->name,
                        $address,
                        'deleted',
                        'automatically',
                    ));
                }
                $property->delete();
                $count++;
            }
        });

        if ($count > 0) {
            $this->info("Deleted {$count} properties that were taken for 14+ days.");
        }
    }

    /**
     * Auto-mark properties as taken if created 30+ days ago.
     */
    protected function markExpiredPropertiesAsTaken(): void
    {
        $properties = Property::query()
            ->with('user')
            ->where('taken', false)
            ->where('created_at', '<=', now()->subDays(30))
            ->get();

        $count = 0;

        foreach ($properties as $property) {
            $property->update([
                'taken' => true,
                'taken_at' => now(),
            ]);

            if ($property->user?->email) {
                $address = trim($property->street.($property->building_number ? ' '.$property->building_number : ''));
                Mail::to($property->user->email)->locale('en')->queue(new PropertyListingStatusChange(
                    $property->user->name,
                    $address,
                    'marked_as_taken',
                    'automatically',
                ));
            }

            $count++;
        }

        if ($count > 0) {
            $this->info("Marked {$count} properties as taken (30+ days old).");
        }
    }

    /**
     * Send warning emails 3 days before auto-marking as taken (at day 27).
     */
    protected function sendTakenWarnings(): void
    {
        $properties = Property::query()
            ->with('user')
            ->where('taken', false)
            ->whereNull('taken_warning_sent_at')
            ->where('created_at', '<=', now()->subDays(27))
            ->where('created_at', '>', now()->subDays(30))
            ->get();

        $count = 0;

        foreach ($properties as $property) {
            if (! $property->user?->email) {
                continue;
            }

            Mail::to($property->user->email)->locale('en')->queue(new PropertyTakenWarning($property));

            $property->update([
                'taken_warning_sent_at' => now(),
            ]);

            $count++;
        }

        if ($count > 0) {
            $this->info("Sent {$count} taken warning emails.");
        }
    }
}
