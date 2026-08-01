<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class AccountLedgerEntry extends Model
{
    public const TYPE_CASH_IN = 'cash_in';

    public const TYPE_CASH_OUT = 'cash_out';

    protected $fillable = [
        'type',
        'amount',
        'entry_date',
        'flat_id',
        'collection_id',
        'expense_id',
        'category',
        'expense_head_id',
        'payee',
        'vendor_id',
        'note',
        'media',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'entry_date' => 'date',
            'media' => 'array',
        ];
    }

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function expenseHead(): BelongsTo
    {
        return $this->belongsTo(ExpenseHead::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isCashIn(): bool
    {
        return $this->type === self::TYPE_CASH_IN;
    }

    public function isCashOut(): bool
    {
        return $this->type === self::TYPE_CASH_OUT;
    }

    /**
     * Attachment ids referenced by this entry's media JSON.
     *
     * @return list<int>
     */
    public function mediaAttachmentIds(): array
    {
        return collect($this->media ?? [])
            ->pluck('attachment_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Attachment>|null  $attachmentsById
     * @return list<array{title: string, url: string, source: string}>
     */
    public function resolvedMedia(?Collection $attachmentsById = null): array
    {
        $items = [];

        foreach ($this->media ?? [] as $item) {
            if (! empty($item['attachment_id'])) {
                $attachment = $attachmentsById?->get((int) $item['attachment_id'])
                    ?? Attachment::query()->find($item['attachment_id']);

                if ($attachment) {
                    $items[] = [
                        'title' => $attachment->title,
                        'url' => $attachment->url(),
                        'source' => 'gallery',
                    ];
                }

                continue;
            }

            if (! empty($item['url']) && is_string($item['url'])) {
                $items[] = [
                    'title' => 'Link',
                    'url' => $item['url'],
                    'source' => 'url',
                ];
            }
        }

        return $items;
    }
}
