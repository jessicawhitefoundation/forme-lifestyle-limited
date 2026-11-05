<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = ['user_id', 'session_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->items->sum(fn ($item) => $item->variant->final_price * $item->quantity);
    }

    public function getCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Merge items from a guest session cart into an authenticated user's cart.
     */
    public static function mergeSessionCartToUser(string $sessionId, int $userId): void
    {
        if (empty($sessionId)) {
            return;
        }

        $sessionCart = self::where('session_id', $sessionId)->first();
        if (! $sessionCart || $sessionCart->items->isEmpty()) {
            return;
        }

        $userCart = self::firstOrCreate(['user_id' => $userId]);

        foreach ($sessionCart->items as $sessionItem) {
            $existingItem = $userCart->items()
                ->where('product_variant_id', $sessionItem->product_variant_id)
                ->first();

            if ($existingItem) {
                $existingItem->increment('quantity', $sessionItem->quantity);
            } else {
                $sessionItem->update(['cart_id' => $userCart->id]);
            }
        }

        $sessionCart->delete();
    }
}
