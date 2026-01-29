<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';

    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'response_code',
        'response_body',
        'status',
        'attempt_number',
        'response_time_ms',
        'error_message',
        'triggered_at',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'triggered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // العلاقات
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('triggered_at', '>=', now()->subDays($days));
    }

    // تنفيذ الطلب
    public function execute(): bool
    {
        $webhook = $this->webhook;
        if (!$webhook) return false;

        $startTime = microtime(true);

        try {
            $headers = $webhook->headers ?? [];
            $headers['Content-Type'] = 'application/json';

            $signature = $webhook->generateSignature($this->payload);
            if ($signature) {
                $headers['X-Webhook-Signature'] = $signature;
            }

            $request = Http::timeout($webhook->timeout_seconds)
                ->withHeaders($headers);

            if (!$webhook->verify_ssl) {
                $request->withoutVerifying();
            }

            $response = match ($webhook->method) {
                'POST' => $request->post($webhook->url, $this->payload),
                'PUT' => $request->put($webhook->url, $this->payload),
                'PATCH' => $request->patch($webhook->url, $this->payload),
                default => $request->post($webhook->url, $this->payload),
            };

            $endTime = microtime(true);
            $responseTimeMs = (int) (($endTime - $startTime) * 1000);

            $this->update([
                'response_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 10000),
                'response_time_ms' => $responseTimeMs,
                'status' => $response->successful() ? 'success' : 'failed',
                'error_message' => $response->successful() ? null : $response->body(),
                'completed_at' => now(),
            ]);

            if ($response->successful()) {
                $webhook->recordSuccess();
                return true;
            } else {
                $webhook->recordFailure();
                return false;
            }

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $responseTimeMs = (int) (($endTime - $startTime) * 1000);

            $this->update([
                'status' => 'failed',
                'response_time_ms' => $responseTimeMs,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $webhook->recordFailure();
            return false;
        }
    }

    // إعادة المحاولة
    public function retry(): self
    {
        return $this->webhook->logs()->create([
            'event_type' => $this->event_type,
            'payload' => $this->payload,
            'status' => 'pending',
            'attempt_number' => $this->attempt_number + 1,
            'triggered_at' => now(),
        ]);
    }

    // هل يمكن إعادة المحاولة؟
    public function canRetry(): bool
    {
        return $this->status === 'failed' 
            && $this->attempt_number < $this->webhook->max_retries;
    }
}
