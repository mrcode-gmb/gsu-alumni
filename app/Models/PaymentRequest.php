<?php

namespace App\Models;

use App\Enums\PaymentRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentRequestFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_reference',
        'full_name',
        'matric_number',
        'email',
        'phone_number',
        'department',
        'faculty',
        'program_type_id',
        'program_type_name',
        'graduation_session',
        'payment_type_id',
        'payment_type_name',
        'payment_type_description',
        'base_amount',
        'portal_charge_amount',
        'paystack_charge_amount',
        'charge_settings_snapshot',
        'amount',
        'payment_status',
        'payment_reference',
        'paystack_reference',
        'paid_at',
        'payment_channel',
        'gateway_response',
        'initialization_payload',
        'verification_payload',
        'transaction_reference',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'program_type_id' => 'integer',
            'base_amount' => 'decimal:2',
            'portal_charge_amount' => 'decimal:2',
            'paystack_charge_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'payment_status' => PaymentRequestStatus::class,
            'paid_at' => 'datetime',
            'charge_settings_snapshot' => 'array',
            'initialization_payload' => 'array',
            'verification_payload' => 'array',
        ];
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function programType(): BelongsTo
    {
        return $this->belongsTo(ProgramType::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_reference';
    }

    public function canInitializePayment(): bool
    {
        return $this->payment_status->canInitialize();
    }
}
