<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table('bath_room_type_bedroom', incrementing: true)]
class BathRoomTypeBedroom extends Pivot
{
    public $incrementing = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bedroom_id',
        'bath_room_type_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Bedroom, $this>
     */
    public function bedroom(): BelongsTo
    {
        return $this->belongsTo(Bedroom::class);
    }

    /**
     * @return BelongsTo<BathRoomType, $this>
     */
    public function bathRoomType(): BelongsTo
    {
        return $this->belongsTo(BathRoomType::class);
    }
}
