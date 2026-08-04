<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <svg width="34" height="34" viewBox="0 0 34 34" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="sokoGradient" x1="0" y1="0" x2="34" y2="34" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#1B4332"/>
                <stop offset="1" stop-color="#2D6A4F"/>
            </linearGradient>
        </defs>

        <!-- Badge -->
        <rect width="34" height="34" rx="8" fill="url(#sokoGradient)"/>

        <!-- Market stall silhouette: awning + base -->
        <path d="M7 15 L17 8 L27 15 L27 16.5 L7 16.5 Z" fill="#F7F9F6"/>
        <rect x="9" y="17" width="16" height="9" rx="1" fill="#F7F9F6" fill-opacity="0.9"/>
        <rect x="12" y="20" width="4" height="6" fill="#1B4332"/>
        <rect x="18" y="20" width="4" height="6" fill="#1B4332"/>

        <!-- Growth accent -->
        <path d="M23 12 L26 9 L29 12" stroke="#E8A93B" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="26" cy="9" r="1.4" fill="#E8A93B"/>
    </svg>

    <span class="font-display text-xl text-[#1B4332] tracking-tight">
        Soko<span class="text-[#E8A93B]">Track</span>
    </span>
</div>
