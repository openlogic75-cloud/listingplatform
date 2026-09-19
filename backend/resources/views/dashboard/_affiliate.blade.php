@php
    /** @var array<string, mixed> $referrals */
    $symbol = config('app.currency_symbol', 'Rs. ');
@endphp

<div class="dash-card dash-card--wide">
    <div class="dash-card-head">
        <h2>Affiliate &amp; referral</h2>
        @if (($referrals['codes_count'] ?? 0) > 0)
            <span class="small muted">
                {{ $referrals['total_conversions'] ?? 0 }}
                {{ \Illuminate\Support\Str::plural('order', $referrals['total_conversions'] ?? 0) }} through your codes
                &middot; {{ $symbol }}{{ number_format((float) ($referrals['total_earnings'] ?? 0), 2) }} commission approved
                @if (($referrals['pending_count'] ?? 0) > 0)
                    &middot; {{ $referrals['pending_count'] }} awaiting your approval
                @endif
            </span>
        @endif
    </div>

    <p class="muted small">
        Share a code with people you trust to market your business. You set the
        commission, and it only counts once <strong>you</strong> approve it —
        you pay the marketer directly. Nothing moves through the platform.
    </p>

    @if (! empty($referrals['pending']))
        <h3 class="small muted" style="margin: var(--space-2) 0 var(--space-1);">
            Awaiting your approval
        </h3>
        <ul class="dash-list" style="margin-bottom: var(--space-3);">
            @foreach ($referrals['pending'] as $pending)
                <li class="dash-row">
                    <div class="dash-row-body">
                        <div class="dash-row-title">
                            {{ $symbol }}{{ number_format($pending['amount'], 2) }}
                            <span class="muted small">commission</span>
                        </div>
                        <div class="dash-row-meta">
                            Code {{ $pending['code'] }}
                            &middot; order {{ $symbol }}{{ number_format($pending['order_value'], 2) }}
                            @if ($pending['recorded_at'])
                                &middot; {{ $pending['recorded_at']->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                    <div class="dash-row-actions">
                        <form method="post" action="{{ route('dashboard.referrals.approve', $pending['id']) }}">
                            @csrf
                            <button class="btn btn-primary btn-sm" type="submit">Approve</button>
                        </form>
                        <form method="post" action="{{ route('dashboard.referrals.reject', $pending['id']) }}">
                            @csrf
                            <button class="btn btn-secondary btn-sm" type="submit">Decline</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if (! empty($referrals['codes']))
        <ul class="dash-list" style="margin-bottom: var(--space-3);">
            @foreach ($referrals['codes'] as $code)
                <li class="dash-row">
                    <div class="dash-row-body">
                        <div class="dash-row-title">{{ $code['code'] }}</div>
                        <div class="dash-row-meta">
                            Commission {{ $code['commission_label'] }}
                            &middot; {{ $code['signups_count'] }} signups
                            &middot; {{ $code['conversions_count'] }}
                            {{ \Illuminate\Support\Str::plural('order', $code['conversions_count']) }}
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="muted small">No codes yet.</p>
    @endif

    <form method="post" action="{{ route('dashboard.referrals.store') }}">
        @csrf
        <div class="dash-form-grid">
            <div class="field">
                <label for="referral-label">Label <span class="muted small">(optional)</span></label>
                <input id="referral-label" name="label" type="text" maxlength="40"
                       placeholder="e.g. instagram" value="{{ old('label') }}">
                @error('label')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="commission-type">Commission type</label>
                <select id="commission-type" name="commission_type" required>
                    <option value="percent" @selected(old('commission_type', 'percent') === 'percent')>Percentage of order</option>
                    <option value="fixed" @selected(old('commission_type') === 'fixed')>Fixed amount per order</option>
                </select>
                @error('commission_type')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="commission-value">Commission value</label>
                <input id="commission-value" name="commission_value" type="number" step="0.01" min="0"
                       required value="{{ old('commission_value', '5') }}">
                <span class="muted small">A percentage (e.g. 5) or an amount, matching the type.</span>
                @error('commission_value')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field field--full">
                <button class="btn btn-primary" type="submit">Create affiliate code</button>
            </div>
        </div>
    </form>
</div>
