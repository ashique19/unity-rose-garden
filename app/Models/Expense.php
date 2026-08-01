<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Expense extends Model
{
    protected $fillable = [
        'expense_head_id',
        'amount',
        'entry_date',
        'payee',
        'vendor_id',
        'note',
        'media',
        'balance_before',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'entry_date' => 'date',
            'media' => 'array',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function expenseHead(): BelongsTo
    {
        return $this->belongsTo(ExpenseHead::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(AccountLedgerEntry::class);
    }

    public function payeeName(): string
    {
        return $this->vendor?->name ?? ($this->payee ?: '');
    }

    /**
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
     * @return list<string>
     */
    public function mediaUrls(): array
    {
        return collect($this->media ?? [])
            ->pluck('url')
            ->filter(fn ($url) => is_string($url) && $url !== '')
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
