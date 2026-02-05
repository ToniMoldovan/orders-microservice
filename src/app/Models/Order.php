<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'customer_email',
        'total_amount',
        'currency',
        'order_created_at',
        'payload_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_created_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Set the currency attribute, ensuring it's uppercase.
     *
     * @param  string  $value
     * @return void
     */
    public function setCurrencyAttribute($value): void
    {
        $this->attributes['currency'] = strtoupper($value);
    }

    /**
     * Scope a query to filter by customer email.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $email
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCustomer($query, string $email)
    {
        return $query->where('customer_email', $email);
    }
}
