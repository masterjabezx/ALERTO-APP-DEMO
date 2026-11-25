@props([
    'headers' => [],
    'empty' => false,
    'emptyTitle' => 'No records found',
    'emptyDescription' => 'There are no items to display at this time.',
    'emptyIcon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'alerto-card w-full bg-white rounded-[20px] border border-[#E6E3DE] overflow-hidden']) }}>
    @if(isset($toolbar))
        <div class="p-4 sm:px-6 border-b border-[#E6E3DE] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            {{ $toolbar }}
        </div>
    @endif

    <div class="overflow-x-auto alerto-scrollbar">
        <table class="w-full text-left text-xs sm:text-sm text-[#292D32]">
            @if(!empty($headers))
                <thead class="bg-[#EFECE6] text-[11px] font-bold uppercase tracking-wider text-[#77736E] border-b border-[#E6E3DE]">
                    <tr>
                        @foreach($headers as $header)
                            <th scope="col" class="px-5 py-3.5 whitespace-nowrap">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-[#E6E3DE]/70">
                @if($empty)
                    <tr>
                        <td colspan="{{ count($headers) ?: 1 }}" class="p-10 text-center">
                            <x-empty-state
                                :title="$emptyTitle"
                                :description="$emptyDescription"
                                :icon="$emptyIcon"
                            />
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    @if(isset($pagination))
        <div class="p-4 border-t border-[#E6E3DE] bg-[#FAF8F5]">
            {{ $pagination }}
        </div>
    @endif
</div>
