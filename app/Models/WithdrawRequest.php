<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WithdrawRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const METHOD_BANK = 'bank';

    public const METHOD_UPI = 'upi';

    public const METHOD_USDT = 'usdt';

    public const METHODS = [self::METHOD_BANK, self::METHOD_UPI, self::METHOD_USDT];

    protected $fillable = [
        'phone', 'amount', 'method', 'payout_upi_id', 'upi_number', 'upi_qr',
        'bank_account_holder', 'bank_account_number', 'bank_ifsc', 'bank_name', 'bank_branch',
        'usdt_address', 'usdt_qr',
        'status', 'admin_note', 'submitted_at', 'reviewed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $withdraw) {
            $withdraw->uuid ??= (string) Str::uuid();
        });
    }

    // Same reasoning as DepositRequest::getRouteKeyName() - the admin
    // approve/reject URLs must use the opaque uuid, never the sequential id.
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    #[Scope]
    protected function status(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // Single source of truth for "where is this money going" - used in the
    // admin table, the approval/rejection notification, and the audit log,
    // so the three never drift out of sync with each other.
    public function destinationLabel(): string
    {
        return match ($this->method) {
            self::METHOD_BANK => "{$this->bank_account_holder} · {$this->bank_name} · A/C {$this->bank_account_number} · IFSC {$this->bank_ifsc}",
            self::METHOD_USDT => "USDT (TRC20) · {$this->usdt_address}",
            default => (string) $this->payout_upi_id.($this->upi_number ? " · {$this->upi_number}" : ''),
        };
    }

    // Uploaded straight into public/assets/withdrawal-proofs (see
    // WithdrawRequestController::storeUploadedQr()) - same no-storage-symlink
    // convention as DepositRequest::paymentScreenshotUrl().
    public function upiQrUrl(): ?string
    {
        return $this->qrUrl($this->upi_qr);
    }

    public function usdtQrUrl(): ?string
    {
        return $this->qrUrl($this->usdt_qr);
    }

    private function qrUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset($path);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
