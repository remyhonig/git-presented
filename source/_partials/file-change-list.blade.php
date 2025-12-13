@props(['changes' => collect()])

<div class="divide-y divide-gray-200">
    @foreach($changes as $change)
    <div class="py-2 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="w-6 text-center font-mono text-xs
                @if($change->isAdded()) text-green-600
                @elseif($change->isDeleted()) text-red-600
                @elseif($change->isModified()) text-yellow-600
                @elseif($change->isRenamed()) text-blue-600
                @else text-gray-600
                @endif
            ">
                @if($change->isAdded()) A
                @elseif($change->isDeleted()) D
                @elseif($change->isModified()) M
                @elseif($change->isRenamed()) R
                @else ?
                @endif
            </span>
            <span class="font-mono text-sm text-gray-800">{{ $change->path }}</span>
            @if($change->oldPath)
            <span class="text-gray-400 text-xs">(from {{ $change->oldPath }})</span>
            @endif
        </div>
        <div class="flex items-center space-x-2 text-xs">
            @if($change->additions > 0)
            <span class="text-green-600">+{{ $change->additions }}</span>
            @endif
            @if($change->deletions > 0)
            <span class="text-red-600">-{{ $change->deletions }}</span>
            @endif
        </div>
    </div>
    @endforeach
</div>
