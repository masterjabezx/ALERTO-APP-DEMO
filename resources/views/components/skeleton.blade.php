@props([
    'type' => 'card',
    'count' => 1,
])

@for($i = 0; $i < $count; $i++)
    @if($type === 'stat')
        <div class="alerto-card bg-white rounded-[20px] p-6 border border-[#E6E3DE] animate-pulse">
            <div class="flex items-center justify-between">
                <div class="space-y-2.5 flex-1">
                    <div class="h-3 bg-[#E6E3DE] rounded-md w-24"></div>
                    <div class="h-8 bg-[#E6E3DE] rounded-lg w-16"></div>
                    <div class="h-2.5 bg-[#EFECE6] rounded-md w-32"></div>
                </div>
                <div class="w-12 h-12 bg-[#EFECE6] rounded-2xl shrink-0"></div>
            </div>
        </div>
    @elseif($type === 'table-row')
        <tr class="animate-pulse">
            <td class="px-6 py-4"><div class="h-3.5 bg-[#E6E3DE] rounded w-24"></div></td>
            <td class="px-6 py-4"><div class="h-3.5 bg-[#E6E3DE] rounded w-36"></div></td>
            <td class="px-6 py-4"><div class="h-3.5 bg-[#E6E3DE] rounded w-20"></div></td>
            <td class="px-6 py-4"><div class="h-6 bg-[#EFECE6] rounded-full w-16"></div></td>
        </tr>
    @elseif($type === 'text')
        <div class="space-y-2 animate-pulse">
            <div class="h-3 bg-[#E6E3DE] rounded w-3/4"></div>
            <div class="h-3 bg-[#EFECE6] rounded w-1/2"></div>
        </div>
    @else
        <div class="alerto-card bg-white rounded-[20px] p-6 border border-[#E6E3DE] animate-pulse space-y-4">
            <div class="h-4 bg-[#E6E3DE] rounded w-1/3"></div>
            <div class="space-y-2">
                <div class="h-3 bg-[#EFECE6] rounded"></div>
                <div class="h-3 bg-[#EFECE6] rounded w-5/6"></div>
            </div>
            <div class="h-9 bg-[#EFECE6] rounded-xl w-28"></div>
        </div>
    @endif
@endfor
