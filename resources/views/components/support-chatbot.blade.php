@props(['whatsappNumber' => '254700000000'])

@php
    $commonFaqs = [
        [
            'keywords' => ['register', 'sign up', 'new vendor', 'create account'],
            'question' => 'How do I register as a vendor?',
            'answer' => 'Click "Register as Vendor" on the login page and fill in your business name, phone number, and contact details. You\'ll be able to start ordering immediately after signing up.',
        ],
        [
            'keywords' => ['review', 'rate product', 'rating'],
            'question' => 'How do I leave a product review?',
            'answer' => "You can review any product you've purchased (order must be confirmed) from that product's detail page — just click a star rating and add an optional comment.",
        ],
    ];

    $vendorFaqs = [
        [
            'keywords' => ['order status', 'track order', 'where is my order', 'order confirm'],
            'question' => 'How do I check my order status?',
            'answer' => 'You can view all your past orders and their status from your Dashboard. Confirmed orders show green, pending bank transfers show yellow, and cancelled orders show orange.',
        ],
        [
            'keywords' => ['credit', 'credit limit', 'credit terms', 'buy on credit'],
            'question' => 'How does credit work?',
            'answer' => 'Your credit limit is set by SokoTrack admin based on your order history and payment reliability. At checkout, choose "Credit" as your payment method — the order amount is added to your outstanding balance, which you can view on your Dashboard.',
        ],
        [
            'keywords' => ['loyalty', 'points', 'tier', 'bronze', 'silver', 'gold', 'rewards'],
            'question' => 'How do loyalty points and tiers work?',
            'answer' => 'You earn 1 loyalty point for every KES 100 spent on confirmed orders. Points determine your tier (Bronze, Silver, Gold), which can unlock better terms over time. Check your points balance in the shop header.',
        ],
        [
            'keywords' => ['low stock', 'out of stock', 'unavailable', 'restock'],
            'question' => 'What if a product is out of stock?',
            'answer' => "Out-of-stock products won't show in the shop until restocked. If something you need regularly goes low, our team gets automatic alerts and works to restock quickly.",
        ],
        [
            'keywords' => ['cancel', 'return', 'refund', 'mistake order'],
            'question' => 'Can I cancel an order?',
            'answer' => "Order cancellations are handled by our admin team — please reach out via WhatsApp with your order number and we'll sort it out quickly.",
        ],
        [
            'keywords' => ['payment method', 'mpesa', 'm-pesa', 'bank transfer', 'pay'],
            'question' => 'What payment methods are accepted?',
            'answer' => 'SokoTrack accepts M-Pesa, bank transfer, and credit terms (for approved vendors). Choose your preferred method at checkout.',
        ],
    ];

    $staffFaqs = [
        [
            'keywords' => ['add staff', 'new staff', 'create staff', 'staff account'],
            'question' => 'How do I add a new staff account?',
            'answer' => 'Go to Staff from the top nav, then click "Add Staff Member". You can set them as Admin or Warehouse Staff — vendor accounts are separate and created via public registration only.',
        ],
        [
            'keywords' => ['low stock alert', 'low-stock', 'stock alert', 'restock alert'],
            'question' => 'How do low-stock alerts work?',
            'answer' => 'A real-time email fires the moment a manual adjustment or a checkout sale pushes a product at or below its threshold. There\'s also a daily digest email sent every morning summarizing all low-stock items.',
        ],
        [
            'keywords' => ['confirm payment', 'bank transfer confirm', 'pending payment'],
            'question' => 'How do I confirm a pending bank transfer?',
            'answer' => 'Open the order from Orders, and if it\'s a pending bank transfer you\'ll see a "Confirm Bank Transfer" button on the order detail page.',
        ],
        [
            'keywords' => ['cancel order', 'reverse order', 'undo order'],
            'question' => 'How do I cancel an order as admin?',
            'answer' => 'From the order detail page, click "Cancel Order". This automatically reverses stock quantities and any loyalty points earned, all logged in the audit trail.',
        ],
        [
            'keywords' => ['credit score', 'credit health', 'vendor risk', 'risk score'],
            'question' => 'What is the Vendor Credit Health Score?',
            'answer' => 'It\'s a computed risk rating (Low/Medium/High) based on a vendor\'s payment reliability, credit utilization, and order cancellation rate. You\'ll see suggested credit limit adjustments on their vendor detail page — these are advisory only, never automatic.',
        ],
        [
            'keywords' => ['edit credit limit', 'change credit', 'vendor credit'],
            'question' => 'How do I change a vendor\'s credit limit?',
            'answer' => 'Go to Vendors, open the vendor\'s detail page, and use the "Edit Credit & Tier" form to update their credit limit or tier.',
        ],
    ];

    $isInternalStaff = auth()->check() && (auth()->user()->isAdmin() ?? false || auth()->user()->isInternalStaff() ?? false);

    $faqs = $isInternalStaff
        ? array_merge($staffFaqs, $commonFaqs)
        : array_merge($vendorFaqs, $commonFaqs);
