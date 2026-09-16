فرصت‌های جدید آوسبیلدونگ در ۷ روز گذشته: {{ $opportunities->count() }}

@forelse($opportunities as $opportunity)
• {{ $opportunity->title_de }} — {{ $opportunity->employer_name }} ({{ $opportunity->city }})
{{ $frontendUrl }}/opportunities/{{ rawurlencode($opportunity->slug) }}

@empty
در این هفته فرصت جدیدی منتشر نشده است.
@endforelse
