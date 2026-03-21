<?php

namespace App\Actions\Shared;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReorderModel
{
    public function handle(User $actor, Model $record, string $orderColumn, int $newPosition): void
    {
        Gate::forUser($actor)->authorize('update', $record);

        $modelClass = $record::class;
        $keyName = $record->getKeyName();

        DB::transaction(function () use ($modelClass, $record, $keyName, $orderColumn, $newPosition): void {
            /** @var list<int> $ids */
            $ids = $modelClass::query()
                ->orderBy($orderColumn)
                ->orderBy($keyName)
                ->pluck($keyName)
                ->all();

            $recordId = $record->getKey();

            $ids = array_values(array_filter($ids, static fn (int $id): bool => $id !== $recordId));

            $newPosition = max(0, min($newPosition, count($ids)));

            array_splice($ids, $newPosition, 0, [$recordId]);

            foreach ($ids as $position => $id) {
                $modelClass::query()
                    ->where($keyName, $id)
                    ->update([$orderColumn => $position + 1]);
            }
        });
    }
}
