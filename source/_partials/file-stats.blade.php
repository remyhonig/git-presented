@props(['additions' => 0, 'deletions' => 0, 'files' => 0])

<div class="flex items-center space-x-3 text-sm">
    @if($files > 0)
    <span class="text-gray-500">
        {{ $files }} file{{ $files !== 1 ? 's' : '' }}
    </span>
    @endif
    @if($additions > 0)
    <span class="text-green-600">
        +{{ $additions }}
    </span>
    @endif
    @if($deletions > 0)
    <span class="text-red-600">
        -{{ $deletions }}
    </span>
    @endif
</div>
