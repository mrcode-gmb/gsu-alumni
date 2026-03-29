<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentType extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTypeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'amount',
        'service_charge_amount',
        'paystack_charge_amount',
        'description',
        'is_active',
        'display_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'paystack_charge_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where('name', 'like', "%{$search}%");
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN display_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('display_order')
            ->orderBy('name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }

    public function programTypes(): BelongsToMany
    {
        return $this->belongsToMany(ProgramType::class)
            ->withTimestamps();
    }
}
