<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table('bed_type_bedroom', incrementing: true)]
class BedTypeBedroom extends Pivot
{
    public $incrementing = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bedroom_id',
        'bed_type_id',
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
     * @return BelongsTo<BedType, $this>
     */
    public function bedType(): BelongsTo
    {
        return $this->belongsTo(BedType::class);
    }
}