@endphp

<div x-data="supportChatbot()" x-cloak class="fixed bottom-5 right-5 z-50">

    {{-- Floating trigger button --}}
    <button @click="open = ! open"
            class="h-14 w-14 rounded-full bg-[#1B4332] text-white shadow-lg flex items-center justify-center hover:bg-[#1B4332]/90 transition focus:outline-none">
        <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    {{-- Chat panel --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute bottom-20 right-0 w-80 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden flex flex-col"
         style="height: 24rem; max-height: calc(100vh - 7rem);">

        {{-- Header --}}
        <div class="bg-[#1B4332] px-4 py-3 flex items-center gap-2 shrink-0">
            <div class="h-8 w-8 rounded-full bg-white/15 flex items-center justify-center shrink-0">
                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-white text-sm font-medium">SokoTrack Support</p>
                <p class="text-white/60 text-xs">Usually replies instantly</p>
            </div>
        </div>

        {{-- Messages --}}
        <div x-ref="messages" class="flex-1 overflow-y-auto px-3 py-3 space-y-2.5 bg-[#F7F9F6]">
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.from === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.from === 'user'
                            ? 'bg-[#1B4332] text-white rounded-2xl rounded-br-sm'
                            : 'bg-white text-gray-700 rounded-2xl rounded-bl-sm border border-gray-100'"
                         class="max-w-[85%] px-3 py-1.5 text-xs leading-relaxed">
                        <span x-text="msg.text"></span>
                        <template x-if="msg.whatsapp">
                            <a :href="whatsappLink(msg.originalQuestion)" target="_blank"
                               class="mt-1.5 flex items-center gap-1.5 text-[#1B4332] font-medium text-xs hover:underline">
                                💬 Continue on WhatsApp
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Quick questions (shown only at the start) --}}
        <div x-show="messages.length <= 1" class="px-3 pb-2 flex flex-wrap gap-1.5 shrink-0">
            <template x-for="faq in faqs.slice(0, 3)" :key="faq.question">
                <button @click="askQuick(faq)"
                        class="text-xs bg-white border border-gray-200 text-gray-600 px-2 py-1 rounded-full hover:border-[#1B4332]/40 hover:text-[#1B4332] transition"
                        x-text="faq.question"></button>
            </template>
        </div>

        {{-- Input --}}
        <form @submit.prevent="sendMessage" class="border-t border-gray-100 p-2.5 flex gap-2 shrink-0">
            <input type="text" x-model="input" placeholder="Ask a question..."
                   class="flex-1 rounded-lg border-gray-300 text-xs focus:ring-[#1B4332] focus:border-[#1B4332]">
            <button type="submit" class="bg-[#1B4332] text-white px-3 py-1.5 rounded-lg hover:bg-[#1B4332]/90 focus:outline-none shrink-0">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
    window.supportChatbot = function () {
        return {
            open: false,
            input: '',
            whatsappNumber: '{{ $whatsappNumber }}',
            faqs: @json($faqs),
            messages: [
                { from: 'bot', text: "Hi! I'm the SokoTrack support bot. Ask me anything, or pick a quick question below." }
            ],

            askQuick(faq) {
                this.messages.push({ from: 'user', text: faq.question });
                this.messages.push({ from: 'bot', text: faq.answer });
                this.scrollDown();
            },

            sendMessage() {
                const question = this.input.trim();
                if (!question) return;

                this.messages.push({ from: 'user', text: question });

                const match = this.findMatch(question);

                if (match) {
                    this.messages.push({ from: 'bot', text: match.answer });
                } else {
                    this.messages.push({
                        from: 'bot',
                        text: "I couldn't find an answer to that. Let's get you to a real person —",
                        whatsapp: true,
                        originalQuestion: question,
                    });
                }

                this.input = '';
                this.scrollDown();
            },

            findMatch(question) {
                const q = question.toLowerCase();
                return this.faqs.find(faq =>
                    faq.keywords.some(keyword => q.includes(keyword))
                );
            },

            whatsappLink(question) {
                const message = encodeURIComponent(`Hi, I have a question: ${question}`);
                return `https://wa.me/${this.whatsappNumber}?text=${message}`;
            },

            scrollDown() {
                this.$nextTick(() => {
                    this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                });
            },
        };
    };
</script>
