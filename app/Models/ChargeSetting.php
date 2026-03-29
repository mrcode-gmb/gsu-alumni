<?php

namespace App\Models;

use App\Enums\ChargeCalculationMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeSetting extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'portal_charge_mode',
        'portal_charge_value',
        'paystack_percentage_rate',
        'paystack_flat_fee',
        'paystack_flat_fee_threshold',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'portal_charge_mode' => ChargeCalculationMode::class,
            'portal_charge_value' => 'decimal:2',
            'paystack_percentage_rate' => 'decimal:4',
            'paystack_flat_fee' => 'decimal:2',
            'paystack_flat_fee_threshold' => 'decimal:2',
            'updated_by' => 'integer',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
