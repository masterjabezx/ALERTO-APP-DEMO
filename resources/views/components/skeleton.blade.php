@props([
    'type' => 'card', // card, stat, table-row, text
    'count' => 1,
])

@for($i = 0; $i < $count; $i++)
    @if($type === 'stat')
        <div class="bg-white rounded-[20px] p-6 border border-slate-200/80 animate-pulse">
            <div class="flex items-center justify-between">
                <div class="space-y-2.5 flex-1">
                    <div class="h-3 bg-slate-200 rounded-md w-24"></div>
                    <div class="h-8 bg-slate-200 rounded-lg w-16"></div>
                    <div class="h-2.5 bg-slate-100 rounded-md w-32"></div>
                </div>
                <div class="w-12 h-12 bg-slate-200 rounded-2xl flex-shrink-0"></div>
            </div>
        </div>
    @elseif($type === 'table-row')
        <tr class="animate-pulse">
            <td class="px-6 py-4"><div class="h-3.5 bg-slate-200 rounded w-24"></div></td>
            <td class="px-6 py-4"><div class="h-3.5 bg-slate-200 rounded w-36"></div></td>
            <td class="px-6 py-4"><div class="h-3.5 bg-slate-200 rounded w-20"></div></td>
            <td class="px-6 py-4"><div class="h-6 bg-slate-200 rounded-full w-16"></div></td>
        </tr>
    @elseif($type === 'text')
        <div class="space-y-2 animate-pulse">
            <div class="h-3 bg-slate-200 rounded w-3/4"></div>
            <div class="h-3 bg-slate-200 rounded w-1/2"></div>
        </div>
    @else
        <div class="bg-white rounded-[20px] p-6 border border-slate-200/80 animate-pulse space-y-4">
            <div class="h-4 bg-slate-200 rounded w-1/3"></div>
            <div class="space-y-2">
                <div class="h-3 bg-slate-100 rounded"></div>
                <div class="h-3 bg-slate-100 rounded w-5/6"></div>
            </div>
            <div class="h-9 bg-slate-200 rounded-xl w-28"></div>
        </div>
    @endif
@endfor

