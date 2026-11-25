@props([
    'headers' => [],
    'empty' => false,
    'emptyTitle' => 'No records found',
    'emptyDescription' => 'There are no items to display at this time.',
    'emptyIcon' => 'inbox',
])

<div class="w-full bg-white rounded-[20px] border border-slate-200/80 overflow-hidden shadow-[0_4px_20px_-2px_rgba(23,57,87,0.05)]">
    @if(isset($toolbar))
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            {{ $toolbar }}
        </div>
    @endif

    <div class="overflow-x-auto alerto-scrollbar">
        <table class="w-full text-left text-sm text-slate-700">
            @if(!empty($headers))
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                    <tr>
                        @foreach($headers as $header)
                            <th scope="col" class="px-6 py-3.5 whitespace-nowrap">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-slate-100">
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
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $pagination }}
        </div>
    @endif
</div>

